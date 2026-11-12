<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Mengirim query SPARQL 1.1 ke Database 3 lewat protokol SPARQL W3C
 * dan mengubah hasilnya ke bentuk tabel yang dipakai halaman /data: ['ok', 'kolom', 'baris', 'catatan'].
 */
class SparqlService
{
    private const BATAS_BARIS = 2000;

    /** IRI panjang disingkat dengan prefix yang sama seperti di file Turtle */
    private const PREFIX = [
        'https://medanvisit.neoverse.my.id/id/' => 'res:',
        'https://medanvisit.neoverse.my.id/ontologi#' => 'mv:',
        'https://schema.org/' => 'schema:',
        'http://www.wikidata.org/entity/' => 'wd:',
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#' => 'rdf:',
        'http://www.w3.org/2000/01/rdf-schema#' => 'rdfs:',
        'http://www.w3.org/2002/07/owl#' => 'owl:',
        'http://purl.org/dc/terms/' => 'dcterms:',
    ];

    public static function endpoint(): ?string
    {
        return config('services.sparql.endpoint') ?: null;
    }

    private static function singkat(string $iri): string
    {
        foreach (self::PREFIX as $ns => $p) {
            if (str_starts_with($iri, $ns)) {
                return $p.substr($iri, strlen($ns));
            }
        }

        return $iri;
    }

    private static function nilai(array $b): string|int|float
    {
        if ($b['type'] === 'uri') {
            return self::singkat($b['value']);
        }
        $tipe = $b['datatype'] ?? '';
        if (preg_match('/#(integer|int|long|short|positiveInteger|nonNegativeInteger)$/', $tipe)) {
            return (int) $b['value'];
        }
        if (preg_match('/#(decimal|double|float)$/', $tipe)) {
            return (float) $b['value'];
        }

        return $b['value'];
    }

    /**
     * @throws ConnectionException bila Database 3 tidak bisa dihubungi
     */
    public static function jalankan(string $query, int $batas = self::BATAS_BARIS): array
    {
        $batas = max(1, $batas);
        $jenis = preg_match('/^\s*(?:(?:PREFIX|BASE)\s[^\n]*\n|#[^\n]*\n|\s)*(SELECT|ASK|CONSTRUCT|DESCRIBE)\b/i', $query, $m) ? strtoupper($m[1]) : null;
        if ($jenis === null) {
            return ['ok' => false, 'pesan' => 'Endpoint ini hanya menerima query SELECT, ASK, CONSTRUCT, atau DESCRIBE.'];
        }

        $grafik = in_array($jenis, ['CONSTRUCT', 'DESCRIBE'], true);
        $http = Http::timeout((int) config('services.sparql.timeout', 30));
        $username = config('services.sparql.username');
        $password = config('services.sparql.password');
        if ($username !== null && $password !== null) {
            $http = $http->withBasicAuth($username, $password);
        }

        $jawab = $http
            ->accept($grafik ? 'application/n-triples' : 'application/sparql-results+json')
            ->asForm()
            ->post(self::endpoint(), ['query' => $query]);

        if (! $jawab->successful()) {
            $pesan = trim(strtok($jawab->body(), "\n")) ?: 'Database 3 menolak query ini.';

            return ['ok' => false, 'pesan' => 'Query tidak valid: '.mb_substr($pesan, 0, 300)];
        }

        $catatan = 'dijalankan di Database 3 '.config('services.sparql.driver').' ('.parse_url(self::endpoint(), PHP_URL_HOST).').';

        if ($grafik) {
            $baris = [];
            foreach (preg_split('/\R/', trim($jawab->body())) as $tripel) {
                if (preg_match('/^(\S+)\s+(\S+)\s+(.+?)\s*\.$/', $tripel, $t)) {
                    // objek literal ("nilai"^^<tipe> atau "nilai"@bahasa) cukup ditampilkan nilainya
                    $objek = preg_match('/^"(.*)"(?:\^\^<[^>]+>|@[\w-]+)?$/s', $t[3], $l) ? stripcslashes($l[1]) : self::singkat(trim($t[3], '<>'));
                    $baris[] = [self::singkat(trim($t[1], '<>')), self::singkat(trim($t[2], '<>')), $objek];
                }
            }

            return ['ok' => true, 'kolom' => ['subjek', 'predikat', 'objek'], 'baris' => array_slice($baris, 0, $batas), 'catatan' => $catatan];
        }

        $hasil = $jawab->json();
        if ($jenis === 'ASK') {
            return ['ok' => true, 'kolom' => ['jawaban'], 'baris' => [[$hasil['boolean'] ? 'ya (true)' : 'tidak (false)']], 'catatan' => $catatan];
        }

        $kolom = $hasil['head']['vars'] ?? [];
        $baris = array_map(
            fn ($b) => array_map(fn ($k) => isset($b[$k]) ? self::nilai($b[$k]) : '', $kolom),
            array_slice($hasil['results']['bindings'] ?? [], 0, $batas),
        );

        return ['ok' => true, 'kolom' => $kolom, 'baris' => $baris, 'catatan' => $catatan];
    }
}
