<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi DB 1 (koneksi "katalog") dari database/data/*.json.
 * JSON dibuat ulang dengan `php artisan data:ekspor`. Seeder ini bisa diulang: isi lama dikosongkan dulu.
 */
class KatalogSeeder extends Seeder
{
    private function baca(string $nama): array
    {
        return json_decode(file_get_contents(database_path("data/$nama.json")), true, flags: JSON_THROW_ON_ERROR);
    }

    public function run(): void
    {
        $db = DB::connection('katalog');
        $sekarang = now();

        $db->transaction(function () use ($db, $sekarang) {
            $db->statement('TRUNCATE jalur_singgah, jalur, relasi, predikat, entitas, tiket, cara_menuju, fasilitas_tempat, tempat, kecamatan, kategori RESTART IDENTITY CASCADE');

            $db->table('kategori')->insert($this->baca('kategori'));
            $db->table('kecamatan')->insert($this->baca('kecamatan'));

            $tempat = $this->baca('tempat');
            $db->table('tempat')->insert(array_map(fn ($t) => [
                'id_tempat' => $t['id_tempat'],
                'id_kategori' => $t['id_kategori'],
                'id_kecamatan' => $t['id_kecamatan'],
                'slug' => $t['slug'],
                'nama_tempat' => $t['nama_tempat'],
                'ringkas' => $t['ringkas'],
                'deskripsi' => $t['deskripsi'],
                'kelurahan' => $t['kelurahan'],
                'alamat' => $t['alamat'],
                'latitude' => $t['latitude'],
                'longitude' => $t['longitude'],
                'koordinat_perkiraan' => $t['koordinat_perkiraan'],
                'keterangan_jam' => $t['keterangan_jam'],
                'jam_buka' => $t['opening_time'],
                'jam_tutup' => $t['closing_time'],
                'jam_contoh' => $t['jam_contoh'],
                'harga_contoh' => $t['harga_contoh'],
                'radius_checkin' => $t['radius_checkin'],
                'durasi_singgah' => $t['durasi_singgah'],
                'image' => $t['image'],
                'wikidata' => $t['wikidata'],
                'status' => $t['status'],
                'source_url' => $t['source_url'],
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ], $tempat));

            // array fasilitas dan cara menuju dipecah menjadi baris (1NF)
            $fasilitas = [];
            $cara = [];
            foreach ($tempat as $t) {
                foreach ($t['fasilitas'] as $i => $f) {
                    $fasilitas[] = ['id_tempat' => $t['id_tempat'], 'urutan' => $i + 1, 'keterangan' => $f];
                }
                foreach ($t['cara_menuju'] as $i => $c) {
                    $cara[] = ['id_tempat' => $t['id_tempat'], 'urutan' => $i + 1, 'keterangan' => $c];
                }
            }
            $db->table('fasilitas_tempat')->insert($fasilitas);
            $db->table('cara_menuju')->insert($cara);

            $db->table('tiket')->insert($this->baca('tiket'));
            $db->table('entitas')->insert($this->baca('entitas'));
            $db->table('predikat')->insert($this->baca('predikat'));

            // kolom "kalimat" tidak disimpan: dibentuk view v_relasi_kalimat dari pola predikat.
            // jarak_m relasi dekatDengan dihitung ulang oleh trigger relasi_cek_simpul.
            $db->table('relasi')->insert(array_map(fn ($r) => [
                'id_relasi' => $r['id_relasi'],
                'subjek' => $r['subjek'],
                'predikat' => $r['predikat'],
                'objek' => $r['objek'],
                'jarak_m' => $r['jarak_m'],
                'sumber' => $r['sumber'],
            ], $this->baca('relasi')));

            $db->table('jalur')->insert($this->baca('jalur'));
            $db->table('jalur_singgah')->insert($this->baca('jalur_singgah'));

            // id diisi manual dari JSON, jadi sequence disetel ke nilai terbesar supaya baris baru tidak bentrok
            foreach (['kategori' => 'id_kategori', 'kecamatan' => 'id_kecamatan', 'tempat' => 'id_tempat', 'fasilitas_tempat' => 'id_fasilitas',
                'cara_menuju' => 'id_cara', 'tiket' => 'id_tiket', 'entitas' => 'id_entitas', 'relasi' => 'id_relasi', 'jalur' => 'id_jalur'] as $tabel => $kolom) {
                $db->statement("SELECT setval(pg_get_serial_sequence('$tabel', '$kolom'), GREATEST((SELECT max($kolom) FROM $tabel), 1))");
            }
        });

        $this->command?->info(sprintf(
            'Katalog: %d tempat, %d entitas, %d relasi.',
            $db->table('tempat')->count(), $db->table('entitas')->count(), $db->table('relasi')->count(),
        ));
    }
}
