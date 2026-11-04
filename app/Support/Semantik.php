<?php

namespace App\Support;

/**
 * Lapisan web semantik prototipe: ekspor RDF Turtle dan JSON-LD dari katalog, dan contoh query SPARQL.
 * Query contoh dijalankan pada salinan data lokal; query lain dikirim ke Apache Jena Fuseki
 * setelah endpoint SPARQL_ENDPOINT tersedia (lihat .env).
 */
class Semantik
{
    public const BASIS = 'https://medanvisit.neoverse.my.id/';

    public const KELAS_RDF = [
        'tempat' => ['mv:TempatWisata', 'schema:TouristAttraction'],
        'tokoh' => ['mv:Tokoh', 'schema:Person'],
        'kerajaan' => ['mv:Kerajaan', 'schema:Organization'],
        'peristiwa' => ['mv:Peristiwa', 'schema:Event'],
        'budaya' => ['mv:Budaya', 'schema:Thing'],
        'kuliner' => ['mv:Kuliner', 'schema:Thing'],
        'transportasi' => ['mv:ModaTransportasi', 'schema:Thing'],
        'wilayah' => ['mv:Kecamatan', 'schema:AdministrativeArea'],
    ];

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

    public static function jalankan(string $query): array
    {
        foreach (self::CONTOH as $id => $c) {
            if (self::rapikan($c['query']) === self::rapikan($query)) {
                return ['ok' => true, 'kolom' => $c['kolom'], 'baris' => self::hasil($id), 'catatan' => 'Dijalankan pada salinan data lokal MedanVisit.'];
            }
        }

        return [
            'ok' => false,
            'pesan' => 'Endpoint SPARQL (Apache Jena Fuseki) belum tersambung, jadi hanya query contoh yang bisa dijalankan sekarang. Query ini akan bisa dijalankan setelah SPARQL_ENDPOINT diisi.',
        ];
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

    private static function lit(string $s): string
    {
        return '"'.addcslashes($s, "\"\\\n").'"';
    }

    public static function turtle(): string
    {
        $o = [];
        $o[] = '@prefix mv: <'.self::BASIS.'ontologi#> .';
        $o[] = '@prefix res: <'.self::BASIS.'id/> .';
        $o[] = '@prefix schema: <https://schema.org/> .';
        $o[] = '@prefix geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> .';
        $o[] = '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .';
        $o[] = '@prefix owl: <http://www.w3.org/2002/07/owl#> .';
        $o[] = '@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .';
        $o[] = '@prefix dct: <http://purl.org/dc/terms/> .';
        $o[] = '';
        $o[] = '# ---------- ontologi ----------';
        foreach (self::KELAS_RDF as $jenis => [$kelas, $super]) {
            $o[] = "$kelas a owl:Class ; rdfs:subClassOf $super ; rdfs:label ".self::lit(Katalog::KELAS[$jenis]).'@id .';
        }
        $o[] = 'mv:Kedekatan a owl:Class ; rdfs:label "Kedekatan dua tempat"@id .';
        foreach (Katalog::LABEL_RELASI as $p => $label) {
            $o[] = "mv:$p a owl:ObjectProperty ; rdfs:label ".self::lit($label).'@id .';
        }
        $o[] = 'mv:bagianDari a owl:TransitiveProperty .';
        $o[] = 'mv:dekatDengan a owl:SymmetricProperty .';
        $o[] = 'mv:hargaTiketMulai a owl:DatatypeProperty ; rdfs:range xsd:integer .';
        $o[] = 'mv:jarakMeter a owl:DatatypeProperty ; rdfs:range xsd:integer .';
        $o[] = '';
        $o[] = '# ---------- entitas ----------';
        foreach (Katalog::entitas() as $slug => $e) {
            $baris = ["res:$slug a ".self::KELAS_RDF[$e['jenis']][0], 'rdfs:label '.self::lit($e['nama']).'@id'];
            if (! empty($e['ringkas'])) {
                $baris[] = 'schema:description '.self::lit($e['ringkas']).'@id';
            }
            if (isset($e['lat'])) {
                $baris[] = 'geo:lat "'.$e['lat'].'"^^xsd:decimal';
                $baris[] = 'geo:long "'.$e['lng'].'"^^xsd:decimal';
            }
            if (! empty($e['tiket'])) {
                $baris[] = 'mv:hargaTiketMulai '.Katalog::hargaTermurah($e);
            }
            if (! empty($e['alamat'])) {
                $baris[] = 'schema:address '.self::lit($e['alamat'].', '.$e['kecamatan'].', Medan').'@id';
            }
            $o[] = implode(" ;\n    ", $baris).' .';
        }
        $o[] = '';
        $o[] = '# ---------- relasi (setiap relasi mencantumkan sumbernya lewat reifikasi) ----------';
        foreach (Katalog::relasi() as $i => $r) {
            $o[] = "res:{$r[0]} mv:{$r[1]} res:{$r[2]} .";
            if (isset($r['jarak_m'])) {
                $o[] = "res:dekat-$i a mv:Kedekatan ; mv:antara res:{$r[0]}, res:{$r[2]} ; mv:jarakMeter {$r['jarak_m']} .";
            }
            $o[] = "[] a rdf:Statement ; rdf:subject res:{$r[0]} ; rdf:predicate mv:{$r[1]} ; rdf:object res:{$r[2]} ; dct:source ".self::lit($r['sumber']).' .';
        }
        array_splice($o, 7, 0, ['@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .']);

        return implode("\n", $o)."\n";
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
