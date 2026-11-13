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
            ALTER TABLE pesanan DROP CONSTRAINT IF EXISTS ck_pesanan_tempat;

            -- kolom: angka menjadi slug (index dan unique ikut pindah ke kolom baru)
            ALTER TABLE pesanan          RENAME COLUMN id_tempat TO tempat_slug;
            ALTER TABLE pesanan          ALTER COLUMN tempat_slug TYPE varchar(80) USING tempat_slug::text;
            ALTER TABLE checkin          RENAME COLUMN id_tempat TO tempat_slug;
            ALTER TABLE checkin          ALTER COLUMN tempat_slug TYPE varchar(80) USING tempat_slug::text;
            ALTER TABLE ulasan           RENAME COLUMN id_tempat TO tempat_slug;
            ALTER TABLE ulasan           ALTER COLUMN tempat_slug TYPE varchar(80) USING tempat_slug::text;
            ALTER TABLE ringkasan_ulasan RENAME COLUMN id_tempat TO tempat_slug;
            ALTER TABLE ringkasan_ulasan ALTER COLUMN tempat_slug TYPE varchar(80) USING tempat_slug::text;
            ALTER TABLE rencana          RENAME COLUMN id_tempat_mulai TO tempat_mulai_slug;
            ALTER TABLE rencana          ALTER COLUMN tempat_mulai_slug TYPE varchar(80) USING tempat_mulai_slug::text;

            ALTER TABLE checkin RENAME CONSTRAINT checkin_id_pengguna_id_tempat_tanggal_unique TO checkin_id_pengguna_tempat_slug_tanggal_unique;
            ALTER TABLE pesanan ADD CONSTRAINT ck_pesanan_tempat CHECK (tempat_slug ~ '^[a-z0-9-]+$');

            -- nama dan email pengulas disimpan saat menulis ulasan (DB 2 tidak bisa membaca users di DB 1)
            ALTER TABLE ulasan ADD COLUMN nama_pengguna varchar(80) NOT NULL DEFAULT 'Wisatawan',
                               ADD COLUMN email_pengguna varchar(120);

            -- ---------- dibuat ulang dengan slug ----------
            CREATE OR REPLACE VIEW v_penjualan_tiket AS
                SELECT p.tempat_slug, date_trunc('month', p.tanggal_kunjungan)::date AS bulan,
                       count(*) AS jumlah_pesanan,
                       sum(i.jumlah_orang) AS jumlah_pengunjung,
                       sum(p.total) AS pendapatan
                FROM pesanan p
                JOIN (SELECT id_pesanan, sum(jumlah) AS jumlah_orang FROM pesanan_item GROUP BY id_pesanan) i
                  ON i.id_pesanan = p.id_pesanan
                WHERE p.status = 'lunas'
                GROUP BY p.tempat_slug, date_trunc('month', p.tanggal_kunjungan);

            CREATE OR REPLACE FUNCTION trg_checkin_poin() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                INSERT INTO riwayat_poin (id_pengguna, poin, sumber, id_sumber, keterangan)
                VALUES (NEW.id_pengguna, 10, 'checkin', NEW.id_checkin, 'Check-in di ' || NEW.tempat_slug);
                RETURN NULL;
            END $$;

            CREATE OR REPLACE FUNCTION fn_hitung_ringkasan_ulasan(p_tempat_slug varchar)
            RETURNS void LANGUAGE sql AS $$
                INSERT INTO ringkasan_ulasan (tempat_slug, jumlah_ulasan, rata_rata_bintang, diperbarui_pada)
                SELECT p_tempat_slug, count(*), round(avg(bintang), 2), now()
                FROM ulasan WHERE tempat_slug = p_tempat_slug AND status = 'tayang'
                ON CONFLICT (tempat_slug) DO UPDATE
                    SET jumlah_ulasan = EXCLUDED.jumlah_ulasan,
                        rata_rata_bintang = EXCLUDED.rata_rata_bintang,
                        diperbarui_pada = EXCLUDED.diperbarui_pada;
            $$;

            CREATE OR REPLACE FUNCTION trg_ulasan_ringkasan() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP IN ('UPDATE', 'DELETE') THEN
                    PERFORM fn_hitung_ringkasan_ulasan(OLD.tempat_slug);
                END IF;
                IF TG_OP IN ('INSERT', 'UPDATE') THEN
                    PERFORM fn_hitung_ringkasan_ulasan(NEW.tempat_slug);
                END IF;
                RETURN NULL;
            END $$;

            CREATE TRIGGER ulasan_ringkasan
                AFTER INSERT OR UPDATE OF status, bintang, tempat_slug OR DELETE ON ulasan
                FOR EACH ROW EXECUTE FUNCTION trg_ulasan_ringkasan();

            -- buat pesanan beserta item tiketnya dalam satu transaksi; batas bayar 15 menit
            -- p_item: [{"jenis": "Dewasa", "harga": 10000, "jumlah": 2}, ...]
            -- (harga diambil aplikasi dari Database 3, karena DB 2 tidak bisa membaca RDF)
            CREATE OR REPLACE PROCEDURE sp_buat_pesanan(
                p_id_pengguna bigint, p_nama varchar, p_email varchar, p_hp varchar,
                p_tempat_slug varchar, p_tanggal date, p_metode varchar, p_item jsonb,
                INOUT p_kode varchar DEFAULT NULL)
            LANGUAGE plpgsql AS $$
            DECLARE
                v_id bigint;
            BEGIN
                IF jsonb_array_length(p_item) = 0 THEN
                    RAISE EXCEPTION 'Pesanan harus berisi minimal satu tiket';
                END IF;
                IF p_tanggal < (now() AT TIME ZONE 'Asia/Jakarta')::date THEN
                    RAISE EXCEPTION 'Tanggal kunjungan tidak boleh di masa lalu';
                END IF;

                p_kode := fn_kode_acak('MV-', 8);
                INSERT INTO pesanan (kode, id_pengguna, nama_pemesan, email_pemesan, hp_pemesan,
                                     tempat_slug, tanggal_kunjungan, metode, batas_bayar, created_at, updated_at)
                VALUES (p_kode, p_id_pengguna, p_nama, p_email, p_hp,
                        p_tempat_slug, p_tanggal, p_metode, now() + interval '15 minutes', now(), now())
                RETURNING id_pesanan INTO v_id;

                INSERT INTO pesanan_item (id_pesanan, jenis_pengunjung, harga, jumlah)
                SELECT v_id, x->>'jenis', (x->>'harga')::integer, (x->>'jumlah')::smallint
                FROM jsonb_array_elements(p_item) AS x
                WHERE (x->>'jumlah')::integer > 0;
            END $$;
            SQL);
    }

    public function down(): void
    {
        $db = DB::connection($this->connection);
        foreach (['pesanan', 'checkin', 'ulasan', 'ringkasan_ulasan', 'rencana'] as $tabel) {
            if ($db->table($tabel)->exists()) {
                throw new RuntimeException("Tabel $tabel sudah berisi data: slug tempat tidak bisa diubah kembali menjadi angka.");
            }
        }

        $db->unprepared(<<<'SQL'
            DROP VIEW IF EXISTS v_penjualan_tiket;
            DROP TRIGGER IF EXISTS ulasan_ringkasan ON ulasan;
            DROP FUNCTION IF EXISTS trg_ulasan_ringkasan();
            DROP FUNCTION IF EXISTS fn_hitung_ringkasan_ulasan(varchar);
            DROP PROCEDURE IF EXISTS sp_buat_pesanan(bigint, varchar, varchar, varchar, varchar, date, varchar, jsonb, varchar);
            ALTER TABLE pesanan DROP CONSTRAINT IF EXISTS ck_pesanan_tempat;
            ALTER TABLE ulasan DROP COLUMN IF EXISTS nama_pengguna, DROP COLUMN IF EXISTS email_pengguna;

            ALTER TABLE checkin RENAME CONSTRAINT checkin_id_pengguna_tempat_slug_tanggal_unique TO checkin_id_pengguna_id_tempat_tanggal_unique;
            ALTER TABLE pesanan          ALTER COLUMN tempat_slug TYPE integer USING NULL;
            ALTER TABLE pesanan          RENAME COLUMN tempat_slug TO id_tempat;
            ALTER TABLE checkin          ALTER COLUMN tempat_slug TYPE integer USING NULL;
            ALTER TABLE checkin          RENAME COLUMN tempat_slug TO id_tempat;
            ALTER TABLE ulasan           ALTER COLUMN tempat_slug TYPE integer USING NULL;
            ALTER TABLE ulasan           RENAME COLUMN tempat_slug TO id_tempat;
            ALTER TABLE ringkasan_ulasan ALTER COLUMN tempat_slug TYPE integer USING NULL;
            ALTER TABLE ringkasan_ulasan RENAME COLUMN tempat_slug TO id_tempat;
            ALTER TABLE rencana          ALTER COLUMN tempat_mulai_slug TYPE integer USING NULL;
            ALTER TABLE rencana          RENAME COLUMN tempat_mulai_slug TO id_tempat_mulai;
            ALTER TABLE pesanan ADD CONSTRAINT ck_pesanan_tempat CHECK (id_tempat > 0);

            CREATE OR REPLACE VIEW v_penjualan_tiket AS
                SELECT p.id_tempat, date_trunc('month', p.tanggal_kunjungan)::date AS bulan,
                       count(*) AS jumlah_pesanan, sum(i.jumlah_orang) AS jumlah_pengunjung, sum(p.total) AS pendapatan
                FROM pesanan p
                JOIN (SELECT id_pesanan, sum(jumlah) AS jumlah_orang FROM pesanan_item GROUP BY id_pesanan) i
                  ON i.id_pesanan = p.id_pesanan
                WHERE p.status = 'lunas'
                GROUP BY p.id_tempat, date_trunc('month', p.tanggal_kunjungan);

            CREATE OR REPLACE FUNCTION trg_checkin_poin() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                INSERT INTO riwayat_poin (id_pengguna, poin, sumber, id_sumber, keterangan)
                VALUES (NEW.id_pengguna, 10, 'checkin', NEW.id_checkin, 'Check-in di tempat #' || NEW.id_tempat);
                RETURN NULL;
            END $$;

