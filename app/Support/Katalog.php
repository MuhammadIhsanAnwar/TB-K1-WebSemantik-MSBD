<?php

namespace App\Support;

/**
 * Data contoh MedanVisit untuk prototipe tampilan.
 *
 * Fakta sejarah (tahun, tokoh, relasi) diambil dari dokumen rancangan Simpul Medan dan sumber publik
 * yang dicantumkan di setiap relasi. Yang belum terverifikasi ditandai:
 *  - 'harga_contoh' => true  : harga tiket contoh, belum dicek ke pengelola.
 *  - 'jam_contoh'   => true  : jam buka contoh.
 *  - 'koordinat_perkiraan' => true : titik peta diperkirakan, perlu dicek ulang ke OpenStreetMap.
 * Nanti diganti oleh data dari PostgreSQL (atribut) dan SPARQL (relasi).
 */
class Katalog
{
    public const KELAS = [
        'tempat' => 'Tempat',
        'tokoh' => 'Tokoh',
        'kerajaan' => 'Kerajaan',
        'peristiwa' => 'Peristiwa',
        'budaya' => 'Budaya',
        'kuliner' => 'Kuliner',
        'transportasi' => 'Transportasi',
        'wilayah' => 'Wilayah',
    ];

    public const KATEGORI = [
        'sejarah' => 'Sejarah & cagar budaya',
        'museum' => 'Museum',
        'religi' => 'Rumah ibadah',
        'kuliner' => 'Kuliner',
        'ruang-publik' => 'Taman & ruang publik',
        'belanja' => 'Belanja',
        'rekreasi-air' => 'Rekreasi air',
    ];

    /** Kalimat per predikat: [arah maju, arah balik]. {a} subjek, {b} objek. */
    public const KALIMAT = [
        'dibangunOleh' => ['{a} dibangun oleh {b}.', '{a} membangun {b}.'],
        'memimpin' => ['{a} memimpin {b}.', '{a} dipimpin oleh {b}.'],
        'terlibatDalam' => ['{a} hidup dan berperan pada masa {b}.', 'Pada masa {a}, {b} ikut berperan.'],
        'merepresentasikan' => ['{a} mencerminkan budaya {b}.', 'Budaya {a} tercermin di {b}.'],
        'khasDari' => ['{a} adalah kuliner khas {b}.', 'Salah satu kuliner khas {a} adalah {b}.'],
        'dekatDengan' => ['{a} hanya {jarak} meter dari {b}.', '{a} hanya {jarak} meter dari {b}.'],
        'bergaya' => ['{a} memakai gaya {b}.', 'Gaya {a} juga terlihat di {b}.'],
        'bagianDari' => ['{a} berada di dalam kompleks {b}.', 'Di dalam kompleks {a} ada {b}.'],
        'berkaitanDengan' => ['{a} berkaitan dengan {b}.', '{a} berkaitan dengan {b}.'],
        'memilikiSejarah' => ['Sejarah {a} dimulai dari {b}.', '{a} melahirkan {b}.'],
        'menjual' => ['{a} menjual {b}.', '{a} bisa dicicipi di {b}.'],
        'dilayaniOleh' => ['{a} bisa dicapai dengan {b}.', '{a} biasa mengantar pengunjung ke {b}.'],
        'beradaDi' => ['{a} berada di Kecamatan {b}.', 'Kecamatan {a} menjadi tempat {b}.'],
    ];

    public const LABEL_RELASI = [
        'dibangunOleh' => 'dibangun oleh',
        'memimpin' => 'memimpin',
        'terlibatDalam' => 'terlibat dalam',
        'merepresentasikan' => 'mencerminkan',
        'khasDari' => 'khas dari',
        'dekatDengan' => 'dekat dengan',
        'bergaya' => 'bergaya',
        'bagianDari' => 'bagian dari',
        'berkaitanDengan' => 'berkaitan dengan',
        'memilikiSejarah' => 'punya sejarah',
        'menjual' => 'menjual',
        'dilayaniOleh' => 'dilayani oleh',
        'beradaDi' => 'berada di',
    ];

    /** Label relasi dibaca dari sisi objek (arah balik), untuk rantai Benang merah dan daftar tetangga di graf. */
    public const LABEL_BALIK = [
        'dibangunOleh' => 'membangun',
        'memimpin' => 'dipimpin oleh',
        'terlibatDalam' => 'melibatkan',
        'merepresentasikan' => 'tercermin di',
        'khasDari' => 'punya kuliner khas',
        'dekatDengan' => 'dekat dengan',
        'bergaya' => 'terlihat di',
        'bagianDari' => 'mencakup',
        'berkaitanDengan' => 'berkaitan dengan',
        'memilikiSejarah' => 'melahirkan',
        'menjual' => 'dijual di',
        'dilayaniOleh' => 'mengantar ke',
        'beradaDi' => 'menjadi tempat',
    ];

    /** Label pendek satu relasi, dibaca dari entitas $sudut, lengkap dengan jarak bila ada. */
    public static function labelRelasi(array $relasi, string $sudut): string
    {
        $label = $relasi[0] === $sudut ? self::LABEL_RELASI[$relasi[1]] : self::LABEL_BALIK[$relasi[1]];

        return $label.(isset($relasi['jarak_m']) ? ' '.$relasi['jarak_m'].' m' : '');
    }

    public static function entitas(): array
    {
        static $data = null;

        return $data ??= self::bangun();
    }

