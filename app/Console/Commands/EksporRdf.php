<?php

namespace App\Console\Commands;

use App\Support\Semantik;
use Illuminate\Console\Command;

/**
 * Ekspor data MedanVisit (ABox) ke RDF Turtle, dari database/data/*.json: sumber yang sama dengan
 * seeder PostgreSQL, supaya isi tugas Web Semantik dan MSBD tidak pernah berbeda.
 * Kelas dan properti (TBox) ada di database/rdf/medanvisit-ontologi.ttl.
 */
class EksporRdf extends Command
{
    protected $signature = 'rdf:ekspor';

    protected $description = 'Ekspor database/data/*.json ke database/rdf/medanvisit-data.ttl';

    private const KELAS_KATEGORI = [
        'sejarah' => 'mv:SitusSejarah', 'museum' => 'mv:Museum', 'religi' => 'mv:RumahIbadah', 'kuliner' => 'mv:TempatKuliner',
        'ruang-publik' => 'mv:RuangPublik', 'belanja' => 'mv:PusatBelanja', 'rekreasi-air' => 'mv:RekreasiAir',
    ];

    private const KELAS_ENTITAS = [
        'tokoh' => 'mv:Tokoh', 'kerajaan' => 'mv:Kerajaan', 'peristiwa' => 'mv:Peristiwa', 'budaya' => 'mv:Budaya',
        'kuliner' => 'mv:Kuliner', 'transportasi' => 'mv:ModaTransportasi',
    ];

    private function baca(string $nama): array
    {
        return json_decode(file_get_contents(database_path("data/$nama.json")), true, flags: JSON_THROW_ON_ERROR);
    }

    /** literal Turtle dengan escape, opsional bertag bahasa atau bertipe */
    private function lit(string $s, string $akhiran = '@id'): string
    {
        return '"'.strtr($s, ['\\' => '\\\\', '"' => '\\"', "\n" => '\\n', "\r" => '', "\t" => '\\t']).'"'.$akhiran;
    }

    private function iri(string $url): string
    {
        return '<'.str_replace(['>', ' '], ['%3E', '%20'], $url).'>';
    }

    /** sumber relasi/tempat: tautan menjadi IRI, selain itu literal biasa */
    private function sumber(string $s): string
    {
        return str_starts_with($s, 'http') ? $this->iri($s) : $this->lit($s, '');
    }

    /** satu subjek dengan daftar [predikat, objek], ditulis dalam blok Turtle */
    private function blok(string $subjek, array $po): string
    {
        return $subjek.' '.implode(" ;\n    ", array_map(fn ($x) => $x[0].' '.$x[1], $po))." .\n";
    }

