<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * DB 2 (koneksi "transaksi"): tempat dirujuk lewat slug, bukan angka.
 *
 * Tempat wisata sekarang hanya ada di Database 3 (RDF), dan resource-nya dikenali lewat slug/URI
 * (https://medanvisit.neoverse.my.id/id/{slug}), tidak punya nomor. Kolom id_tempat (integer) diganti
 * tempat_slug (varchar), sama seperti users.tempat_slug di DB 1. Function, trigger, view, dan procedure
 * yang memakai kolom itu dibuat ulang. Ulasan juga menyimpan nama pengulas saat menulis, karena
 * DB 2 tidak bisa membaca tabel users di DB 1.
 */
return new class extends Migration
{
    protected $connection = 'transaksi';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            -- objek yang bergantung pada kolom id_tempat dilepas dulu
            DROP VIEW IF EXISTS v_penjualan_tiket;
            DROP TRIGGER IF EXISTS ulasan_ringkasan ON ulasan;
            DROP FUNCTION IF EXISTS trg_ulasan_ringkasan();
            DROP FUNCTION IF EXISTS fn_hitung_ringkasan_ulasan(integer);
            DROP PROCEDURE IF EXISTS sp_buat_pesanan(bigint, varchar, varchar, varchar, integer, date, varchar, jsonb, varchar);
