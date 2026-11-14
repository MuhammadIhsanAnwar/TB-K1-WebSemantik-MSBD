# Koneksi Database lewat Supabase REST API

Hosting (cPanel) tidak bisa menjangkau port PostgreSQL Supabase (5432/6543), jadi aplikasi
**tidak lagi memakai string koneksi PDO** untuk DB 1 dan DB 2. Semua akses data lewat HTTPS
ke Supabase REST API (PostgREST).

| Jalur | Protokol | Dipakai untuk |
|---|---|---|
| DB 1 `SUPABASE_DB1_*` | HTTPS `/rest/v1` | users, token verifikasi, reset password |
| DB 2 `SUPABASE_DB2_*` | HTTPS `/rest/v1` + `/rest/v1/rpc` | pesanan, ulasan, rencana, log pencarian |
| DB 3 `SPARQL_*` | HTTPS SPARQL | data wisata (RDF) |

## Yang berubah

1. `app/Support/SupabaseApi.php` — klien REST untuk kedua database: baca/tulis tabel,
   nested select (`pesanan?select=*,pembayaran(...),e_tiket(...)`), pagination, dan RPC.
2. `app/Support/Penyimpanan.php` — semua akses DB 2 dipindah dari `DB::connection('transaksi')`
   ke `SupabaseApi('transaksi')`. Tidak ada lagi PDO di jalur runtime.
3. `database/migrations/2026_11_15_000100_function_rpc_supabase.php` — function pembungkus
   karena PostgREST hanya memanggil `FUNCTION` lewat `/rest/v1/rpc`, bukan `CALL` procedure:
   - `sp_buat_pesanan(...)` → mengembalikan `kode` pesanan
   - `sp_bayar_pesanan(kode, referensi)` → mengembalikan `kode_qr`
   - `fn_simpan_rencana(...)` → menyimpan rencana + minat dalam satu transaksi
   - `GRANT` untuk role `anon`/`authenticated` pada tabel dan function DB 2

## Urutan deploy

```bash
# 1. dari komputer yang bisa menjangkau PostgreSQL Supabase (bukan server hosting)
php artisan migrate --database=katalog     # DB 1 + migration DB 2 (lihat database/README.md)
php artisan rdf:kirim                      # DB 3

# 2. di server hosting
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

Migration tetap butuh koneksi PostgreSQL langsung. Kalau server hosting tidak bisa,
jalankan migration dari laptop atau lewat Supabase SQL Editor (lihat `database/README.md`).

## Kunci di `.env`

```
SUPABASE_DB1_URL=https://<ref-db1>.supabase.co
SUPABASE_DB1_PUBLISHABLE_KEY=sb_publishable_...
SUPABASE_DB1_SECRET_KEY=sb_secret_...

SUPABASE_DB2_URL=https://<ref-db2>.supabase.co
SUPABASE_DB2_PUBLISHABLE_KEY=sb_publishable_...
SUPABASE_DB2_SECRET_KEY=sb_secret_...
```

`DB_*` dan `DB2_*` hanya dipakai saat `php artisan migrate` dari mesin pengembang.
Jangan diisi di server hosting — tidak dipakai lagi oleh aplikasi.

## Catatan

- Row Level Security tabel DB 2 tidak diaktifkan; akses REST memakai secret key di server.
  Jangan pernah memakai secret key di sisi browser.
- Kalau project Supabase paused, REST mengembalikan 503. Halaman yang memakai `aman: true`
  tetap terbuka dengan data kosong dan menulis peringatan ke log.