    private static function bangun(): array
    {
        $tempat = [
            [
                'slug' => 'istana-maimun', 'nama' => 'Istana Maimun', 'kategori' => 'sejarah',
                'ringkas' => 'Istana Kesultanan Deli, dibangun 1888–1891 oleh Sultan Ma\'moen Al Rasyid.',
                'kecamatan' => 'Medan Maimun', 'kelurahan' => 'Aur', 'alamat' => 'Jl. Brigjend Katamso No. 66',
                'lat' => 3.57529, 'lng' => 98.68405,
                'foto' => 'istana-maimun.jpg', 'foto_lama' => 'istana-maimun-1900.jpg', 'tahun_foto_lama' => 'sekitar 1900',
                'tiket' => ['Dewasa' => 10000, 'Anak' => 5000], 'harga_contoh' => true,
                'jam' => 'Setiap hari, 08.00–17.00', 'jam_buka' => [8, 17], 'jam_contoh' => true,
                'fasilitas' => ['Parkir', 'Toilet', 'Mushola', 'Sewa baju adat Melayu (Rp20.000–50.000)', 'Balita di bawah 5 tahun gratis'],
                'radius_checkin' => 150, 'singgah' => 60,
                'cara' => ['Becak motor dari Kesawan, tawar ongkos sebelum naik (sekitar Rp20.000–30.000)', 'Bus Listrik Medan koridor K1 melintas di depan istana', 'Jalan kaki dari Masjid Raya Al-Mashun, sekitar 6 menit'],
                'cerita' => [
                    'Istana Maimun dibangun Sultan Ma\'moen Al Rasyid Perkasa Alamsyah, sultan Deli kesembilan, dan rampung pada 1891. Bangunannya memadukan unsur Melayu, Islam, India, dan Eropa.',
                    'Warna kuning yang mendominasi istana adalah warna kebesaran Melayu. Sampai sekarang sebagian ruangan masih ditinggali kerabat kesultanan, dan pengunjung bisa berfoto memakai baju adat Melayu di balairung.',
                    'Di halaman istana ada bangunan kecil yang menyimpan Meriam Puntung, yang dalam cerita rakyat dikaitkan dengan legenda Putri Hijau.',
                ],
            ],
            [
                'slug' => 'masjid-raya-al-mashun', 'nama' => 'Masjid Raya Al-Mashun', 'kategori' => 'religi',
                'ringkas' => 'Masjid Kesultanan Deli, dibangun 1906–1909 dengan denah segi delapan.',
                'kecamatan' => 'Medan Kota', 'kelurahan' => 'Mesjid', 'alamat' => 'Jl. Sisingamangaraja',
                'lat' => 3.57517, 'lng' => 98.68730,
                'foto' => 'masjid-raya.jpg', 'foto_potret' => 'masjid-raya-potret.jpg',
                'tiket' => null, 'jam' => 'Terbuka di luar waktu salat', 'jam_buka' => [5, 21], 'jam_contoh' => true,
                'fasilitas' => ['Parkir', 'Tempat wudu', 'Peminjaman kain penutup'],
                'radius_checkin' => 100, 'singgah' => 45,
                'cara' => ['Jalan kaki dari Istana Maimun, sekitar 6 menit'],
                'cerita' => [
                    'Masjid Raya Al-Mashun dibangun oleh Sultan Ma\'moen Al Rasyid, sultan yang sama yang membangun Istana Maimun, dan selesai pada 1909.',
                    'Denahnya segi delapan dengan kubah utama dikelilingi empat serambi. Masjid ini masih dipakai untuk salat berjamaah, jadi pengunjung datang di luar waktu salat dan berpakaian sopan.',
                ],
            ],
            [
                'slug' => 'tjong-a-fie-mansion', 'nama' => 'Tjong A Fie Mansion', 'kategori' => 'sejarah',
                'ringkas' => 'Rumah saudagar Tjong A Fie di Kesawan, kini museum rumah.',
                'kecamatan' => 'Medan Barat', 'kelurahan' => 'Kesawan', 'alamat' => 'Jl. Jend. Ahmad Yani No. 105',
                'lat' => 3.58543, 'lng' => 98.68037,
                'foto' => 'tjong-a-fie-mansion.jpg', 'foto_lama' => 'tjong-a-fie-arsip.jpg', 'tahun_foto_lama' => 'awal 1900-an',
                'tiket' => ['Umum, termasuk pemandu' => 35000],
                'jam' => 'Setiap hari, 09.00–17.00 (masuk terakhir 16.30), tutup saat Imlek', 'jam_buka' => [9, 17],
                'fasilitas' => ['Pemandu', 'Toilet', 'Kafe'],
                'radius_checkin' => 80, 'singgah' => 60,
                'cara' => ['Jalan kaki dari Lapangan Merdeka, sekitar 8 menit', 'Becak motor dari Istana Maimun, sekitar 10 menit'],
                'cerita' => [
                    'Tjong A Fie adalah saudagar Hakka yang datang ke Deli pada masa kejayaan tembakau dan menjadi pemimpin komunitas Tionghoa Medan. Rumahnya di Kesawan selesai dibangun sekitar 1900.',
                    'Arsitekturnya memadukan rumah Tionghoa, Eropa, dan Melayu. Kamar, perabot, dan foto keluarga masih tersimpan, sehingga pengunjung bisa melihat bagaimana sebuah keluarga saudagar hidup di Medan awal abad ke-20.',
                ],
            ],
            [
                'slug' => 'meriam-puntung', 'nama' => 'Meriam Puntung', 'kategori' => 'sejarah',
                'ringkas' => 'Meriam di halaman Istana Maimun yang dikaitkan dengan legenda Putri Hijau.',
                'kecamatan' => 'Medan Maimun', 'kelurahan' => 'Aur', 'alamat' => 'Kompleks Istana Maimun',
                'lat' => 3.57493, 'lng' => 98.68405,
                'foto' => 'meriam-puntung.jpg', 'foto_keterangan' => 'Rumah Meriam Puntung di halaman Istana Maimun', 'tiket' => null, 'jam' => 'Mengikuti jam buka Istana Maimun', 'jam_buka' => [8, 17], 'jam_contoh' => true,
                'fasilitas' => [], 'radius_checkin' => 60, 'singgah' => 15, 'cara' => ['Di halaman Istana Maimun'],
                'cerita' => [
                    'Menurut cerita rakyat, meriam ini adalah penjelmaan saudara Putri Hijau yang menembak tanpa henti saat kerajaan diserang hingga patah. Satu potongannya ada di Istana Maimun, potongan lainnya di Tanah Karo.',
                ],
            ],
            [
                'slug' => 'kuil-shri-mariamman', 'nama' => 'Kuil Shri Mariamman', 'kategori' => 'religi',
                'ringkas' => 'Kuil Hindu tertua di Medan, berdiri sejak 1884 di Kampung Madras.',
                'kecamatan' => 'Medan Petisah', 'kelurahan' => 'Petisah Tengah', 'alamat' => 'Jl. Teuku Umar No. 18',
                'lat' => 3.58397, 'lng' => 98.67102,
                'foto' => 'kuil-shri-mariamman.jpg',
                'tiket' => null, 'jam' => 'Setiap hari, 06.00–12.00 dan 16.00–20.00', 'jam_buka' => [6, 20], 'jam_contoh' => true,
                'fasilitas' => ['Lepas alas kaki di gerbang'], 'radius_checkin' => 80, 'singgah' => 30,
                'cara' => ['Becak motor dari Lapangan Merdeka, sekitar 10 menit'],
                'cerita' => [
                    'Kuil ini dibangun komunitas Tamil pada 1884 dan menjadi pusat Kampung Madras, kawasan yang juga dikenal sebagai Little India Medan. Menara gerbangnya dipenuhi patung dewa berwarna cerah.',
                ],
            ],
            [
                'slug' => 'vihara-gunung-timur', 'nama' => 'Vihara Gunung Timur', 'kategori' => 'religi',
                'ringkas' => 'Kelenteng Tionghoa di tepi Sungai Babura, dikenal dengan atap naga dan lampionnya.',
                'kecamatan' => 'Medan Polonia', 'kelurahan' => 'Madras Hulu', 'alamat' => 'Jl. Hang Tuah No. 16',
                'lat' => 3.57801, 'lng' => 98.66899,
                'foto' => 'vihara-gunung-timur.jpg',
                'tiket' => null, 'jam' => 'Setiap hari, 09.00–16.00', 'jam_buka' => [9, 16], 'jam_contoh' => true,
                'fasilitas' => ['Parkir', 'Wajib berpakaian rapi dan tertutup'], 'radius_checkin' => 80, 'singgah' => 30,
                'cara' => ['Becak motor dari Kampung Madras, sekitar 5 menit'],
                'cerita' => [
                    'Vihara Gunung Timur adalah salah satu kelenteng terbesar di Medan. Menjelang Imlek, halaman dan atapnya dipenuhi lampion merah.',
                ],
            ],
            [
                'slug' => 'graha-maria-annai-velangkanni', 'nama' => 'Graha Maria Annai Velangkanni', 'kategori' => 'religi',
                'ringkas' => 'Gereja Katolik bergaya Indo-Mughal di Tanjung Selamat, diresmikan 2005.',
                'kecamatan' => 'Medan Tuntungan', 'kelurahan' => 'Tanjung Selamat', 'alamat' => 'Jl. Sakura III, Tanjung Selamat',
                'lat' => 3.5205, 'lng' => 98.6120, 'koordinat_perkiraan' => true,
                'foto' => 'graha-maria-annai-velangkanni.jpg',
                'tiket' => null, 'jam' => 'Setiap hari, 08.00–17.00', 'jam_buka' => [8, 17], 'jam_contoh' => true,
                'fasilitas' => ['Parkir', 'Toilet'], 'radius_checkin' => 120, 'singgah' => 45,
                'cara' => ['Taksi atau ojek daring dari pusat kota, sekitar 30 menit'],
                'cerita' => [
                    'Graha ini dibangun untuk Bunda Maria Annai Velangkanni yang dihormati umat Katolik Tamil. Bangunannya bertingkat seperti candi dengan ornamen India, sehingga sering dikira kuil.',
                ],
            ],
            [
                'slug' => 'museum-negeri-sumatera-utara', 'nama' => 'Museum Negeri Sumatera Utara', 'kategori' => 'museum',
                'ringkas' => 'Museum sejarah dan budaya etnis-etnis Sumatera Utara.',
                'kecamatan' => 'Medan Kota', 'kelurahan' => 'Teladan Baru', 'alamat' => 'Jl. H. M. Joni No. 51',
                'lat' => 3.5645, 'lng' => 98.6990, 'koordinat_perkiraan' => true,
                'foto' => 'museum-negeri-sumut.jpg',
                'tiket' => ['Dewasa' => 5000, 'Anak' => 3000], 'harga_contoh' => true,
                'jam' => 'Selasa–Minggu, 08.00–15.30', 'jam_buka' => [8, 15], 'jam_contoh' => true,
                'fasilitas' => ['Parkir', 'Toilet'], 'radius_checkin' => 100, 'singgah' => 60,
                'cara' => ['Trans Metro Deli lalu jalan kaki', 'Becak motor dari Masjid Raya, sekitar 10 menit'],
                'cerita' => [
                    'Koleksinya mengenalkan etnis-etnis Sumatera Utara, dari Melayu, Batak, Nias, sampai Tionghoa dan Tamil yang datang pada masa perkebunan.',
                ],
            ],
            [
                'slug' => 'soto-kesawan', 'nama' => 'Soto Kesawan', 'kategori' => 'kuliner',
                'ringkas' => 'Warung soto lama di Kesawan, beberapa langkah dari Tjong A Fie Mansion.',
                'kecamatan' => 'Medan Barat', 'kelurahan' => 'Kesawan', 'alamat' => 'Jl. Jend. Ahmad Yani',
                'lat' => 3.5879, 'lng' => 98.6791, 'koordinat_perkiraan' => true,
                'foto' => 'soto-medan.jpg', 'foto_keterangan' => 'Soto Medan berkuah santan',
                'tiket' => null, 'jam' => 'Pagi sampai siang', 'jam_buka' => [7, 15], 'jam_contoh' => true,
                'fasilitas' => [], 'radius_checkin' => 60, 'singgah' => 40,
                'cara' => ['Jalan kaki dari Tjong A Fie Mansion'],
                'cerita' => ['Soto Medan berkuah santan kuning, disajikan dengan perkedel dan emping.'],
            ],
            [
                'slug' => 'bika-ambon-mojopahit', 'nama' => 'Deretan Bika Ambon Jl. Mojopahit', 'kategori' => 'kuliner',
                'ringkas' => 'Jalan tempat toko-toko bika ambon berjajar, tujuan oleh-oleh khas Medan.',
                'kecamatan' => 'Medan Petisah', 'kelurahan' => 'Petisah Tengah', 'alamat' => 'Jl. Mojopahit',
                'lat' => 3.5893, 'lng' => 98.6683, 'koordinat_perkiraan' => true,
                'foto' => 'bika-ambon.jpg', 'foto_keterangan' => 'Bika ambon pandan',
                'tiket' => null, 'jam' => 'Setiap hari, 07.00–21.00', 'jam_buka' => [7, 21], 'jam_contoh' => true,
                'fasilitas' => [], 'radius_checkin' => 120, 'singgah' => 30,
                'cara' => ['Becak motor dari Kuil Shri Mariamman, sekitar 5 menit'],
                'cerita' => ['Bika ambon Medan bertekstur bersarang, beraroma daun jeruk dan pandan. Di Jl. Mojopahit toko-tokonya berjajar.'],
            ],
            [
                'slug' => 'lapangan-merdeka', 'nama' => 'Lapangan Merdeka', 'kategori' => 'ruang-publik',
                'ringkas' => 'Alun-alun kota yang di peta 1930 masih bernama Esplanade.',
                'kecamatan' => 'Medan Barat', 'kelurahan' => 'Kesawan', 'alamat' => 'Jl. Balai Kota',
                'lat' => 3.5904, 'lng' => 98.6779, 'koordinat_perkiraan' => true,
                'foto' => 'lapangan-merdeka.jpg', 'foto_keterangan' => 'Lapangan Merdeka hari ini', 'foto_posisi' => '70% 50%', 'foto_lama' => 'esplanade-medan-arsip.jpg', 'tahun_foto_lama' => 'masa Esplanade', 'tiket' => null, 'jam' => 'Terbuka 24 jam', 'jam_buka' => [0, 24], 'jam_contoh' => true,
                'fasilitas' => [], 'radius_checkin' => 150, 'singgah' => 30,
                'cara' => ['Stasiun Medan ada di sisi timur lapangan'],
                'cerita' => ['Di peta Medan 1930, lapangan ini tercatat sebagai Esplanade, dikelilingi kantor pos, bank, dan hotel kolonial.'],
            ],
            [
                'slug' => 'taman-sri-deli', 'nama' => 'Taman Sri Deli', 'kategori' => 'ruang-publik',
                'ringkas' => 'Taman berkolam di depan Masjid Raya Al-Mashun.',
                'kecamatan' => 'Medan Kota', 'kelurahan' => 'Mesjid', 'alamat' => 'Jl. Sisingamangaraja',
                'lat' => 3.5744, 'lng' => 98.6881, 'koordinat_perkiraan' => true,
                'foto' => 'taman-sri-deli.jpg', 'foto_keterangan' => 'Taman Sri Deli', 'tiket' => null, 'jam' => 'Terbuka 24 jam', 'jam_buka' => [0, 24], 'jam_contoh' => true,
                'fasilitas' => [], 'radius_checkin' => 100, 'singgah' => 20,
                'cara' => ['Di seberang Masjid Raya Al-Mashun'],
                'cerita' => ['Taman ini dahulu bagian dari kawasan Kesultanan Deli dan kini jadi tempat warga bersantai sore.'],
            ],
            [
                'slug' => 'gereja-immanuel', 'nama' => 'Gereja Immanuel', 'kategori' => 'religi',
                'ringkas' => 'Gereja Protestan berlonceng menara di Jl. Diponegoro.',
                'kecamatan' => 'Medan Polonia', 'kelurahan' => 'Madras Hulu', 'alamat' => 'Jl. Diponegoro No. 25',
                'lat' => 3.5838, 'lng' => 98.6732, 'koordinat_perkiraan' => true,
                'foto' => 'gereja-immanuel.jpg', 'foto_keterangan' => 'GPIB Immanuel hari ini', 'foto_arsip' => 'gereja-immanuel-arsip.jpg', 'foto_arsip_keterangan' => 'Gereja ini pada masa kolonial. Judul arsipnya menyebut jalan di depannya Mangalaan.', 'tiket' => null, 'jam' => 'Di luar jadwal ibadah', 'jam_buka' => [8, 16], 'jam_contoh' => true,
                'fasilitas' => [], 'radius_checkin' => 80, 'singgah' => 20,
                'cara' => ['Jalan kaki dari Kuil Shri Mariamman, sekitar 8 menit'],
                'cerita' => ['Salah satu gereja tua Medan yang masih dipakai beribadah.'],
            ],
        ];

        $lain = [
            ['slug' => 'sultan-maamun-al-rasyid', 'jenis' => 'tokoh', 'nama' => 'Sultan Ma\'moen Al Rasyid', 'ringkas' => 'Sultan Deli kesembilan, memerintah 1873–1924.', 'masa' => '1873–1924 (masa pemerintahan)'],
            ['slug' => 'tjong-a-fie', 'jenis' => 'tokoh', 'nama' => 'Tjong A Fie', 'ringkas' => 'Saudagar dan pemimpin komunitas Tionghoa Medan, 1860–1921.', 'masa' => '1860–1921', 'foto' => 'tjong-a-fie-arsip.jpg'],
            ['slug' => 'jacob-nienhuys', 'jenis' => 'tokoh', 'nama' => 'Jacob Nienhuys', 'ringkas' => 'Pengusaha Belanda yang membuka perkebunan tembakau Deli pada 1863.', 'masa' => '1836–1928'],
            ['slug' => 'kesultanan-deli', 'jenis' => 'kerajaan', 'nama' => 'Kesultanan Deli', 'ringkas' => 'Kerajaan Melayu yang berpusat di Medan.'],
            ['slug' => 'pembangunan-istana-maimun', 'jenis' => 'peristiwa', 'nama' => 'Pembangunan Istana Maimun', 'ringkas' => 'Istana dibangun 1888 dan rampung 1891.', 'masa' => '1888–1891'],
            ['slug' => 'pembangunan-masjid-raya', 'jenis' => 'peristiwa', 'nama' => 'Pembangunan Masjid Raya', 'ringkas' => 'Masjid dibangun 1906 dan rampung 1909.', 'masa' => '1906–1909'],
            ['slug' => 'era-tembakau-deli', 'jenis' => 'peristiwa', 'nama' => 'Era Tembakau Deli', 'ringkas' => 'Masa perkebunan tembakau yang membuat Medan tumbuh menjadi kota.', 'masa' => 'sejak 1863', 'foto' => 'tembakau-deli-1897.jpg'],
            ['slug' => 'legenda-putri-hijau', 'jenis' => 'peristiwa', 'nama' => 'Legenda Putri Hijau', 'ringkas' => 'Cerita rakyat Deli tentang putri, naga, dan meriam yang patah.', 'masa' => 'cerita rakyat'],
            ['slug' => 'melayu-deli', 'jenis' => 'budaya', 'nama' => 'Melayu Deli', 'ringkas' => 'Budaya Melayu pesisir timur Sumatera, budaya Kesultanan Deli.'],
            ['slug' => 'tionghoa', 'jenis' => 'budaya', 'nama' => 'Tionghoa Medan', 'ringkas' => 'Komunitas Tionghoa yang datang pada masa perkebunan.'],
            ['slug' => 'tamil', 'jenis' => 'budaya', 'nama' => 'Tamil', 'ringkas' => 'Komunitas Tamil Medan, berpusat di Kampung Madras.'],
            ['slug' => 'arsitektur-mughal', 'jenis' => 'budaya', 'nama' => 'Arsitektur Mughal', 'ringkas' => 'Gaya bangunan Islam India: kubah, lengkung, dan menara.'],
            ['slug' => 'bubur-pedas', 'jenis' => 'kuliner', 'nama' => 'Bubur pedas', 'ringkas' => 'Bubur rempah Melayu Deli, biasa disajikan saat Ramadan.', 'foto' => 'bubur-pedas.jpg'],
            ['slug' => 'roti-jala', 'jenis' => 'kuliner', 'nama' => 'Roti jala', 'ringkas' => 'Roti berbentuk jaring, dimakan dengan kari.', 'foto' => 'roti-jala.jpg'],
            ['slug' => 'bika-ambon', 'jenis' => 'kuliner', 'nama' => 'Bika ambon', 'ringkas' => 'Kue bersarang beraroma pandan, oleh-oleh khas Medan.', 'foto' => 'bika-ambon.jpg'],
            ['slug' => 'soto-medan', 'jenis' => 'kuliner', 'nama' => 'Soto Medan', 'ringkas' => 'Soto berkuah santan kuning.', 'foto' => 'soto-medan.jpg'],
            ['slug' => 'becak-motor', 'jenis' => 'transportasi', 'nama' => 'Becak motor', 'ringkas' => 'Sepeda motor bergandeng kabin, angkutan khas Medan.', 'foto' => 'becak-medan.jpg'],
            ['slug' => 'trans-metro-deli', 'jenis' => 'transportasi', 'nama' => 'Trans Metro Deli', 'ringkas' => 'Bus kota Medan.'],
        ];

        $data = [];
        foreach ($tempat as $t) {
            $data[$t['slug']] = $t + ['jenis' => 'tempat'];
        }
        foreach ($lain as $e) {
            $data[$e['slug']] = $e;
        }
        foreach (['Medan Maimun', 'Medan Kota', 'Medan Barat', 'Medan Petisah', 'Medan Polonia', 'Medan Tuntungan'] as $kec) {
            $slug = 'kec-'.str($kec)->slug();
            $data[$slug] = ['slug' => $slug, 'jenis' => 'wilayah', 'nama' => $kec, 'ringkas' => 'Kecamatan di Kota Medan.'];
        }

        return $data;
    }

