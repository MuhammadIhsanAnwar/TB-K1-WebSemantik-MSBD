# Data MedanVisit: PostgreSQL (MSBD) dan RDF (Web Semantik)

Satu sumber data, dua tujuan:

```
database/data/*.json  ← php artisan data:ekspor (dari app/Support/Katalog.php)
   ├─► php artisan migrate + db:seed ─► PostgreSQL DB 1 "katalog" + DB 2 "transaksi"
   └─► php artisan rdf:ekspor          ─► database/rdf/*.ttl ─► Apache Jena Fuseki (SPARQL)
```

Kalau data tempat berubah: ubah `Katalog.php` atau `app/Support/data/tempat-tambahan.php`, lalu jalankan `data:ekspor`, `rdf:ekspor`, dan `db:seed` lagi.

## Isi folder

| Folder / file | Isi |
|---|---|
| `data/*.json` | 9 file sumber: kategori, kecamatan, tempat, tiket, entitas, predikat, relasi, jalur, jalur_singgah |
| `migrations/2026_11_06_000100_buat_tabel_katalog.php` | tabel DB 1 |
| `migrations/2026_11_06_000200_buat_objek_katalog.php` | index, view, function, trigger, procedure DB 1 |
| `migrations/2026_11_06_000300_buat_tabel_transaksi.php` | tabel DB 2 |
| `migrations/2026_11_06_000400_buat_objek_transaksi.php` | index, view, function, trigger, procedure DB 2 |
| `migrations/2026_11_06_000500_buat_role_database.php` | role hak akses kedua database |
| `seeders/KatalogSeeder.php` | isi DB 1 dari `data/*.json` |
| `seeders/TransaksiSeeder.php` | akun uji coba dan contoh transaksi di DB 2 (data uji coba) |
| `rdf/medanvisit-ontologi.ttl` (`.owl`) | ontologi (TBox): kelas dan properti, RDFS/OWL 2 |
| `rdf/medanvisit-data.ttl` (`.jsonld`) | data (ABox), dibuat `rdf:ekspor` |
| `rdf/fuseki-medanvisit.ttl` | konfigurasi Fuseki: dataset `medanvisit`, hanya baca, reasoner OWL Micro |
| `rdf/query/*.rq` | 11 contoh query SPARQL (SELECT, property path, reifikasi, ASK, CONSTRUCT) |

## Server lokal (pengembangan)

Untuk sementara kedua database dan Fuseki berjalan di laptop; Supabase dipakai lagi saat deploy (baris Supabase di `.env` diberi awalan `# supabase:`).

| Layanan | Alamat | Menyalakan |
|---|---|---|
| PostgreSQL 17 portable | `127.0.0.1:5432`, user `postgres`, sandi `medanvisit_lokal`, database `medanvisit_katalog` dan `medanvisit_transaksi` | `C:\Users\ASUS\postgresql-17-portable\pgsql\bin\pg_ctl -D C:\Users\ASUS\postgresql-17-portable\data -l C:\Users\ASUS\postgresql-17-portable\postgres.log start` (ganti `start` dengan `stop` untuk mematikan) |
| Apache Jena Fuseki 5.1 | `http://localhost:3030`, dataset `medanvisit` | dari folder `Downloads\apache-jena-fuseki-5.1.0\apache-jena-fuseki-5.1.0`: `fuseki-server --config=C:\MSBD-WS\TB-K1-WebSemantik-MSBD\database\rdf\fuseki-medanvisit.ttl` |

Membuka database lewat DBeaver: koneksi PostgreSQL baru, host `localhost`, port `5432`, isi user dan sandi di atas.

## 1. PostgreSQL: dua database

