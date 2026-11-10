<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * DB 2 (koneksi "transaksi"): pesanan tiket, check-in, poin, ulasan, voucher, rencana, log pencarian.
 * Menggantikan penyimpanan sementara di App\Support\Penyimpanan (cache berkas).
 *
 * Kolom id_pengguna, id_tempat, dan kode_kategori merujuk ke DB 1 (katalog).
 * Foreign key tidak bisa melintasi database, jadi kecocokannya dijaga aplikasi.
 */
return new class extends Migration
{
    protected $connection = 'transaksi';

    public function up(): void
    {
        Schema::connection($this->connection)->create('usaha_mitra', function (Blueprint $t) {
            $t->increments('id_usaha');
            $t->foreignId('id_pengguna')->unique();
            $t->string('nama_usaha', 80);
            $t->string('jenis', 20);
            $t->string('alamat', 160);
            $t->string('jam', 80);
            $t->string('deskripsi', 400)->nullable();
            $t->timestampsTz();
        });

        Schema::connection($this->connection)->create('menu_usaha', function (Blueprint $t) {
            $t->increments('id_menu');
            $t->integer('id_usaha');
            $t->string('nama_menu', 60);
            $t->integer('harga');
            $t->foreign('id_usaha')->references('id_usaha')->on('usaha_mitra')->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('pesanan', function (Blueprint $t) {
            $t->bigIncrements('id_pesanan');
            $t->string('kode', 20)->unique();
            $t->foreignId('id_pengguna')->nullable();
            $t->string('nama_pemesan', 80);
            $t->string('email_pemesan', 120);
            $t->string('hp_pemesan', 20);
            $t->integer('id_tempat');
            $t->date('tanggal_kunjungan');
            $t->integer('total')->default(0);
            $t->string('metode', 10);
            $t->string('status', 12)->default('menunggu');
            $t->timestampTz('batas_bayar');
            $t->timestampsTz();
        });

        Schema::connection($this->connection)->create('pesanan_item', function (Blueprint $t) {
            $t->bigIncrements('id_item');
            $t->bigInteger('id_pesanan');
            $t->string('jenis_pengunjung', 60);
            $t->integer('harga');
            $t->smallInteger('jumlah');
            $t->integer('subtotal')->storedAs('harga * jumlah');
            $t->foreign('id_pesanan')->references('id_pesanan')->on('pesanan')->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('pembayaran', function (Blueprint $t) {
            $t->bigIncrements('id_pembayaran');
            $t->bigInteger('id_pesanan')->unique();
            $t->string('metode', 10);
            $t->integer('jumlah');
            $t->string('referensi', 40)->nullable();
            $t->timestampTz('dibayar_pada')->useCurrent();
            $t->foreign('id_pesanan')->references('id_pesanan')->on('pesanan')->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('e_tiket', function (Blueprint $t) {
            $t->bigIncrements('id_etiket');
            $t->bigInteger('id_pesanan')->unique();
            $t->string('kode_qr', 40)->unique();
            $t->timestampTz('diterbitkan_pada')->useCurrent();
            $t->timestampTz('dipakai_pada')->nullable();
            $t->foreign('id_pesanan')->references('id_pesanan')->on('pesanan')->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('checkin', function (Blueprint $t) {
            $t->bigIncrements('id_checkin');
            $t->foreignId('id_pengguna');
            $t->integer('id_tempat');
            $t->smallInteger('jarak_m');
            $t->date('tanggal')->default(DB::raw("((now() AT TIME ZONE 'Asia/Jakarta')::date)"));
            $t->timestampTz('waktu')->useCurrent();
            $t->unique(['id_pengguna', 'id_tempat', 'tanggal']);
        });

        Schema::connection($this->connection)->create('riwayat_poin', function (Blueprint $t) {
            $t->bigIncrements('id_riwayat');
            $t->foreignId('id_pengguna');
            $t->integer('poin');
            $t->string('sumber', 12);
            $t->bigInteger('id_sumber')->nullable();
            $t->string('keterangan', 120);
            $t->timestampTz('waktu')->useCurrent();
        });

        Schema::connection($this->connection)->create('ulasan', function (Blueprint $t) {
            $t->bigIncrements('id_ulasan');
            $t->foreignId('id_pengguna');
            $t->integer('id_tempat');
            $t->smallInteger('bintang');
            $t->string('teks', 600);
            $t->string('status', 10)->default('menunggu');
            $t->timestampsTz();
        });

        // diisi trigger dari tabel ulasan; hanya ulasan berstatus "tayang" yang dihitung
        Schema::connection($this->connection)->create('ringkasan_ulasan', function (Blueprint $t) {
            $t->integer('id_tempat')->primary();
            $t->integer('jumlah_ulasan')->default(0);
            $t->decimal('rata_rata_bintang', 3, 2)->nullable();
            $t->timestampTz('diperbarui_pada')->useCurrent();
        });

        Schema::connection($this->connection)->create('voucher', function (Blueprint $t) {
            $t->increments('id_voucher');
            $t->integer('id_usaha');
            $t->string('nama', 60);
            $t->smallInteger('poin');
            $t->smallInteger('stok');
            $t->smallInteger('stok_awal');
            $t->date('berlaku_sampai');
            $t->timestampsTz();
            $t->foreign('id_usaha')->references('id_usaha')->on('usaha_mitra')->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('penukaran', function (Blueprint $t) {
            $t->bigIncrements('id_penukaran');
            $t->foreignId('id_pengguna');
            $t->integer('id_voucher');
            $t->smallInteger('poin');
            $t->string('kode', 12)->unique();
            $t->boolean('dipakai')->default(false);
            $t->timestampTz('waktu')->useCurrent();
            $t->timestampTz('dipakai_pada')->nullable();
            $t->foreign('id_voucher')->references('id_voucher')->on('voucher')->restrictOnDelete();
        });

        Schema::connection($this->connection)->create('rencana', function (Blueprint $t) {
            $t->bigIncrements('id_rencana');
            $t->string('kode', 12)->unique();
            $t->foreignId('id_pengguna');
            $t->string('judul', 80);
            $t->smallInteger('durasi_jam');
            $t->boolean('moda_jalan')->default(true);
            $t->boolean('moda_becak')->default(false);
            $t->integer('id_tempat_mulai');
            $t->timestampsTz();
        });

        Schema::connection($this->connection)->create('rencana_minat', function (Blueprint $t) {
            $t->bigInteger('id_rencana');
            $t->string('kode_kategori', 20);
            $t->primary(['id_rencana', 'kode_kategori']);
            $t->foreign('id_rencana')->references('id_rencana')->on('rencana')->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('log_pencarian', function (Blueprint $t) {
            $t->bigIncrements('id_log');
            $t->string('kata_kunci', 120);
            $t->smallInteger('jumlah_hasil');
            $t->timestampTz('waktu')->useCurrent();
        });

        DB::connection($this->connection)->unprepared(<<<'SQL'
            ALTER TABLE usaha_mitra  ADD CONSTRAINT ck_usaha_jenis     CHECK (jenis IN ('kuliner', 'oleh-oleh', 'kerajinan', 'penginapan'));
            ALTER TABLE menu_usaha   ADD CONSTRAINT ck_menu_harga      CHECK (harga >= 0);
            ALTER TABLE pesanan
                ADD CONSTRAINT ck_pesanan_metode CHECK (metode IN ('qris', 'va', 'ewallet', 'kartu')),
                ADD CONSTRAINT ck_pesanan_status CHECK (status IN ('menunggu', 'lunas', 'dibatalkan', 'kedaluwarsa')),
                ADD CONSTRAINT ck_pesanan_tempat CHECK (id_tempat > 0),
                ADD CONSTRAINT ck_pesanan_total  CHECK (total >= 0);
            ALTER TABLE pesanan_item
                ADD CONSTRAINT ck_item_harga  CHECK (harga >= 0),
                ADD CONSTRAINT ck_item_jumlah CHECK (jumlah BETWEEN 1 AND 20);
            ALTER TABLE pembayaran   ADD CONSTRAINT ck_bayar_jumlah    CHECK (jumlah >= 0);
            ALTER TABLE checkin      ADD CONSTRAINT ck_checkin_jarak   CHECK (jarak_m >= 0);
            ALTER TABLE riwayat_poin ADD CONSTRAINT ck_poin_sumber     CHECK (sumber IN ('checkin', 'bonus', 'penukaran'));
            ALTER TABLE ulasan
                ADD CONSTRAINT ck_ulasan_bintang CHECK (bintang BETWEEN 1 AND 5),
                ADD CONSTRAINT ck_ulasan_teks    CHECK (char_length(teks) >= 10),
                ADD CONSTRAINT ck_ulasan_status  CHECK (status IN ('menunggu', 'tayang', 'ditolak'));
            ALTER TABLE voucher
                ADD CONSTRAINT ck_voucher_poin CHECK (poin BETWEEN 10 AND 500),
                ADD CONSTRAINT ck_voucher_stok CHECK (stok >= 0 AND stok <= stok_awal);
            ALTER TABLE rencana      ADD CONSTRAINT ck_rencana_durasi  CHECK (durasi_jam IN (2, 4, 6, 8)),
                                     ADD CONSTRAINT ck_rencana_moda    CHECK (moda_jalan OR moda_becak);
            SQL);
    }

    public function down(): void
    {
        foreach (['log_pencarian', 'rencana_minat', 'rencana', 'penukaran', 'voucher', 'ringkasan_ulasan', 'ulasan', 'riwayat_poin', 'checkin', 'e_tiket', 'pembayaran', 'pesanan_item', 'pesanan', 'menu_usaha', 'usaha_mitra'] as $tabel) {
            Schema::connection($this->connection)->dropIfExists($tabel);
        }
    }
};
