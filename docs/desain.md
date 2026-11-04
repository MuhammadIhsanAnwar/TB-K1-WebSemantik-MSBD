# Dokumentasi Desain Antarmuka MedanVisit

Dokumen ini merangkum arah visual, sistem token, susunan navigasi, komponen bersama, serta kontrak antarmuka publik **MedanVisit** pada perombakan Gelombang 1.

## 1. Arah Visual & Gagasan Utama

- **Arah halaman konten (`place-carries-it`)**: Fotografi terbuka tempat wisata dan arsip sejarah Kota Medan menjadi pusat perhatian, dipadukan dengan bidang warna datar beridentitas Melayu Deli tanpa gradien CSS, tanpa bayangan pada elemen diam, dan tanpa garis pembatas tipis di luar tabel atau formulir.
- **Arah layar alat (`framed-sheet`)**: Halaman kerja seperti Peta Wisata (`/peta`), Tanya Medan (`/tanya`), dan alur pemesanan tiket memakai panel berbingkai tegas (`[data-ui]`) agar saringan, percakapan, serta formulir tetap rapi dan mudah dipindai.
- **Motif fisik khas**: Potongan sudut ala karcis cetak (`clip-path: var(--mv-potong)`) dan pita penanda datar pada kartu tempat menggantikan kartu generik bersudut membulat seragam.

## 2. Palet Warna & Kontras

Seluruh warna diturunkan dari panduan merek MedanVisit dengan rasio kontras minimum WCAG AA (`4,5:1` untuk teks normal dan `3:1` untuk teks besar):

| Token CSS | Nilai Hex | Peran Utama | Pasangan Teks Aman |
|---|---|---|---|
| `--mv-hijau` | `#0F3B2E` | Hijau Deli: kepala situs, bidang gelap, judul utama | `#F6F1E4` (`12,8:1`), `#E3A51C` (`6,7:1`) |
| `--mv-hijau-2` | `#154839` | Baris selang-seling gelap & tombol sekunder kepala | `#F6F1E4` |
| `--mv-kuning` | `#E3A51C` | Kuning Deli: tombol aksi utama, benang peta 1930, penanda aktif | `#0F3B2E` (`6,7:1`, tidak pernah putih) |
| `--mv-krem` | `#F6F1E4` | Bidang hangat editorial (Benang Merah, FAQ, kepala detail) | `#1B2622` (`12,9:1`), `#0F3B2E` (`12,8:1`) |
| `--mv-kertas` | `#F4F6F2` | Latar dasar halaman baca dan ruang kerja | `#1B2622`, `#3F574E` |
| `--mv-putih` | `#FFFFFF` | Permukaan kartu tempat, panel formulir, dan lembar e-tiket | `#1B2622`, `#0F3B2E` |
| `--mv-teks` | `#1B2622` | Teks isi utama | Di atas `#FFFFFF`, `#F4F6F2`, `#F6F1E4` |
| `--mv-redup` | `#3F574E` | Keterangan sekunder dan metadata | Di atas permukaan terang (`>= 5,5:1`) |
| `--mv-emas-teks` | `#704C00` | Angka jarak atau aksen teks emas di latar terang | Di atas `#FFFFFF` / `#F6F1E4` (`>= 6,0:1`) |

Warna kelas entitas semantik hanya digunakan pada simpul graf, penanda peta, dan legenda berlabel teks:
- Tempat: `#E3A51C`
- Tokoh: `#8E2C36`
- Kesultanan: `#9B6B3D`
- Peristiwa: `#2F6690`
- Budaya & Religi: `#6A4C9C`
- Kuliner: `#C9541E`
- Wilayah & Ruang Publik: `#2E7D4F`
- Transportasi: `#2F8A8A`

## 3. Tipografi & Skala Ukuran

- **Judul (`--font-display`)**: `Fraunces` (berat `400` dan `600`, dimuat lokal melalui `@fonts` Vite tanpa ketergantungan CDN luar).
- **Teks & Antarmuka (`--font-text`)**: `Plus Jakarta Sans` (berat `400`, `500`, `600`, `700`), dilengkapi `font-variant-numeric: tabular-nums` (`.nums`) untuk harga tiket, jarak meter, hitung mundur pembayaran, dan poin.
- **Batas baris judul**: Setiap `h1` hingga `h3` dijaga maksimal dua baris pada lebar `1440px`, `768px`, dan `390px`.

## 4. Navigasi & Komponen Bersama

Seluruh halaman publik memakai kerangka `resources/views/layouts/situs.blade.php` dengan komponen seragam:

1. **Kepala Situs (`partials/kepala.blade.php`)**: Bilah lengket Hijau Deli di semua halaman termasuk Beranda, memuat logo `tanpa-tagline-gelap.svg`, lima tautan utama (`Tempat & Peta`, `Jalur & Rute`, `Cerita Medan`, `Tanya`, `Tiket`), tautan cepat satu ketuk di layar tablet/ponsel (`Peta`, `Tanya`) tanpa bilah bawah yang menutupi teks bacaan, pemicu dialog pencarian cepat, serta menu akun wisatawan dengan lencana tiket aktif dan poin.
2. **Menu Lembar Penuh Ponsel (`partials/menu.blade.php`)**: Dialog `<dialog>` layar penuh untuk navigasi cepat di perangkat bergerak lengkap dengan kolom pencarian langsung dan akses sub-halaman akun.
3. **Dialog Pencarian Cepat (`partials/dialog-cari.blade.php`)**: Dapat dibuka lewat tombol kepala atau tombol papan ketik `/`, menampilkan saran langsung toleran salah ketik serta tautan ke asisten percakapan.
4. **Widget Asisten Melayang (`partials/asisten-melayang.blade.php`)**: Tombol di pojok kanan bawah layar desktop pada halaman selain `/tanya` yang membuka jendela percakapan ringkas ke `POST /tanya` lengkap dengan kartu tempat mini.
5. **Pembesar Foto (`partials/lightbox-foto.blade.php`)**: Dialog `<dialog>` aksesibel untuk memeriksa foto tempat ukuran penuh beserta atribusi lisensinya.
6. **Kaki Halaman Arsip (`partials/kaki.blade.php`)**: Fotografi arsip Kesawan penuh lebar dengan marka kata `MedanVisit`, tautan navigasi, atribusi sumber terbuka, serta catatan kejujuran data contoh.

## 5. Cakupan Halaman Gelombang 1

- **Beranda (`situs/beranda.blade.php`)**: Sampul fotografi silang-pudar dengan tombol jeda, kolom pencarian bersaran langsung, galeri 13 tempat dengan saringan kategori instan, panggung interaktif Benang Merah di atas peta 1930, daftar harga menu 3 tempat bertiket, 3 jalur siap jalan, peta ringkas + penjelasan Paspor Digital, serta tanya jawab praktis.
- **Detail Tempat (`situs/tempat.blade.php`)**: Tata letak editorial dua pertiga isi utama dan sepertiga kartu lengket tiket/check-in GPS, penggeser perbandingan arsip **Lorong Waktu** berskala penuh `16:9`, navigasi lompat bagian, potongan peta 1930, graf hubungan semantik, rekomendasi tempat terdekat beralasan graf, serta ulasan jujur tanpa data fiktif.
- **Peta Wisata (`situs/peta.blade.php`)**: Ruang kerja Leaflet + daftar tersinkronisasi dengan pencarian teks langsung, pengurutan jarak/biaya, saringan kategori dan jam buka, serta lembar bawah tiga tinggi (`Peta`, `Daftar`, `Penuh`) di ponsel.
- **Tanya Medan (`situs/tanya.blade.php`)**: Ruang percakapan dua kolom dengan topik pembuka populer, kartu cuplikan destinasi Medan pada tampilan awal, gelembung pesan jelas, kartu tempat mini (`Lihat tempat`, `Pesan tiket`), indikator mengetik, dan tombol coba ulang saat gagal.
- **Alur Tiket & E-Tiket (`tiket/*.blade.php`)**: Penanda langkah 1 sampai 4 yang konsisten, penghitung jumlah pengunjung dengan total langsung, hitung mundur pembayaran 15 menit, layar stempel pelunasan, lembar e-tiket berlubang dengan kode QR SVG `224px` yang bersih saat dicetak ke PDF, serta halaman `Tiket saya` bertab status.
- **Autentikasi (`akun/masuk.blade.php`, `akun/daftar.blade.php`)**: Tata letak belah seimbang `50/50` antara fotografi arsip Medan (dilengkapi strip tiga foto tempat) dan formulir fokus dengan tombol tampilkan/sembunyikan sandi, indikator kekuatan sandi langsung saat pendaftaran, serta pelestarian parameter pengalihan `kembali`.

## 6. Endpoint Tambahan Antarmuka

Satu rute baca-saja ditambahkan untuk mendukung saran langsung (autocomplete) toleran salah ketik tanpa mengubah rute maupun kontrak formulir yang sudah ada:

- **`GET /cari/saran.json?q={kata_kunci}`** (`cari.saran` pada `SitusController::saran`): Mengembalikan maksimal 6 entitas teratas hasil `Katalog::cari($q)` dalam format JSON (`{ saran: [{ nama, jenis, label_jenis, ringkas, harga, url }], tanya, semua }`) untuk ditampilkan pada kotak pencarian Beranda dan dialog pencarian cepat.

