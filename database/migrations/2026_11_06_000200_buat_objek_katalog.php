<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * DB 1 (koneksi "katalog"): index, view, function, trigger, dan procedure.
 * Dipisah dari migrasi tabel supaya mudah dirujuk di laporan MSBD.
 */
return new class extends Migration
{
    protected $connection = 'katalog';

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            -- ---------- index ----------
            CREATE INDEX ix_tempat_kategori  ON tempat (id_kategori);
            CREATE INDEX ix_tempat_kecamatan ON tempat (id_kecamatan);
            CREATE INDEX ix_tempat_koordinat ON tempat (latitude, longitude);
            CREATE INDEX ix_relasi_subjek    ON relasi (subjek);
            CREATE INDEX ix_relasi_objek     ON relasi (objek);
            CREATE INDEX ix_entitas_jenis    ON entitas (jenis);

            -- ---------- function ----------
            -- jarak dua titik di permukaan bumi (rumus haversine), dalam meter
            CREATE OR REPLACE FUNCTION fn_jarak_meter(lat1 double precision, lng1 double precision,
                                                      lat2 double precision, lng2 double precision)
            RETURNS integer LANGUAGE sql IMMUTABLE AS $$
                SELECT round(2 * 6371000 * asin(sqrt(
                    power(sin(radians(lat2 - lat1) / 2), 2)
                    + cos(radians(lat1)) * cos(radians(lat2)) * power(sin(radians(lng2 - lng1) / 2), 2)
                )))::integer
            $$;

            -- tempat dalam radius tertentu dari sebuah titik, urut dari yang terdekat
            CREATE OR REPLACE FUNCTION fn_tempat_terdekat(p_lat double precision, p_lng double precision,
                                                          p_batas_m integer DEFAULT 1000)
            RETURNS TABLE (id_tempat integer, slug varchar, nama_tempat varchar, kategori varchar, jarak_m integer)
            LANGUAGE sql STABLE AS $$
                SELECT x.id_tempat, x.slug, x.nama_tempat, x.nama_kategori, x.jarak_m
                FROM (
                    SELECT t.id_tempat, t.slug, t.nama_tempat, k.nama_kategori,
                           fn_jarak_meter(p_lat, p_lng, t.latitude::float8, t.longitude::float8) AS jarak_m
                    FROM tempat t JOIN kategori k ON k.id_kategori = t.id_kategori
                ) x
                WHERE x.jarak_m <= p_batas_m
                ORDER BY x.jarak_m
            $$;

            -- apakah tempat buka pada jam tertentu (bawaan: jam sekarang di Medan)
            CREATE OR REPLACE FUNCTION fn_buka_pada(p_id_tempat integer,
                                                    p_jam time DEFAULT (now() AT TIME ZONE 'Asia/Jakarta')::time)
            RETURNS boolean LANGUAGE sql STABLE AS $$
                SELECT COALESCE(p_jam >= jam_buka AND p_jam < jam_tutup, false)
                FROM tempat WHERE id_tempat = p_id_tempat
            $$;

            -- ---------- view ----------
            -- semua simpul yang boleh muncul sebagai subjek/objek relasi
            CREATE OR REPLACE VIEW v_simpul AS
                SELECT slug, nama_tempat::varchar AS nama, 'tempat'::varchar AS jenis FROM tempat
                UNION ALL
                SELECT slug, nama::varchar, jenis::varchar FROM entitas
                UNION ALL
                SELECT slug, nama_kecamatan::varchar, 'wilayah'::varchar FROM kecamatan;

            -- tempat beserta kategori, kecamatan, dan harga tiket termurah
            CREATE OR REPLACE VIEW v_tempat_lengkap AS
                SELECT t.id_tempat, t.slug, t.nama_tempat, k.kode AS kode_kategori, k.nama_kategori,
                       c.nama_kecamatan, t.kelurahan, t.alamat, t.latitude, t.longitude,
                       t.keterangan_jam, t.jam_buka, t.jam_tutup,
                       h.harga_termurah, COALESCE(h.jumlah_jenis_tiket, 0) AS jumlah_jenis_tiket,
                       (h.harga_termurah IS NOT NULL) AS bertiket,
                       t.radius_checkin, t.durasi_singgah, t.image, t.wikidata, t.status
                FROM tempat t
                JOIN kategori k  ON k.id_kategori  = t.id_kategori
                JOIN kecamatan c ON c.id_kecamatan = t.id_kecamatan
                LEFT JOIN (
                    SELECT id_tempat, min(harga) AS harga_termurah, count(*) AS jumlah_jenis_tiket
                    FROM tiket GROUP BY id_tempat
                ) h ON h.id_tempat = t.id_tempat;

            -- relasi dalam bentuk kalimat yang bisa dibaca, lengkap dengan sumbernya
            CREATE OR REPLACE VIEW v_relasi_kalimat AS
                SELECT r.id_relasi, r.subjek, s.nama AS nama_subjek, r.predikat, p.label,
                       r.objek, o.nama AS nama_objek, r.jarak_m,
                       replace(replace(replace(p.pola_maju, '{a}', s.nama), '{b}', o.nama),
                               '{jarak}', COALESCE(r.jarak_m::text, '')) AS kalimat,
                       r.sumber
                FROM relasi r
                JOIN predikat p ON p.kode = r.predikat
                JOIN v_simpul s ON s.slug = r.subjek
                JOIN v_simpul o ON o.slug = r.objek;

            -- jumlah tempat per kategori
            CREATE OR REPLACE VIEW v_statistik_kategori AS
                SELECT k.kode, k.nama_kategori, count(DISTINCT t.id_tempat) AS jumlah_tempat,
                       count(DISTINCT tk.id_tempat) AS jumlah_bertiket
                FROM kategori k
                LEFT JOIN tempat t ON t.id_kategori = k.id_kategori
                LEFT JOIN tiket tk ON tk.id_tempat = t.id_tempat
                GROUP BY k.id_kategori, k.kode, k.nama_kategori;

            -- ---------- trigger ----------
            -- subjek/objek relasi harus ada; jarak "dekatDengan" selalu dihitung dari koordinat, tidak diketik
            CREATE OR REPLACE FUNCTION trg_relasi_cek_simpul() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                a tempat%ROWTYPE;
                b tempat%ROWTYPE;
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM v_simpul WHERE slug = NEW.subjek) THEN
                    RAISE EXCEPTION 'Subjek "%" tidak ada di tempat, entitas, maupun kecamatan', NEW.subjek;
                END IF;
                IF NOT EXISTS (SELECT 1 FROM v_simpul WHERE slug = NEW.objek) THEN
                    RAISE EXCEPTION 'Objek "%" tidak ada di tempat, entitas, maupun kecamatan', NEW.objek;
                END IF;
                IF NEW.predikat = 'dekatDengan' THEN
                    SELECT * INTO a FROM tempat WHERE slug = NEW.subjek;
                    SELECT * INTO b FROM tempat WHERE slug = NEW.objek;
                    IF a.id_tempat IS NULL OR b.id_tempat IS NULL THEN
                        RAISE EXCEPTION 'Relasi dekatDengan hanya boleh antara dua tempat';
                    END IF;
                    NEW.jarak_m := fn_jarak_meter(a.latitude::float8, a.longitude::float8,
                                                  b.latitude::float8, b.longitude::float8);
                END IF;
                RETURN NEW;
            END $$;

            CREATE TRIGGER relasi_cek_simpul
                BEFORE INSERT OR UPDATE ON relasi
                FOR EACH ROW EXECUTE FUNCTION trg_relasi_cek_simpul();

            -- integritas referensial buatan untuk slug: tidak boleh dihapus selama dipakai relasi,
            -- dan bila slug diganti, relasinya ikut diganti
            CREATE OR REPLACE FUNCTION trg_simpul_jaga_relasi() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    IF EXISTS (SELECT 1 FROM relasi WHERE subjek = OLD.slug OR objek = OLD.slug) THEN
                        RAISE EXCEPTION '"%" masih dipakai di tabel relasi; hapus relasinya dulu', OLD.slug;
                    END IF;
                    RETURN OLD;
                END IF;
                IF NEW.slug <> OLD.slug THEN
                    UPDATE relasi SET subjek = NEW.slug WHERE subjek = OLD.slug;
                    UPDATE relasi SET objek  = NEW.slug WHERE objek  = OLD.slug;
                END IF;
                RETURN NEW;
            END $$;

            CREATE TRIGGER tempat_jaga_hapus     BEFORE DELETE ON tempat    FOR EACH ROW EXECUTE FUNCTION trg_simpul_jaga_relasi();
            CREATE TRIGGER tempat_ikut_ganti     AFTER UPDATE OF slug ON tempat    FOR EACH ROW EXECUTE FUNCTION trg_simpul_jaga_relasi();
            CREATE TRIGGER entitas_jaga_hapus    BEFORE DELETE ON entitas   FOR EACH ROW EXECUTE FUNCTION trg_simpul_jaga_relasi();
            CREATE TRIGGER entitas_ikut_ganti    AFTER UPDATE OF slug ON entitas   FOR EACH ROW EXECUTE FUNCTION trg_simpul_jaga_relasi();
            CREATE TRIGGER kecamatan_jaga_hapus  BEFORE DELETE ON kecamatan FOR EACH ROW EXECUTE FUNCTION trg_simpul_jaga_relasi();
            CREATE TRIGGER kecamatan_ikut_ganti  AFTER UPDATE OF slug ON kecamatan FOR EACH ROW EXECUTE FUNCTION trg_simpul_jaga_relasi();

            -- updated_at tempat selalu mengikuti waktu perubahan
            CREATE OR REPLACE FUNCTION trg_set_updated_at() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                NEW.updated_at := now();
                RETURN NEW;
            END $$;

            CREATE TRIGGER tempat_updated_at BEFORE UPDATE ON tempat FOR EACH ROW EXECUTE FUNCTION trg_set_updated_at();

            -- ---------- procedure ----------
            -- buat relasi "dekatDengan" untuk setiap pasangan tempat yang jaraknya <= p_batas_m
            -- (pasangan yang sudah punya relasi dekat dilewati); jaraknya diisi trigger relasi_cek_simpul
            CREATE OR REPLACE PROCEDURE sp_hitung_relasi_dekat(p_batas_m integer DEFAULT 300)
            LANGUAGE plpgsql AS $$
            BEGIN
                INSERT INTO relasi (subjek, predikat, objek, sumber)
                SELECT a.slug, 'dekatDengan', b.slug, 'Dihitung dari koordinat'
                FROM tempat a
                JOIN tempat b ON a.id_tempat < b.id_tempat
                WHERE fn_jarak_meter(a.latitude::float8, a.longitude::float8,
                                     b.latitude::float8, b.longitude::float8) <= p_batas_m
                  AND NOT EXISTS (
                      SELECT 1 FROM relasi r
                      WHERE r.predikat = 'dekatDengan'
                        AND ((r.subjek = a.slug AND r.objek = b.slug) OR (r.subjek = b.slug AND r.objek = a.slug))
                  );
            END $$;
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP PROCEDURE IF EXISTS sp_hitung_relasi_dekat(integer);
            DROP TRIGGER IF EXISTS tempat_updated_at ON tempat;
            DROP TRIGGER IF EXISTS kecamatan_ikut_ganti ON kecamatan;
            DROP TRIGGER IF EXISTS kecamatan_jaga_hapus ON kecamatan;
            DROP TRIGGER IF EXISTS entitas_ikut_ganti ON entitas;
            DROP TRIGGER IF EXISTS entitas_jaga_hapus ON entitas;
            DROP TRIGGER IF EXISTS tempat_ikut_ganti ON tempat;
            DROP TRIGGER IF EXISTS tempat_jaga_hapus ON tempat;
            DROP TRIGGER IF EXISTS relasi_cek_simpul ON relasi;
            DROP FUNCTION IF EXISTS trg_set_updated_at();
            DROP FUNCTION IF EXISTS trg_simpul_jaga_relasi();
            DROP FUNCTION IF EXISTS trg_relasi_cek_simpul();
            DROP VIEW IF EXISTS v_statistik_kategori;
            DROP VIEW IF EXISTS v_relasi_kalimat;
            DROP VIEW IF EXISTS v_tempat_lengkap;
            DROP VIEW IF EXISTS v_simpul;
            DROP FUNCTION IF EXISTS fn_buka_pada(integer, time);
            DROP FUNCTION IF EXISTS fn_tempat_terdekat(double precision, double precision, integer);
            DROP FUNCTION IF EXISTS fn_jarak_meter(double precision, double precision, double precision, double precision);
            DROP INDEX IF EXISTS ix_entitas_jenis;
            DROP INDEX IF EXISTS ix_relasi_objek;
            DROP INDEX IF EXISTS ix_relasi_subjek;
            DROP INDEX IF EXISTS ix_tempat_koordinat;
            DROP INDEX IF EXISTS ix_tempat_kecamatan;
            DROP INDEX IF EXISTS ix_tempat_kategori;
            SQL);
    }
};