    public static function relasi(): array
    {
        static $r = null;

        return $r ??= self::bangunRelasi();
    }

    private static function bangunRelasi(): array
    {
        $wiki = fn ($judul) => 'https://id.wikipedia.org/wiki/'.$judul;

        $r = [
            ['istana-maimun', 'dibangunOleh', 'sultan-maamun-al-rasyid', 'sumber' => $wiki('Istana_Maimun')],
            ['masjid-raya-al-mashun', 'dibangunOleh', 'sultan-maamun-al-rasyid', 'sumber' => $wiki('Masjid_Raya_Medan')],
            ['sultan-maamun-al-rasyid', 'memimpin', 'kesultanan-deli', 'sumber' => $wiki('Kesultanan_Deli')],
            ['istana-maimun', 'merepresentasikan', 'melayu-deli', 'sumber' => $wiki('Istana_Maimun')],
            ['istana-maimun', 'bergaya', 'arsitektur-mughal', 'sumber' => $wiki('Istana_Maimun')],
            ['masjid-raya-al-mashun', 'bergaya', 'arsitektur-mughal', 'sumber' => $wiki('Masjid_Raya_Medan')],
            ['istana-maimun', 'dekatDengan', 'masjid-raya-al-mashun', 'sumber' => 'Dihitung dari koordinat'],
            ['istana-maimun', 'memilikiSejarah', 'pembangunan-istana-maimun', 'sumber' => $wiki('Istana_Maimun')],
            ['masjid-raya-al-mashun', 'memilikiSejarah', 'pembangunan-masjid-raya', 'sumber' => $wiki('Masjid_Raya_Medan')],
            ['meriam-puntung', 'bagianDari', 'istana-maimun', 'sumber' => $wiki('Meriam_Puntung')],
            ['meriam-puntung', 'berkaitanDengan', 'legenda-putri-hijau', 'sumber' => $wiki('Putri_Hijau')],
            ['bubur-pedas', 'khasDari', 'melayu-deli', 'sumber' => $wiki('Bubur_pedas')],
            ['roti-jala', 'khasDari', 'melayu-deli', 'sumber' => $wiki('Roti_jala')],
            ['sultan-maamun-al-rasyid', 'terlibatDalam', 'era-tembakau-deli', 'sumber' => $wiki('Kesultanan_Deli')],
            ['tjong-a-fie', 'terlibatDalam', 'era-tembakau-deli', 'sumber' => $wiki('Tjong_A_Fie')],
            ['jacob-nienhuys', 'terlibatDalam', 'era-tembakau-deli', 'sumber' => $wiki('Tembakau_Deli')],
            ['tjong-a-fie-mansion', 'dibangunOleh', 'tjong-a-fie', 'sumber' => $wiki('Rumah_Tjong_A_Fie')],
            ['tjong-a-fie', 'berkaitanDengan', 'tionghoa', 'sumber' => $wiki('Tjong_A_Fie')],
            ['vihara-gunung-timur', 'merepresentasikan', 'tionghoa', 'sumber' => $wiki('Vihara_Gunung_Timur')],
            ['kuil-shri-mariamman', 'merepresentasikan', 'tamil', 'sumber' => $wiki('Kuil_Shri_Mariamman')],
            ['graha-maria-annai-velangkanni', 'berkaitanDengan', 'tamil', 'sumber' => $wiki('Graha_Maria_Annai_Velangkanni')],
            ['soto-kesawan', 'dekatDengan', 'tjong-a-fie-mansion', 'sumber' => 'Dihitung dari koordinat'],
            ['soto-kesawan', 'menjual', 'soto-medan', 'sumber' => 'Survei tim (perlu verifikasi)'],
            ['bika-ambon-mojopahit', 'menjual', 'bika-ambon', 'sumber' => 'Survei tim (perlu verifikasi)'],
            ['istana-maimun', 'dilayaniOleh', 'becak-motor', 'sumber' => 'Survei tim (perlu verifikasi)'],
            ['museum-negeri-sumatera-utara', 'dilayaniOleh', 'trans-metro-deli', 'sumber' => 'Survei tim (perlu verifikasi)'],
            ['taman-sri-deli', 'dekatDengan', 'masjid-raya-al-mashun', 'sumber' => 'Dihitung dari koordinat'],
            ['lapangan-merdeka', 'dekatDengan', 'soto-kesawan', 'sumber' => 'Dihitung dari koordinat'],
            ['gereja-immanuel', 'dekatDengan', 'kuil-shri-mariamman', 'sumber' => 'Dihitung dari koordinat'],
        ];

        foreach (self::entitas() as $slug => $e) {
            if ($e['jenis'] === 'tempat') {
                $r[] = [$slug, 'beradaDi', 'kec-'.str($e['kecamatan'])->slug(), 'sumber' => 'BPS Kota Medan'];
            }
        }

        // Jarak "dekat dengan" satu sumber saja: dihitung dari koordinat, tidak diketik ulang.
        foreach ($r as &$rel) {
            if ($rel[1] === 'dekatDengan') {
                $rel['jarak_m'] = self::jarakMeter(self::get($rel[0]), self::get($rel[2]));
            }
        }

        return $r;
    }

