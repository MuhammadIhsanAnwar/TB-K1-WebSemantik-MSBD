<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Support\Katalog;

/**
 * Mengisi DB 1 dengan akun uji coba dan DB 2 (koneksi "transaksi") dengan contoh transaksi.
 * Semua transaksi di sini DATA UJI COBA, bukan transaksi sungguhan. Pesanan, pembayaran, check-in,
 * dan penukaran sengaja dibuat lewat procedure dan trigger supaya objek database ikut teruji.
 * Seeder ini bisa diulang: isi lama dikosongkan dulu.
 */
class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection('transaksi');
        $akun = DB::connection('katalog');
        // id_tempat adalah ID deterministik dari sumber RDF, bukan foreign key PostgreSQL.
        $idTempat = function (string $slug, int $cadangan): int {
            $id = array_search($slug, array_keys(Katalog::tempat()), true);

            return $id === false ? $cadangan : $id + 1;
        };

        $db->statement('TRUNCATE log_pencarian, rencana_minat, rencana, penukaran, voucher, ringkasan_ulasan, ulasan, riwayat_poin, checkin, e_tiket, pembayaran, pesanan_item, pesanan, menu_usaha, usaha_mitra RESTART IDENTITY CASCADE');
        $akun->table('users')->truncate();

        // akun uji coba yang sama dengan yang dipakai saat pengujian prototipe (sandi: rahasia123)
        $sandi = Hash::make('rahasia123');
        $sekarang = now();
        foreach ([['Rina', 'rina@contoh.id', 'wisatawan'], ['Usaha Contoh', 'usaha@contoh.id', 'mitra'], ['Kurator MedanVisit', 'kurator@contoh.id', 'kurator']] as [$nama, $email, $peran]) {
            $akun->table('users')->insert(['name' => $nama, 'email' => $email, 'password' => $sandi, 'peran' => $peran, 'created_at' => $sekarang, 'updated_at' => $sekarang]);
        }
        $rina = $akun->table('users')->where('email', 'rina@contoh.id')->value('id');
        $mitra = $akun->table('users')->where('email', 'usaha@contoh.id')->value('id');

        $usaha = $db->table('usaha_mitra')->insertGetId([
            'id_pengguna' => $mitra, 'nama_usaha' => 'Kedai Contoh MedanVisit', 'jenis' => 'kuliner',
            'alamat' => 'Alamat contoh, Medan', 'jam' => 'Setiap hari, 08.00–21.00', 'deskripsi' => 'Usaha uji coba untuk menguji fitur mitra.',
            'created_at' => $sekarang, 'updated_at' => $sekarang,
        ], 'id_usaha');
        $db->table('menu_usaha')->insert([
            ['id_usaha' => $usaha, 'nama_menu' => 'Bika ambon potong', 'harga' => 8000],
            ['id_usaha' => $usaha, 'nama_menu' => 'Teh manis', 'harga' => 5000],
        ]);
        $voucher = $db->table('voucher')->insertGetId([
            'id_usaha' => $usaha, 'nama' => 'Diskon 10% menu kedai', 'poin' => 30, 'stok' => 20, 'stok_awal' => 20,
            'berlaku_sampai' => now()->addMonths(3)->toDateString(), 'created_at' => $sekarang, 'updated_at' => $sekarang,
        ], 'id_voucher');

        // pesanan dibuat dan dibayar lewat procedure; total dihitung trigger pesanan_item_total
        $istana = $idTempat('istana-maimun', 1);
        $item = json_encode([['jenis' => 'Dewasa', 'harga' => 10000, 'jumlah' => 2], ['jenis' => 'Anak', 'harga' => 5000, 'jumlah' => 1]]);
        $kode = $db->selectOne('CALL sp_buat_pesanan(?, ?, ?, ?, ?, ?, ?, ?::jsonb, NULL)', [
            $rina, 'Rina', 'rina@contoh.id', '081200000000', $istana, now()->addDay()->toDateString(), 'qris', $item,
        ])->p_kode;
        $db->statement('CALL sp_bayar_pesanan(?, ?, NULL)', [$kode, 'UJI-COBA']);

        // pesanan kedua dibiarkan menunggu pembayaran
        $db->selectOne('CALL sp_buat_pesanan(?, ?, ?, ?, ?, ?, ?, ?::jsonb, NULL)', [
            $rina, 'Rina', 'rina@contoh.id', '081200000000', $idTempat('tjong-a-fie-mansion', 3), now()->addDays(2)->toDateString(), 'va',
            json_encode([['jenis' => 'Umum, termasuk pemandu', 'harga' => 35000, 'jumlah' => 1]]),
        ]);

        // dua check-in di tempat bersejarah: trigger checkin_poin memberi 10 poin per check-in,
        // lalu bonus "Pemburu Sejarah" dicatat aplikasi (aturannya butuh kategori dari DB 1)
        $db->table('checkin')->insert([
            ['id_pengguna' => $rina, 'id_tempat' => $istana, 'jarak_m' => 42],
            ['id_pengguna' => $rina, 'id_tempat' => $idTempat('tjong-a-fie-mansion', 3), 'jarak_m' => 18],
        ]);
        $db->table('riwayat_poin')->insert(['id_pengguna' => $rina, 'poin' => 25, 'sumber' => 'bonus', 'keterangan' => 'Badge Pemburu Sejarah: 2 tempat bersejarah']);

        // penukaran voucher: trigger mengecek saldo dan stok, mengurangi stok, dan mencatat poin keluar
        $db->table('penukaran')->insert(['id_pengguna' => $rina, 'id_voucher' => $voucher, 'poin' => 0, 'kode' => null]);

        // ulasan: hanya yang "tayang" dihitung ke ringkasan_ulasan oleh trigger
        $db->table('ulasan')->insert([
            ['id_pengguna' => $rina, 'id_tempat' => $istana, 'bintang' => 5, 'teks' => 'Ulasan uji coba: istananya terawat dan pemandunya ramah.', 'status' => 'tayang', 'created_at' => $sekarang, 'updated_at' => $sekarang],
            ['id_pengguna' => $rina, 'id_tempat' => $idTempat('tjong-a-fie-mansion', 3), 'bintang' => 4, 'teks' => 'Ulasan uji coba yang masih menunggu moderasi.', 'status' => 'menunggu', 'created_at' => $sekarang, 'updated_at' => $sekarang],
        ]);

        $rencana = $db->table('rencana')->insertGetId([
            'kode' => 'mdn-uji1', 'id_pengguna' => $rina, 'judul' => 'Setengah hari di kota lama', 'durasi_jam' => 4,
            'moda_jalan' => true, 'moda_becak' => true, 'id_tempat_mulai' => $istana, 'created_at' => $sekarang, 'updated_at' => $sekarang,
        ], 'id_rencana');
        $db->table('rencana_minat')->insert([['id_rencana' => $rencana, 'kode_kategori' => 'sejarah'], ['id_rencana' => $rencana, 'kode_kategori' => 'kuliner']]);

        $db->table('log_pencarian')->insert([
            ['kata_kunci' => 'istana maimun', 'jumlah_hasil' => 3],
            ['kata_kunci' => 'kebun binatang', 'jumlah_hasil' => 0],
            ['kata_kunci' => 'kebun binatang', 'jumlah_hasil' => 0],
        ]);

        $saldo = $db->selectOne('SELECT fn_saldo_poin(?) AS saldo', [$rina])->saldo;
        $this->command?->info("Transaksi: 3 akun uji coba, pesanan $kode lunas, saldo poin Rina $saldo.");
    }
}
