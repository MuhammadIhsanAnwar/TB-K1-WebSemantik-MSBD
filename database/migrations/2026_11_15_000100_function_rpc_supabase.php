<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * DB 2 (koneksi "transaksi"): function untuk Supabase REST API (PostgREST).
 *
 * PostgREST hanya memanggil FUNCTION lewat /rest/v1/rpc, bukan PROCEDURE (CALL). Function di sini
 * membungkus procedure yang sudah ada supaya aplikasi tetap memakai aturan bisnis di database
 * tanpa koneksi PostgreSQL langsung ke server hosting.
 *
 * Jalankan migration ini dari komputer yang bisa menjangkau PostgreSQL Supabase (lihat database/README.md).
 */
return new class extends Migration
{
    protected $connection = 'transaksi';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            -- buat pesanan: kembalikan kode pesanan yang dibuat (sp_buat_pesanan menulis p_kode)
            CREATE OR REPLACE FUNCTION sp_buat_pesanan(
                p_id_pengguna bigint, p_nama varchar, p_email varchar, p_hp varchar,
                p_tempat_slug varchar, p_tanggal date, p_metode varchar, p_item jsonb)
            RETURNS varchar LANGUAGE plpgsql AS $$
            DECLARE
                v_kode varchar;
            BEGIN
                CALL sp_buat_pesanan(p_id_pengguna, p_nama, p_email, p_hp, p_tempat_slug, p_tanggal, p_metode, p_item, v_kode);
                RETURN v_kode;
            END $$;

            -- bayar pesanan: kembalikan kode QR e-tiket, NULL kalau pesanan kedaluwarsa
            CREATE OR REPLACE FUNCTION sp_bayar_pesanan(p_kode varchar, p_referensi varchar DEFAULT NULL)
            RETURNS varchar LANGUAGE plpgsql AS $$
            DECLARE
                v_qr varchar;
            BEGIN
                CALL sp_bayar_pesanan(p_kode, p_referensi, v_qr);
                RETURN v_qr;
            END $$;

            -- simpan rencana + minatnya dalam satu transaksi; kembalikan baris rencana
            CREATE OR REPLACE FUNCTION fn_simpan_rencana(
                p_kode varchar, p_id_pengguna bigint, p_judul varchar, p_durasi_jam smallint,
                p_moda_jalan boolean, p_moda_becak boolean, p_tempat_mulai_slug varchar, p_minat text[])
            RETURNS SETOF rencana LANGUAGE plpgsql AS $$
            DECLARE
                v_id bigint;
            BEGIN
                INSERT INTO rencana (kode, id_pengguna, judul, durasi_jam, moda_jalan, moda_becak,
                                     tempat_mulai_slug, created_at, updated_at)
                VALUES (p_kode, p_id_pengguna, p_judul, p_durasi_jam, p_moda_jalan, p_moda_becak,
                        p_tempat_mulai_slug, now(), now())
                RETURNING id_rencana INTO v_id;

                INSERT INTO rencana_minat (id_rencana, kode_kategori)
                SELECT v_id, k FROM unnest(p_minat) AS k;

                RETURN QUERY SELECT * FROM rencana WHERE id_rencana = v_id;
            END $$;

            -- PostgREST memanggil dengan role anon/authenticated; akses dibatasi ke tabel DB 2 saja
            GRANT USAGE ON SCHEMA public TO anon, authenticated;
            GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO anon, authenticated;
            GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO anon, authenticated;
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            DROP FUNCTION IF EXISTS fn_simpan_rencana(varchar, bigint, varchar, smallint, boolean, boolean, varchar, text[]);
            DROP FUNCTION IF EXISTS sp_bayar_pesanan(varchar, varchar);
            DROP FUNCTION IF EXISTS sp_buat_pesanan(bigint, varchar, varchar, varchar, varchar, date, varchar, jsonb);
            SQL);
    }
};