    public static function get(string $slug): ?array
    {
        return self::entitas()[$slug] ?? null;
    }

    public static function jenis(string $jenis): array
    {
        return array_filter(self::entitas(), fn ($e) => $e['jenis'] === $jenis);
    }

    public static function tempat(?string $kategori = null): array
    {
        return array_filter(self::jenis('tempat'), fn ($t) => $kategori === null || $t['kategori'] === $kategori);
    }

    public static function bertiket(): array
    {
        return array_filter(self::tempat(), fn ($t) => ! empty($t['tiket']));
    }

    public static function hargaTermurah(array $t): ?int
    {
        return empty($t['tiket']) ? null : min($t['tiket']);
    }

    /** Relasi yang menyentuh $slug, dengan arah dari sudut pandang $slug. */
    public static function tetangga(string $slug, bool $denganWilayah = true): array
    {
        $hasil = [];
        foreach (self::relasi() as $i => $r) {
            [$s, $p, $o] = $r;
            if ($s !== $slug && $o !== $slug) {
                continue;
            }
            $lawan = $s === $slug ? $o : $s;
            if (! $denganWilayah && self::get($lawan)['jenis'] === 'wilayah') {
                continue;
            }
            $hasil[] = ['id' => $i, 'predikat' => $p, 'maju' => $s === $slug, 'lawan' => $lawan, 'relasi' => $r];
        }

        return $hasil;
    }

