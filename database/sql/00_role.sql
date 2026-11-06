-- Role hak akses MedanVisit; jalankan sebelum 01_medanvisit_katalog.sql dan 02_medanvisit_transaksi.sql,
-- karena kedua dump berisi GRANT ke role-role ini. Sama dengan migrasi 2026_11_06_000500_buat_role_database.
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'medanvisit_baca')     THEN CREATE ROLE medanvisit_baca NOLOGIN; END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'medanvisit_kurator')  THEN CREATE ROLE medanvisit_kurator NOLOGIN; END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'medanvisit_aplikasi') THEN CREATE ROLE medanvisit_aplikasi NOLOGIN; END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'medanvisit_laporan')  THEN CREATE ROLE medanvisit_laporan NOLOGIN; END IF;
END $$;