    public function handle(): int
    {
        $basis = Semantik::BASIS;
        $kategori = array_column($this->baca('kategori'), 'kode', 'id_kategori');
        $kecamatan = array_column($this->baca('kecamatan'), 'slug', 'id_kecamatan');
        $tiket = collect($this->baca('tiket'))->groupBy('id_tempat');
        $tempat = $this->baca('tempat');
        $slugTempat = array_column($tempat, 'slug', 'id_tempat');

        $o = [];
        $o[] = "# Data MedanVisit (ABox), dibuat otomatis oleh `php artisan rdf:ekspor` dari database/data/*.json.\n# Jangan diedit langsung: ubah datanya, lalu jalankan ulang data:ekspor dan rdf:ekspor.\n";
        foreach ([
            'mv' => $basis.'ontologi#', 'res' => $basis.'id/', 'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#', 'owl' => 'http://www.w3.org/2002/07/owl#',
            'xsd' => 'http://www.w3.org/2001/XMLSchema#', 'schema' => 'https://schema.org/',
            'geo' => 'http://www.w3.org/2003/01/geo/wgs84_pos#', 'dcterms' => 'http://purl.org/dc/terms/',
            'wd' => 'http://www.wikidata.org/entity/',
        ] as $p => $ns) {
            $o[] = "@prefix $p: <$ns> .";
        }
        $o[] = '';

        $o[] = "# ---------- kota dan kecamatan ----------\n";
        $o[] = $this->blok('res:kota-medan', [['a', 'schema:City'], ['schema:name', $this->lit('Medan')], ['owl:sameAs', 'wd:Q5972']]);
        foreach ($this->baca('kecamatan') as $k) {
            $o[] = $this->blok("res:{$k['slug']}", [
                ['a', 'mv:Kecamatan'], ['rdfs:label', $this->lit('Kecamatan '.$k['nama_kecamatan'])],
                ['schema:name', $this->lit($k['nama_kecamatan'])], ['schema:containedInPlace', 'res:kota-medan'],
            ]);
        }

        $o[] = "# ---------- tempat wisata ----------\n";
        foreach ($tempat as $t) {
            $po = [
                ['a', self::KELAS_KATEGORI[$kategori[$t['id_kategori']]]],
                ['rdfs:label', $this->lit($t['nama_tempat'])],
                ['schema:name', $this->lit($t['nama_tempat'])],
                ['schema:description', $this->lit($t['ringkas'])],
                ['mv:cerita', $this->lit($t['deskripsi'])],
                ['schema:address', '[ a schema:PostalAddress ; schema:streetAddress '.$this->lit($t['alamat'], '').' ; schema:addressLocality "Medan" ; schema:addressRegion "Sumatera Utara" ; schema:addressCountry "ID" ]'],
                ['geo:lat', $this->lit((string) $t['latitude'], '^^xsd:decimal')],
                ['geo:long', $this->lit((string) $t['longitude'], '^^xsd:decimal')],
                ['mv:koordinatPerkiraan', $t['koordinat_perkiraan'] ? 'true' : 'false'],
                ['mv:keteranganJam', $this->lit($t['keterangan_jam'], '')],
                ['mv:jamContoh', $t['jam_contoh'] ? 'true' : 'false'],
                ['mv:radiusCheckin', (string) $t['radius_checkin']],
                ['mv:durasiSinggah', (string) $t['durasi_singgah']],
                ['schema:isAccessibleForFree', $t['bertiket'] ? 'false' : 'true'],
            ];
            if ($t['kelurahan']) {
                $po[] = ['mv:kelurahan', $this->lit($t['kelurahan'], '')];
            }
            if ($t['opening_time']) {
                $po[] = ['mv:jamBuka', $this->lit($t['opening_time'].':00', '^^xsd:time')];
                $po[] = ['mv:jamTutup', $this->lit($t['closing_time'].':00', '^^xsd:time')];
            }
            foreach ($t['fasilitas'] as $f) {
                $po[] = ['mv:fasilitas', $this->lit($f)];
            }
            foreach ($t['cara_menuju'] as $c) {
                $po[] = ['mv:caraMenuju', $this->lit($c)];
            }
            if ($t['image']) {
                $po[] = ['schema:image', $this->iri($basis.'images/foto/'.$t['image'])];
            }
            if ($t['wikidata']) {
                $po[] = ['owl:sameAs', 'wd:'.$t['wikidata']];
            }
            if ($t['source_url']) {
                $po[] = ['dcterms:source', $this->sumber($t['source_url'])];
            }
            if ($t['bertiket']) {
                $harga = $tiket[$t['id_tempat']];
                $po[] = ['mv:hargaTiketMulai', (string) $harga->min('harga')];
                $po[] = ['mv:hargaContoh', $t['harga_contoh'] ? 'true' : 'false'];
                foreach ($harga as $h) {
                    $po[] = ['schema:offers', '[ a schema:Offer ; schema:name '.$this->lit($h['jenis_pengunjung']).' ; schema:price '.$h['harga'].' ; schema:priceCurrency "IDR" ]'];
                }
            }
            $o[] = $this->blok("res:{$t['slug']}", $po);
        }

        $o[] = "# ---------- tokoh, kerajaan, peristiwa, budaya, kuliner, transportasi ----------\n";
        foreach ($this->baca('entitas') as $e) {
            $po = [['a', self::KELAS_ENTITAS[$e['jenis']]], ['rdfs:label', $this->lit($e['nama'])], ['schema:name', $this->lit($e['nama'])]];
            if ($e['ringkas']) {
                $po[] = ['schema:description', $this->lit($e['ringkas'])];
            }
            if ($e['masa']) {
                $po[] = ['mv:masa', $this->lit($e['masa'], '')];
            }
            if ($e['image']) {
                $po[] = ['schema:image', $this->iri($basis.'images/foto/'.$e['image'])];
            }
            $o[] = $this->blok("res:{$e['slug']}", $po);
        }

        // setiap relasi ditulis sebagai tripel biasa, lalu direifikasi (rdf:Statement) untuk menyimpan sumber dan kalimatnya
        $o[] = "# ---------- relasi, beserta sumbernya lewat reifikasi rdf:Statement ----------\n";
        foreach ($this->baca('relasi') as $r) {
            $o[] = "res:{$r['subjek']} mv:{$r['predikat']} res:{$r['objek']} .";
            $o[] = $this->blok("res:relasi-{$r['id_relasi']}", [
                ['a', 'rdf:Statement'], ['rdf:subject', "res:{$r['subjek']}"], ['rdf:predicate', "mv:{$r['predikat']}"],
                ['rdf:object', "res:{$r['objek']}"], ['mv:kalimat', $this->lit($r['kalimat'])], ['dcterms:source', $this->sumber($r['sumber'])],
            ]);
            if ($r['jarak_m'] !== null) {
                $o[] = $this->blok("res:kedekatan-{$r['id_relasi']}", [
                    ['a', 'mv:Kedekatan'], ['mv:antara', "res:{$r['subjek']}, res:{$r['objek']}"], ['mv:jarakMeter', (string) $r['jarak_m']],
                ]);
            }
        }

        $o[] = "# ---------- jalur tematik ----------\n";
        $singgah = collect($this->baca('jalur_singgah'))->groupBy('id_jalur');
        foreach ($this->baca('jalur') as $j) {
            $po = [['a', 'mv:JalurTematik'], ['schema:name', $this->lit($j['nama_jalur'])], ['schema:description', $this->lit($j['ringkas'])]];
            if ($j['image']) {
                $po[] = ['schema:image', $this->iri($basis.'images/foto/'.$j['image'])];
            }
            $titik = [];
            foreach ($singgah[$j['id_jalur']] ?? [] as $s) {
                $id = "res:{$j['slug']}-singgah-{$s['urutan']}";
                $po[] = ['mv:persinggahan', $id];
                $titik[] = $this->blok($id, [['a', 'mv:Persinggahan'], ['mv:urutan', $this->lit((string) $s['urutan'], '^^xsd:positiveInteger')], ['mv:tempatSinggah', 'res:'.$slugTempat[$s['id_tempat']]]]);
            }
            $o[] = $this->blok("res:{$j['slug']}", $po);
            array_push($o, ...$titik);
        }

        $tujuan = database_path('rdf/medanvisit-data.ttl');
        if (! is_dir(dirname($tujuan))) {
            mkdir(dirname($tujuan), 0777, true);
        }
        file_put_contents($tujuan, implode("\n", $o)."\n");
        $this->info('Tertulis '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $tujuan));

        return self::SUCCESS;
    }
}