    /** Jalur terpendek (BFS) antara dua entitas, tanpa lewat simpul wilayah. Mengembalikan daftar langkah. */
    public static function benangMerah(string $dari, string $ke): ?array
    {
        if (! self::get($dari) || ! self::get($ke)) {
            return null;
        }
        $antre = [$dari];
        $asal = [$dari => null];
        while ($antre) {
            $kini = array_shift($antre);
            if ($kini === $ke) {
                break;
            }
            foreach (self::tetangga($kini, false) as $t) {
                if (! array_key_exists($t['lawan'], $asal)) {
                    $asal[$t['lawan']] = [$kini, $t];
                    $antre[] = $t['lawan'];
                }
            }
        }
        if (! array_key_exists($ke, $asal)) {
            return null;
        }
        $langkah = [];
        for ($n = $ke; $asal[$n] !== null; $n = $asal[$n][0]) {
            [$sebelum, $t] = $asal[$n];
            $langkah[] = ['dari' => $sebelum, 'ke' => $n, 'kalimat' => self::kalimat($t['relasi'], $sebelum), 'relasi' => $t['relasi']];
        }

        return array_reverse($langkah);
    }

    /** Kalimat untuk satu relasi, dibaca dari entitas $sudut. */
    public static function kalimat(array $relasi, string $sudut): string
    {
        [$s, $p, $o] = $relasi;
        $maju = $s === $sudut;
        $a = self::get($maju ? $s : $o)['nama'];
        $b = self::get($maju ? $o : $s)['nama'];
        $pola = self::KALIMAT[$p][$maju ? 0 : 1];

        return strtr($pola, ['{a}' => $a, '{b}' => $b, '{jarak}' => number_format($relasi['jarak_m'] ?? 0, 0, ',', '.')]);
    }