| Koneksi | Variabel `.env` | Isi |
|---|---|---|
| `katalog` (DB 1) | `DB_*` | data master: kategori, kecamatan, tempat, fasilitas_tempat, cara_menuju, tiket, entitas, predikat, relasi, jalur, jalur_singgah |
| `transaksi` (DB 2) | `DB2_*` | users, usaha_mitra, menu_usaha, pesanan, pesanan_item, pembayaran, e_tiket, checkin, riwayat_poin, ulasan, ringkasan_ulasan, voucher, penukaran, rencana, rencana_minat, log_pencarian |

`id_tempat` di DB 2 merujuk ke DB 1. Foreign key tidak bisa melintasi database, jadi kecocokannya dijaga aplikasi.

```bash
php artisan migrate      # membuat tabel dan objek di kedua database
php artisan db:seed      # KatalogSeeder lalu TransaksiSeeder (bisa diulang)
```

Akun uji coba (sandi `rahasia123`): `rina@contoh.id` (wisatawan), `usaha@contoh.id` (mitra), `kurator@contoh.id` (kurator).

### Objek database

| Jenis | DB 1 katalog | DB 2 transaksi |
|---|---|---|
| View | `v_simpul`, `v_tempat_lengkap`, `v_relasi_kalimat`, `v_statistik_kategori` | `v_saldo_poin`, `v_penjualan_tiket`, `v_celah_konten` |
| Function | `fn_jarak_meter`, `fn_tempat_terdekat`, `fn_buka_pada` | `fn_kode_acak`, `fn_saldo_poin`, `fn_hitung_ringkasan_ulasan` |
| Trigger | `relasi_cek_simpul` (slug harus ada, jarak dihitung otomatis), `*_jaga_hapus` / `*_ikut_ganti` (integritas slug), `tempat_updated_at` | `pesanan_item_total`, `checkin_poin`, `ulasan_ringkasan`, `penukaran_cek`, `penukaran_poin` |
| Procedure | `sp_hitung_relasi_dekat` | `sp_buat_pesanan`, `sp_bayar_pesanan`, `sp_kedaluwarsakan_pesanan` |
| Role | `medanvisit_baca`, `medanvisit_kurator` | `medanvisit_aplikasi`, `medanvisit_laporan` |

Contoh di DBeaver atau psql:

```sql
-- DB 1
SELECT * FROM fn_tempat_terdekat(3.5752, 98.6840, 500);
SELECT kalimat, sumber FROM v_relasi_kalimat WHERE subjek = 'tjong-a-fie';
CALL sp_hitung_relasi_dekat(300);

-- DB 2
CALL sp_buat_pesanan(1, 'Rina', 'rina@contoh.id', '0812', 1, current_date + 1, 'qris',
                     '[{"jenis":"Dewasa","harga":10000,"jumlah":2}]', NULL);
CALL sp_bayar_pesanan('MV-XXXXXXXX', 'REF-1', NULL);
SELECT * FROM v_saldo_poin;
```

## 2. Web Semantik: standar W3C

RDF (model data), RDFS dan OWL 2 (ontologi), Turtle / JSON-LD / RDF-XML (format file), SPARQL 1.1 (query). Kosakata yang dipakai ulang: schema.org, WGS84 `geo:`, Dublin Core `dcterms:`, dan `owl:sameAs` ke Wikidata. Sumber setiap relasi disimpan lewat reifikasi `rdf:Statement` + `dcterms:source`.

Menjalankan Fuseki (Java 17+), dari folder Fuseki:

```bash
fuseki-server --config=<path repo>/database/rdf/fuseki-medanvisit.ttl
```

Buka `http://localhost:3030`, dataset `medanvisit`. Endpoint SPARQL: `http://localhost:3030/medanvisit/sparql` (isi `SPARQL_ENDPOINT` di `.env`). Halaman `/data` di web memakai endpoint ini; bila Fuseki mati, query contoh dijawab dari salinan lokal.

Menguji contoh query dari terminal:

```bash
curl http://localhost:3030/medanvisit/sparql -H "Accept: text/csv" --data-urlencode "query@database/rdf/query/05-benang-merah-property-path.rq"
```
