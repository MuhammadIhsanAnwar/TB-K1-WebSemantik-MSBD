<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * DB 1 (koneksi "katalog"): data master MedanVisit, diisi dari database/data/*.json oleh KatalogSeeder.
 * Fasilitas dan cara menuju dipisah ke tabel sendiri (bukan array di kolom) supaya memenuhi 1NF.
 * Relasi memakai slug karena subjek/objeknya bisa tempat, entitas, atau kecamatan (tripel ala RDF);
 * keberadaan slug dijaga trigger di migrasi objek katalog.
 */
return new class extends Migration
{
    protected $connection = 'katalog';

    public function up(): void
    {
        Schema::connection($this->connection)->create('kategori', function (Blueprint $t) {
            $t->smallIncrements('id_kategori');
            $t->string('kode', 20)->unique();
            $t->string('nama_kategori', 60);
        });

        Schema::connection($this->connection)->create('kecamatan', function (Blueprint $t) {
            $t->smallIncrements('id_kecamatan');
            $t->string('slug', 60)->unique();
            $t->string('nama_kecamatan', 60)->unique();
            $t->string('kota', 40)->default('Medan');
        });

        Schema::connection($this->connection)->create('tempat', function (Blueprint $t) {
            $t->increments('id_tempat');
            $t->smallInteger('id_kategori');
            $t->smallInteger('id_kecamatan');
            $t->string('slug', 80)->unique();
            $t->string('nama_tempat', 120);
            $t->string('ringkas', 300);
            $t->text('deskripsi');
            $t->string('kelurahan', 60)->nullable();
            $t->string('alamat', 200);
            $t->decimal('latitude', 9, 6);
            $t->decimal('longitude', 9, 6);
            $t->boolean('koordinat_perkiraan')->default(false);
            $t->string('keterangan_jam', 160);
            $t->time('jam_buka')->nullable();
            $t->time('jam_tutup')->nullable();
            $t->boolean('jam_contoh')->default(true);
            $t->boolean('harga_contoh')->default(false);
            $t->smallInteger('radius_checkin')->default(100);
            $t->smallInteger('durasi_singgah')->default(45);
            $t->string('image', 160)->nullable();
            $t->string('wikidata', 20)->nullable();
            $t->string('status', 12)->default('aktif');
            $t->text('source_url')->nullable();
            $t->timestampsTz();

            $t->foreign('id_kategori')->references('id_kategori')->on('kategori')->restrictOnDelete();
            $t->foreign('id_kecamatan')->references('id_kecamatan')->on('kecamatan')->restrictOnDelete();
        });

        Schema::connection($this->connection)->create('fasilitas_tempat', function (Blueprint $t) {
            $t->increments('id_fasilitas');
            $t->integer('id_tempat');
            $t->smallInteger('urutan');
            $t->string('keterangan', 160);
            $t->unique(['id_tempat', 'urutan']);
            $t->foreign('id_tempat')->references('id_tempat')->on('tempat')->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('cara_menuju', function (Blueprint $t) {
            $t->increments('id_cara');
            $t->integer('id_tempat');
            $t->smallInteger('urutan');
            $t->string('keterangan', 200);
            $t->unique(['id_tempat', 'urutan']);
            $t->foreign('id_tempat')->references('id_tempat')->on('tempat')->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('tiket', function (Blueprint $t) {
            $t->increments('id_tiket');
            $t->integer('id_tempat');
            $t->string('jenis_pengunjung', 60);
            $t->integer('harga');
            $t->unique(['id_tempat', 'jenis_pengunjung']);
            $t->foreign('id_tempat')->references('id_tempat')->on('tempat')->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('entitas', function (Blueprint $t) {
            $t->increments('id_entitas');
            $t->string('slug', 80)->unique();
            $t->string('jenis', 20);
            $t->string('nama', 120);
            $t->string('ringkas', 300)->nullable();
            $t->string('masa', 60)->nullable();
            $t->string('image', 160)->nullable();
        });

        Schema::connection($this->connection)->create('predikat', function (Blueprint $t) {
            $t->string('kode', 30)->primary();
            $t->string('label', 40);
            $t->string('label_balik', 40);
            $t->string('pola_maju', 160);
            $t->string('pola_balik', 160);
            $t->boolean('simetris')->default(false);
        });

        Schema::connection($this->connection)->create('relasi', function (Blueprint $t) {
            $t->increments('id_relasi');
            $t->string('subjek', 80);
            $t->string('predikat', 30);
            $t->string('objek', 80);
            $t->integer('jarak_m')->nullable();
            $t->text('sumber');
            $t->unique(['subjek', 'predikat', 'objek']);
            $t->foreign('predikat')->references('kode')->on('predikat')->restrictOnDelete();
        });

        Schema::connection($this->connection)->create('jalur', function (Blueprint $t) {
            $t->smallIncrements('id_jalur');
            $t->string('slug', 80)->unique();
            $t->string('nama_jalur', 120);
            $t->string('ringkas', 300);
            $t->string('image', 160)->nullable();
        });

        Schema::connection($this->connection)->create('jalur_singgah', function (Blueprint $t) {
            $t->smallInteger('id_jalur');
            $t->integer('id_tempat');
            $t->smallInteger('urutan');
            $t->primary(['id_jalur', 'urutan']);
            $t->unique(['id_jalur', 'id_tempat']);
            $t->foreign('id_jalur')->references('id_jalur')->on('jalur')->cascadeOnDelete();
            $t->foreign('id_tempat')->references('id_tempat')->on('tempat')->restrictOnDelete();
        });

        // aturan nilai yang tidak bisa ditulis lewat Blueprint
        DB::connection($this->connection)->unprepared(<<<'SQL'
            ALTER TABLE tempat
                ADD CONSTRAINT ck_tempat_latitude  CHECK (latitude  BETWEEN -90  AND 90),
                ADD CONSTRAINT ck_tempat_longitude CHECK (longitude BETWEEN -180 AND 180),
                ADD CONSTRAINT ck_tempat_radius    CHECK (radius_checkin BETWEEN 30 AND 300),
                ADD CONSTRAINT ck_tempat_singgah   CHECK (durasi_singgah BETWEEN 5 AND 480),
                ADD CONSTRAINT ck_tempat_status    CHECK (status IN ('aktif', 'sebagian', 'tutup')),
                ADD CONSTRAINT ck_tempat_jam       CHECK (jam_buka IS NULL OR jam_tutup IS NULL OR jam_tutup > jam_buka);
            ALTER TABLE tiket   ADD CONSTRAINT ck_tiket_harga   CHECK (harga >= 0);
            ALTER TABLE entitas ADD CONSTRAINT ck_entitas_jenis CHECK (jenis IN ('tokoh', 'kerajaan', 'peristiwa', 'budaya', 'kuliner', 'transportasi'));
            ALTER TABLE relasi  ADD CONSTRAINT ck_relasi_beda   CHECK (subjek <> objek);
            ALTER TABLE jalur_singgah ADD CONSTRAINT ck_jalur_urutan CHECK (urutan >= 1);
            SQL);
    }

    public function down(): void
    {
        foreach (['jalur_singgah', 'jalur', 'relasi', 'predikat', 'entitas', 'tiket', 'cara_menuju', 'fasilitas_tempat', 'tempat', 'kecamatan', 'kategori'] as $tabel) {
            Schema::connection($this->connection)->dropIfExists($tabel);
        }
    }
};