    public static function jarakMeter(array $a, array $b): int
    {
        $r = 6371000;
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLng = deg2rad($b['lng'] - $a['lng']);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLng / 2) ** 2;

        return (int) round(2 * $r * asin(sqrt($h)));
    }

    /** Perpindahan antar dua tempat: moda dan menit. Becak 18 km/jam, jalan kaki 4,5 km/jam. */
    public static function perpindahan(array $a, array $b, array $moda): array
    {
        $m = self::jarakMeter($a, $b);
        if ($m <= 700 && in_array('jalan', $moda, true) || ! in_array('becak', $moda, true)) {
            return ['moda' => 'Jalan kaki', 'meter' => $m, 'menit' => max(1, (int) round($m / 75)), 'biaya' => 0];
        }

        return ['moda' => 'Becak motor', 'meter' => $m, 'menit' => max(3, (int) round($m / 300) + 2), 'biaya' => 10000 + (int) (ceil(max(0, $m - 1000) / 1000) * 5000)];
    }

    public static function jalurTematik(): array
    {
        $jalur = [
            'jejak-kesultanan-deli' => [
                'nama' => 'Jejak Kesultanan Deli', 'foto' => 'istana-maimun-1900.jpg',
                'ringkas' => 'Istana, meriam, masjid, dan taman yang dibangun di masa Sultan Ma\'moen Al Rasyid.',
                'singgah' => ['istana-maimun', 'meriam-puntung', 'masjid-raya-al-mashun', 'taman-sri-deli'],
            ],
            'satu-kota-banyak-rumah-ibadah' => [
                'nama' => 'Satu kota, banyak rumah ibadah', 'foto' => 'vihara-gunung-timur.jpg',
                'ringkas' => 'Masjid, kuil, vihara, dan gereja yang berdiri berdekatan di pusat Medan.',
                'singgah' => ['masjid-raya-al-mashun', 'vihara-gunung-timur', 'kuil-shri-mariamman', 'gereja-immanuel'],
            ],
            'kesawan-tempo-dulu' => [
                'nama' => 'Kesawan tempo dulu', 'foto' => 'kesawan-arsip.jpg',
                'ringkas' => 'Esplanade, rumah saudagar, dan warung soto di jalan dagang tertua Medan.',
                'singgah' => ['lapangan-merdeka', 'tjong-a-fie-mansion', 'soto-kesawan'],
            ],
        ];

        foreach ($jalur as $slug => &$j) {
            $j['slug'] = $slug;
            $meter = 0;
            $menit = 0;
            foreach ($j['singgah'] as $i => $s) {
                $menit += self::get($s)['singgah'];
                if ($i > 0) {
                    $p = self::perpindahan(self::get($j['singgah'][$i - 1]), self::get($s), ['jalan', 'becak']);
                    $meter += $p['meter'];
                    $menit += $p['menit'];
                }
            }
            $j['meter'] = $meter;
            $j['menit'] = $menit;
        }

        return $jalur;
    }

    /** Menyusun rute: pilih tempat sesuai minat, urutkan tetangga terdekat dari titik mulai, potong sesuai durasi. */
    public static function susunRute(array $minat, int $durasiJam, array $moda, string $mulai): array
    {
        $pool = array_filter(self::tempat(), fn ($t) => in_array($t['kategori'], $minat, true) || $t['slug'] === $mulai);
        $sisaMenit = $durasiJam * 60;
        $awal = self::get($mulai) ?? reset($pool);
        $urut = [];
        $kini = $awal;
        $jam = 8 * 60 + 30;
        $total = 0;
        unset($pool[$awal['slug']]);
        $urut[] = ['tempat' => $awal, 'mulai' => $jam, 'pindah' => null];
        $sisaMenit -= $awal['singgah'];
        $jam += $awal['singgah'];
        $total += self::hargaTermurah($awal) ?? 0;

        while ($pool) {
            uasort($pool, fn ($x, $y) => self::jarakMeter($kini, $x) <=> self::jarakMeter($kini, $y));
            $calon = reset($pool);
            $p = self::perpindahan($kini, $calon, $moda);
            if ($p['menit'] + $calon['singgah'] > $sisaMenit || $p['meter'] > 6000) {
                break;
            }
            $sisaMenit -= $p['menit'] + $calon['singgah'];
            $jam += $p['menit'];
            $urut[] = ['tempat' => $calon, 'mulai' => $jam, 'pindah' => $p];
            $jam += $calon['singgah'];
            $total += $p['biaya'] + (self::hargaTermurah($calon) ?? 0);
            unset($pool[$calon['slug']]);
            $kini = $calon;
        }

        return ['singgah' => $urut, 'biaya' => $total, 'selesai' => $jam];
    }

    public static function jamTeks(int $menitDariTengahMalam): string
    {
        return sprintf('%02d.%02d', intdiv($menitDariTengahMalam, 60) % 24, $menitDariTengahMalam % 60);
    }

    public static function rupiah(?int $n): string
    {
        return $n === null ? 'Gratis' : 'Rp'.number_format($n, 0, ',', '.');
    }

    public static function bukaSekarang(array $t, ?int $jam = null): bool
    {
        $jam ??= (int) now('Asia/Jakarta')->format('G');

        return isset($t['jam_buka']) && $jam >= $t['jam_buka'][0] && $jam < $t['jam_buka'][1];
    }

    /** Pencarian toleran salah ketik: cocok kata, lalu kemiripan (similar_text / levenshtein). */
    public static function cari(string $q): array
    {
        $q = mb_strtolower(trim($q));
        if ($q === '') {
            return [];
        }
        $hasil = [];
        foreach (self::entitas() as $slug => $e) {
            if ($e['jenis'] === 'wilayah') {
                continue;
            }
            $teks = mb_strtolower($e['nama'].' '.($e['ringkas'] ?? '').' '.($e['kecamatan'] ?? '').' '.($e['kategori'] ?? ''));
            $nama = mb_strtolower($e['nama']);
            $skor = 0;
            foreach (preg_split('/\s+/', $q) as $kata) {
                if (mb_strlen($kata) < 3) {
                    continue;
                }
                if (str_contains($teks, $kata)) {
                    $skor += 2;
                } else {
                    foreach (preg_split('/[\s\-]+/', $nama) as $k2) {
                        if (mb_strlen($k2) >= 4 && levenshtein($kata, $k2) <= 1) {
                            $skor += 1.5;
                        }
                    }
                }
            }
            similar_text($q, $nama, $persen);
            $skor += $persen / 50;
            if ($skor >= 1.6) {
                $hasil[$slug] = $skor;
            }
        }
        arsort($hasil);

        return array_map(fn ($s) => self::get($s), array_keys($hasil));
    }

    /** Usulan koreksi ejaan: nama entitas paling mirip bila kueri tidak persis. */
    public static function koreksi(string $q): ?string
    {
        $q = mb_strtolower(trim($q));
        $terbaik = null;
        $jarak = PHP_INT_MAX;
        foreach (self::entitas() as $e) {
            $d = levenshtein($q, mb_strtolower($e['nama']));
            if ($d < $jarak) {
                [$jarak, $terbaik] = [$d, $e['nama']];
            }
        }

        return $jarak > 0 && $jarak <= 3 ? $terbaik : null;
    }

    /** Kredit foto dari credits.json (diisi oleh assets.mjs saat foto diunduh). */
    public static function kredit(?string $berkas = null): array|string|null
    {
        static $kredit = null;
        if ($kredit === null) {
            $path = public_path('images/foto/credits.json');
            $kredit = [];
            foreach (is_file($path) ? json_decode(file_get_contents($path), true) : [] as $k) {
                $kredit[basename($k['file'])] = $k;
            }
        }

        return $berkas === null ? $kredit : ($kredit[$berkas]['credit'] ?? null);
    }

    /**
     * Posisi sebuah tempat di peta Medan 1930 (piksel pada berkas 1422 × 1600).
     * Diregistrasi dari dua titik di legenda peta: "15. Palace of the Sultan of Deli" di (818, 1150)
     * dan Esplanade (kini Lapangan Merdeka) di (643, 680); skalanya ±280 px per km, sesuai 1:20.000.
     * Mengembalikan null bila tempat berada di luar bingkai peta.
     */
    public static function posisi1930(array $t): ?array
    {
        if (! isset($t['lat'])) {
            return null;
        }
        $x = 818 + ($t['lng'] - 98.6837) * 31110;
        $y = 1150 - ($t['lat'] - 3.5752) * 30960;

        return ($x > 80 && $x < 1340 && $y > 300 && $y < 1470) ? ['x' => round($x), 'y' => round($y)] : null;
    }

    /** Posisi di potongan "kota lama" (480,630)–(1040,1190), dalam persen. */
    public static function posisiKotaLama(array $t): ?array
    {
        $p = self::posisi1930($t);
        if (! $p) {
            return null;
        }
        $x = ($p['x'] - 480) / 560 * 100;
        $y = ($p['y'] - 630) / 560 * 100;

        return ($x >= 0 && $x <= 100 && $y >= 0 && $y <= 100) ? ['x' => round($x, 2), 'y' => round($y, 2)] : null;
    }

    public const GELAR = [
        0 => 'Pendatang Baru',
        50 => 'Pelancong',
        200 => 'Pengembara',
        500 => 'Penjelajah',
        1000 => 'Datuk Penjelajah',
    ];

    public static function gelar(int $poin): array
    {
        $kini = 'Pendatang Baru';
        $berikut = null;
        foreach (self::GELAR as $batas => $nama) {
            if ($poin >= $batas) {
                $kini = $nama;
            } elseif ($berikut === null) {
                $berikut = ['nama' => $nama, 'batas' => $batas];
            }
        }

        return ['nama' => $kini, 'berikut' => $berikut];
    }
}
