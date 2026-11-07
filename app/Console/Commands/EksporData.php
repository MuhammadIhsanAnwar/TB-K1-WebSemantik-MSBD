<?php

namespace App\Console\Commands;

use App\Support\Katalog;
use Illuminate\Console\Command;

/**
 * Ekspor isi App\Support\Katalog ke database/data/*.json, satu file per tabel.
 * File ini sumber tunggal untuk seeder PostgreSQL (MSBD) dan ekspor RDF (Web Semantik).
 */
class EksporData extends Command
{
    protected $signature = 'data:ekspor';

    protected $description = 'Ekspor katalog MedanVisit ke database/data/*.json';

    public function handle(): int
    {
        $jam = fn (?int $h) => $h === null ? null : sprintf('%02d:00', $h);
        $jamTutup = fn (?int $h) => $h === null ? null : ($h >= 24 ? '23:59' : sprintf('%02d:00', $h));

        $kategori = [];
        $idKategori = [];
        foreach (array_keys(Katalog::KATEGORI) as $i => $kode) {
            $idKategori[$kode] = $i + 1;
            $kategori[] = ['id_kategori' => $i + 1, 'kode' => $kode, 'nama_kategori' => Katalog::KATEGORI[$kode]];
        }

        $kecamatan = [];
        $idKecamatan = [];
        foreach (array_values(Katalog::jenis('wilayah')) as $i => $w) {
            $idKecamatan[$w['nama']] = $i + 1;
            $kecamatan[] = ['id_kecamatan' => $i + 1, 'slug' => $w['slug'], 'nama_kecamatan' => $w['nama'], 'kota' => 'Medan'];
        }

        $tempat = [];
        $tiket = [];
        $idTempat = [];
        foreach (array_values(Katalog::tempat()) as $i => $t) {
            $id = $i + 1;
            $idTempat[$t['slug']] = $id;
            $tempat[] = [
                'id_tempat' => $id,
                'id_kategori' => $idKategori[$t['kategori']],
                'id_kecamatan' => $idKecamatan[$t['kecamatan']],
                'slug' => $t['slug'],
                'nama_tempat' => $t['nama'],
                'ringkas' => $t['ringkas'],
                'deskripsi' => implode("\n\n", $t['cerita']),
                'kelurahan' => $t['kelurahan'] ?: null,
                'alamat' => $t['alamat'],
                'latitude' => $t['lat'],
                'longitude' => $t['lng'],
                'koordinat_perkiraan' => ! empty($t['koordinat_perkiraan']),
                'keterangan_jam' => $t['jam'],
                'opening_time' => isset($t['jam_buka']) ? $jam($t['jam_buka'][0]) : null,
                'closing_time' => isset($t['jam_buka']) ? $jamTutup($t['jam_buka'][1]) : null,
                'jam_contoh' => ! empty($t['jam_contoh']),
                'harga_contoh' => ! empty($t['harga_contoh']),
                'bertiket' => ! empty($t['tiket']),
                'fasilitas' => $t['fasilitas'],
                'cara_menuju' => $t['cara'],
                'radius_checkin' => $t['radius_checkin'],
                'durasi_singgah' => $t['singgah'],
                'image' => $t['foto'] ?: null,
                'wikidata' => $t['wikidata'] ?? null,
                'status' => 'aktif',
                'source_url' => $t['sumber'] ?? null,
            ];
            foreach ($t['tiket'] ?? [] as $jenis => $harga) {
                $tiket[] = ['id_tiket' => count($tiket) + 1, 'id_tempat' => $id, 'jenis_pengunjung' => $jenis, 'harga' => $harga];
            }
        }

        $entitas = [];
        foreach (Katalog::entitas() as $e) {
            if (in_array($e['jenis'], ['tempat', 'wilayah'], true)) {
                continue;
            }
            $entitas[] = [
                'id_entitas' => count($entitas) + 1,
                'slug' => $e['slug'],
                'jenis' => $e['jenis'],
                'nama' => $e['nama'],
                'ringkas' => $e['ringkas'] ?? null,
                'masa' => $e['masa'] ?? null,
                'image' => $e['foto'] ?? null,
            ];
        }

        // jenis relasi beserta label dan pola kalimatnya, dua arah
        $predikat = [];
        foreach (Katalog::LABEL_RELASI as $kode => $label) {
            $predikat[] = [
                'kode' => $kode,
                'label' => $label,
                'label_balik' => Katalog::LABEL_BALIK[$kode],
                'pola_maju' => Katalog::KALIMAT[$kode][0],
                'pola_balik' => Katalog::KALIMAT[$kode][1],
                'simetris' => in_array($kode, ['dekatDengan', 'berkaitanDengan'], true),
            ];
        }

        // relasi memakai slug: subjek/objek bisa tempat, entitas, atau kecamatan, seperti tripel RDF
        $relasi = [];
        foreach (Katalog::relasi() as $r) {
            $relasi[] = [
                'id_relasi' => count($relasi) + 1,
                'subjek' => $r[0],
                'predikat' => $r[1],
                'objek' => $r[2],
                'kalimat' => Katalog::kalimat($r, $r[0]),
                'jarak_m' => $r['jarak_m'] ?? null,
                'sumber' => $r['sumber'],
            ];
        }

        $jalur = [];
        $jalurSinggah = [];
        foreach (array_values(Katalog::jalurTematik()) as $i => $j) {
            $jalur[] = ['id_jalur' => $i + 1, 'slug' => $j['slug'], 'nama_jalur' => $j['nama'], 'ringkas' => $j['ringkas'], 'image' => $j['foto'] ?? null];
            foreach ($j['singgah'] as $urut => $s) {
                $jalurSinggah[] = ['id_jalur' => $i + 1, 'id_tempat' => $idTempat[$s], 'urutan' => $urut + 1];
            }
        }

        $keluar = database_path('data');
        if (! is_dir($keluar)) {
            mkdir($keluar, 0777, true);
        }
        $opsi = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $tabel = compact('kategori', 'kecamatan', 'tempat', 'tiket', 'entitas', 'predikat', 'relasi', 'jalur') + ['jalur_singgah' => $jalurSinggah];
        foreach ($tabel as $nama => $baris) {
            file_put_contents("$keluar/$nama.json", json_encode($baris, $opsi)."\n");
            $this->line(str_pad("$nama.json", 20).count($baris).' baris');
        }

        return self::SUCCESS;
    }
}
