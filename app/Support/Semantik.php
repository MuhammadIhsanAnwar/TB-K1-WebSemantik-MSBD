<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;

/**
 * Lapisan web semantik: unduhan RDF Turtle dan JSON-LD, serta query SPARQL ke Database 3
 * (SPARQL_ENDPOINT). Kelas dan properti ada di database/rdf/medanvisit-ontologi.ttl.
 */
class Semantik
{
    public const BASIS = 'https://medanvisit.neoverse.my.id/';

    public const CONTOH = [
        'dibangun-pemimpin-deli' => [
            'judul' => 'Tempat yang dibangun pemimpin Kesultanan Deli',
            'kolom' => ['tempat', 'tokoh'],
            'query' => "PREFIX mv: <https://medanvisit.neoverse.my.id/ontologi#>\nPREFIX res: <https://medanvisit.neoverse.my.id/id/>\n\nSELECT ?tempat ?tokoh WHERE {\n  ?tokoh  mv:memimpin    res:kesultanan-deli .\n  ?tempat mv:dibangunOleh ?tokoh .\n}",
        ],
        'kuliner-melayu-deli' => [
            'judul' => 'Kuliner khas Melayu Deli',
            'kolom' => ['kuliner', 'keterangan'],
            'query' => "PREFIX mv: <https://medanvisit.neoverse.my.id/ontologi#>\nPREFIX res: <https://medanvisit.neoverse.my.id/id/>\nPREFIX schema: <https://schema.org/>\n\nSELECT ?kuliner ?keterangan WHERE {\n  ?kuliner mv:khasDari res:melayu-deli ;\n           schema:description ?keterangan .\n}",
        ],
        'bertiket-termurah' => [
            'judul' => 'Tempat bertiket, urut dari yang termurah',
            'kolom' => ['tempat', 'harga'],
            'query' => "PREFIX mv: <https://medanvisit.neoverse.my.id/ontologi#>\n\nSELECT ?tempat ?harga WHERE {\n  ?tempat a mv:TempatWisata ;\n          mv:hargaTiketMulai ?harga .\n}\nORDER BY ?harga",
        ],
        'dekat-istana' => [
            'judul' => 'Tempat dalam 1 km dari Istana Maimun',
            'kolom' => ['tempat', 'jarak_m'],
            'query' => "PREFIX mv: <https://medanvisit.neoverse.my.id/ontologi#>\nPREFIX res: <https://medanvisit.neoverse.my.id/id/>\n\nSELECT ?tempat ?jarak_m WHERE {\n  ?k a mv:Kedekatan ;\n     mv:antara res:istana-maimun, ?tempat ;\n     mv:jarakMeter ?jarak_m .\n  FILTER (?tempat != res:istana-maimun && ?jarak_m <= 1000)\n}\nORDER BY ?jarak_m",
        ],
    ];

    private static function rapikan(string $q): string
    {
        return preg_replace('/\s+/', ' ', trim($q));
    }

    /** Jalankan query hanya di Database 3; salinan lokal tidak digunakan sebagai sumber jawaban. */
    public static function jalankan(string $query): array
    {
        if (! SparqlService::endpoint()) {
            return ['ok' => false, 'pesan' => 'Endpoint SPARQL Database 3 belum diatur.'];
        }

        try {
            return SparqlService::jalankan($query);
        } catch (ConnectionException $e) {
            return ['ok' => false, 'pesan' => 'Database 3 tidak bisa dihubungi: '.$e->getMessage()];
        }
    }

    private static function hasil(string $id): array
    {
        $nama = fn ($s) => Katalog::get($s)['nama'];
        $baris = [];
        switch ($id) {
            case 'dibangun-pemimpin-deli':
                foreach (Katalog::relasi() as $r) {
                    if ($r[1] === 'memimpin' && $r[2] === 'kesultanan-deli') {
                        foreach (Katalog::relasi() as $r2) {
                            if ($r2[1] === 'dibangunOleh' && $r2[2] === $r[0]) {
                                $baris[] = [$nama($r2[0]), $nama($r[0])];
                            }
                        }
                    }
                }
                break;
            case 'kuliner-melayu-deli':
                foreach (Katalog::relasi() as $r) {
                    if ($r[1] === 'khasDari' && $r[2] === 'melayu-deli') {
                        $baris[] = [$nama($r[0]), Katalog::get($r[0])['ringkas']];
                    }
                }
                break;
            case 'bertiket-termurah':
                $t = Katalog::bertiket();
                uasort($t, fn ($a, $b) => Katalog::hargaTermurah($a) <=> Katalog::hargaTermurah($b));
                foreach ($t as $x) {
                    $baris[] = [$x['nama'], Katalog::hargaTermurah($x)];
                }
                break;
            case 'dekat-istana':
                $istana = Katalog::get('istana-maimun');
                foreach (Katalog::tempat() as $x) {
                    $j = Katalog::jarakMeter($istana, $x);
                    if ($x['slug'] !== 'istana-maimun' && $j <= 1000) {
                        $baris[$x['nama']] = [$x['nama'], $j];
                    }
                }
                usort($baris, fn ($a, $b) => $a[1] <=> $b[1]);
                break;
        }

        return array_values($baris);
    }

    /**
     * Ontologi dan data dalam satu dokumen Turtle, persis file yang dimuat ke Database 3
     * (database/rdf/medanvisit-ontologi.ttl dan medanvisit-data.ttl, dibuat ulang dengan `php artisan rdf:ekspor`).
     */
    public static function turtle(): string
    {
        return collect(['medanvisit-ontologi.ttl', 'medanvisit-data.ttl'])
            ->map(fn ($f) => file_get_contents(database_path("rdf/$f")))
            ->implode("
");
    }

    public static function jsonld(): array
    {
        $graph = [];
        foreach (Katalog::tempat() as $slug => $t) {
            $n = [
                '@type' => 'TouristAttraction',
                '@id' => self::BASIS.'id/'.$slug,
                'name' => $t['nama'],
                'description' => $t['ringkas'],
                'address' => ['@type' => 'PostalAddress', 'streetAddress' => $t['alamat'], 'addressLocality' => 'Medan', 'addressRegion' => 'Sumatera Utara', 'addressCountry' => 'ID'],
                'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $t['lat'], 'longitude' => $t['lng']],
                'isAccessibleForFree' => empty($t['tiket']),
            ];
            if (! empty($t['tiket'])) {
                $n['offers'] = array_map(fn ($jenis, $harga) => ['@type' => 'Offer', 'name' => $jenis, 'price' => $harga, 'priceCurrency' => 'IDR'], array_keys($t['tiket']), $t['tiket']);
            }
            $graph[] = $n;
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /** JSON-LD satu tempat, untuk disematkan di halaman detail. */
    public static function jsonldTempat(array $t): array
    {
        foreach (self::jsonld()['@graph'] as $n) {
            if ($n['@id'] === self::BASIS.'id/'.$t['slug']) {
                return ['@context' => 'https://schema.org'] + $n;
            }
        }

        return [];
    }
}
