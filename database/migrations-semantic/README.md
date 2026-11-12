# Arsip Migration Katalog Lama

Folder ini **tidak digunakan oleh Laravel**. Isinya adalah migration katalog
PostgreSQL lama yang hanya disimpan sebagai arsip:

- `2026_11_06_000100_buat_tabel_katalog.php`
- `2026_11_06_000200_buat_objek_katalog.php`

## Alur Database 3 yang aktif

Data tempat wisata, kategori, tiket, entitas, relasi, dan jalur sekarang dikirim
ke AllegroGraph melalui migration yang disimpan di folder ini:

`database/migrations-semantic/2026_11_11_000200_kirim_rdf_ke_database3.php`

Migration tersebut:

1. Membaca `database/rdf/medanvisit-ontologi.ttl`.
2. Membaca `database/rdf/medanvisit-data.ttl`.
3. Mengubah deklarasi `@prefix` menjadi `PREFIX`.
4. Mengirim isi RDF ke endpoint SPARQL Database 3 menggunakan `INSERT DATA`.
5. Menggunakan autentikasi dari `SPARQL_USERNAME` dan `SPARQL_PASSWORD`.

## Menjalankan migration RDF

Laravel hanya memuat file migration dari `database/migrations`. Karena itu,
pindahkan file migration RDF ke folder aktif terlebih dahulu:

```powershell
Move-Item database/migrations-semantic/2026_11_11_000200_kirim_rdf_ke_database3.php database/migrations/
```

Setelah itu jalankan:

```bash
php artisan migrate
```

Jika migration sudah selesai dan ingin mengarsipkannya kembali:

```powershell
Move-Item database/migrations/2026_11_11_000200_kirim_rdf_ke_database3.php database/migrations-semantic/
```

Untuk mengirim ulang data RDF dari berkas sumber:

```bash
php artisan rdf:kirim
```

`database/rdf/medanvisit-data.ttl` adalah sumber data katalog; edit berkas RDF
tersebut lalu kirim ulang. Hindari opsi `--replace` kecuali memang ingin
menghapus seluruh graph Database 3 sebelum mengirim kedua berkas.

Migration RDF boleh dipindahkan ke `database/migrations` saat diperlukan.
Migration katalog PostgreSQL lama tetap jangan dipindahkan, karena akan membuat
katalog wisata disimpan kembali ke PostgreSQL.
