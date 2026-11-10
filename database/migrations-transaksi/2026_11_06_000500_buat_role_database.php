<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Role (kelompok hak akses) untuk kedua database. Role dibuat NOLOGIN: akun login sungguhan
 * cukup diberi salah satu role ini, misalnya GRANT medanvisit_baca TO akun_web.
 *
 *   medanvisit_baca     DB 1  membaca akun, konfigurasi Laravel, dan katalog (web publik, laporan)
 *   medanvisit_kurator  DB 1  membaca dan mengubah isi katalog (tempat, tiket, entitas, relasi, jalur)
 *   medanvisit_aplikasi DB 2  membaca dan menulis transaksi, menjalankan procedure
 *   medanvisit_laporan  DB 2  hanya membaca view ringkasan (poin, penjualan, celah konten)
 */
return new class extends Migration
{
    private const ROLE = ['medanvisit_baca', 'medanvisit_kurator', 'medanvisit_aplikasi', 'medanvisit_laporan'];

    private function buatRole(string $koneksi): void
    {
        $buat = collect(self::ROLE)->map(fn ($r) => "IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '$r') THEN CREATE ROLE $r NOLOGIN; END IF;")->implode("\n");
        DB::connection($koneksi)->unprepared("DO \$\$ BEGIN\n$buat\nEND \$\$;");
    }

    public function up(): void
    {
        // pada server lokal kedua database berada di cluster yang sama, jadi role cukup dibuat sekali;
        // pada Supabase tiap database adalah proyek terpisah, jadi role dibuat di keduanya
        $this->buatRole('katalog');
        $this->buatRole('transaksi');

        DB::connection('katalog')->unprepared(<<<'SQL'
            GRANT USAGE ON SCHEMA public TO medanvisit_baca, medanvisit_kurator;
            GRANT SELECT ON ALL TABLES IN SCHEMA public TO medanvisit_baca, medanvisit_kurator;
            GRANT EXECUTE ON FUNCTION fn_jarak_meter(double precision, double precision, double precision, double precision),
                                      fn_tempat_terdekat(double precision, double precision, integer),
                                      fn_buka_pada(integer, time)
                  TO medanvisit_baca, medanvisit_kurator;
            GRANT INSERT, UPDATE, DELETE ON tempat, fasilitas_tempat, cara_menuju, tiket, entitas, relasi, jalur, jalur_singgah
                  TO medanvisit_kurator;
            GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO medanvisit_kurator;
            GRANT EXECUTE ON PROCEDURE sp_hitung_relasi_dekat(integer) TO medanvisit_kurator;
            SQL);

        DB::connection('transaksi')->unprepared(<<<'SQL'
            GRANT USAGE ON SCHEMA public TO medanvisit_aplikasi, medanvisit_laporan;
            GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA public TO medanvisit_aplikasi;
            GRANT DELETE ON menu_usaha, rencana, rencana_minat TO medanvisit_aplikasi;
            GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO medanvisit_aplikasi;
            GRANT EXECUTE ON PROCEDURE sp_buat_pesanan(bigint, varchar, varchar, varchar, integer, date, varchar, jsonb, varchar),
                                       sp_bayar_pesanan(varchar, varchar, varchar),
                                       sp_kedaluwarsakan_pesanan()
                  TO medanvisit_aplikasi;
            GRANT SELECT ON v_saldo_poin, v_penjualan_tiket, v_celah_konten, ringkasan_ulasan TO medanvisit_laporan;
            SQL);
    }

    public function down(): void
    {
        foreach (['katalog', 'transaksi'] as $koneksi) {
            $role = implode(', ', self::ROLE);
            DB::connection($koneksi)->unprepared(<<<SQL
                REVOKE ALL ON ALL TABLES IN SCHEMA public FROM $role;
                REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM $role;
                REVOKE ALL ON ALL FUNCTIONS IN SCHEMA public FROM $role;
                REVOKE ALL ON ALL PROCEDURES IN SCHEMA public FROM $role;
                REVOKE USAGE ON SCHEMA public FROM $role;
                SQL);
        }
        foreach (['katalog', 'transaksi'] as $koneksi) {
            foreach (self::ROLE as $r) {
                DB::connection($koneksi)->unprepared("DROP ROLE IF EXISTS $r");
            }
        }
    }
};
