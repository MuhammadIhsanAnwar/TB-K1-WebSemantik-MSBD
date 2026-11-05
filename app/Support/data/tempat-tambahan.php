<?php

/*
 * Tempat tambahan dari daftar tim (5 Nov 2026), hanya yang masih ada dan berada di Kota Medan.
 * Data diambil dari laporan riset "Verifikasi data wisata MedanVisit" (folder reports/ di luar repo):
 * koordinat dari OpenStreetMap/Wikidata, jam dan harga dari media atau agregator. Karena itu semua
 * jam dan harga ditandai contoh sampai dicek langsung ke pengelola. 'sumber' menyimpan rujukan utama.
 *
 * Yang sengaja tidak dimasukkan: tutup (Wonders Water World, Medan Plaza, Merdeka Walk), nyaris kosong
 * (Grand Palladium), di luar Kota Medan (Maha Vihara Maitreya, Taman Burung Cemara Asri, Hairos, Merci,
 * Singapore Land), tidak ditemukan (Pajak Kencana, Pajak Kedai Tapak, Warkop DKI Kesawan, Plaza Anugerah,
 * Taman Marikena, Kolam Renang Nabasa, Masjid Lama Gang Seram), dan yang buktinya terlalu lemah
 * (Durian House, Si Bolang Durian, Warkop Elisabeth, Waterboom Griya Riatur).
 */

return [
    // ---------- Sejarah & cagar budaya ----------
    [
        'slug' => 'balai-kota-lama', 'nama' => 'Gedung Balai Kota Lama', 'kategori' => 'sejarah',
        'ringkas' => 'Bekas balai kota Medan di depan Lapangan Merdeka, dibangun 1908–1909, kini bagian kompleks hotel.',
        'kecamatan' => 'Medan Barat', 'kelurahan' => 'Kesawan', 'alamat' => 'Jl. Balai Kota',
        'lat' => 3.59033, 'lng' => 98.67740,
        'jam' => 'Dilihat dari luar, gedung terintegrasi dengan hotel', 'jam_contoh' => true, 'singgah' => 15,
        'cerita' => ['Gedung bermenara jam ini dibangun pada 1908–1909 sebagai balai kota Medan. Pembangunannya disebut melibatkan peran saudagar Tjong A Fie. Kini gedungnya dikelola swasta bersama hotel di sebelahnya.'],
        'sumber' => 'https://www.detik.com/sumut/berita/d-7045682/sejarah-balai-kota-medan-ada-peran-miliarder-tjong-a-fie',
    ],
    [
        'slug' => 'gedung-lonsum', 'nama' => 'Gedung London Sumatra', 'kategori' => 'sejarah',
        'ringkas' => 'Gedung perkantoran perkebunan di Kesawan, disebut gedung berlift pertama di Sumatera.',
        'kecamatan' => 'Medan Barat', 'kelurahan' => 'Kesawan', 'alamat' => 'Jl. Jend. Ahmad Yani No. 2',
        'lat' => 3.58857, 'lng' => 98.67803,
        'jam' => 'Dilihat dari luar, masih kantor aktif', 'jam_contoh' => true, 'singgah' => 10,
        'cerita' => ['Dibangun pada 1906 (sebagian sumber menulis 1909) untuk perusahaan perkebunan Harrisons & Crosfield. Sampai sekarang gedung ini masih menjadi kantor PT PP London Sumatra Indonesia.'],
        'sumber' => 'https://economy.okezone.com/read/2023/04/12/470/2797205/ini-gedung-dengan-lift-pertama-di-sumatera-sudah-ada-sejak-zaman-kolonial',
    ],
    [
        'slug' => 'menara-air-tirtanadi', 'nama' => 'Menara Air Tirtanadi', 'kategori' => 'sejarah',
        'ringkas' => 'Menara air peninggalan kolonial yang menjadi penanda kota, selesai 1908.',
        'kecamatan' => 'Medan Kota', 'kelurahan' => 'Pasar Baru', 'alamat' => 'Jl. Sisingamangaraja, kompleks PDAM Tirtanadi',
        'lat' => 3.58210, 'lng' => 98.68499,
        'jam' => 'Dilihat dari luar, berada di kompleks PDAM', 'jam_contoh' => true, 'singgah' => 10,
        'cerita' => ['Menara ini selesai pada 1908 untuk menyalurkan air bersih ke kota. Kini berstatus cagar budaya dan menjadi latar foto yang populer.'],
        'sumber' => 'https://www.detik.com/sumut/budaya/d-7210677/sejarah-menara-pdam-tirtanadi-medan-dibangun-di-masa-kolonial-belanda',
    ],
    [
        'slug' => 'pos-bloc-medan', 'nama' => 'Kantor Pos Besar Medan (Pos Bloc)', 'kategori' => 'sejarah',
        'ringkas' => 'Kantor pos kolonial di sisi Lapangan Merdeka yang kini sebagian besar menjadi ruang kuliner dan kreatif Pos Bloc.',
        'kecamatan' => 'Medan Barat', 'kelurahan' => 'Kesawan', 'alamat' => 'Jl. Pos No. 1',
        'lat' => 3.59194, 'lng' => 98.67743,
        'jam' => 'Senin–Jumat 10.00–22.00, Sabtu–Minggu 08.00–23.00', 'jam_buka' => [10, 22], 'jam_contoh' => true, 'singgah' => 60,
        'radius_checkin' => 100,
        'cerita' => ['Gedung kantor pos ini berstatus cagar budaya. Sebagian besar ruangnya kini dipakai Pos Bloc untuk tenant kuliner dan acara kreatif, jadi pengunjung bisa masuk melihat bangunannya.'],
        'sumber' => 'https://www.detik.com/sumut/berita/d-7187048/sejarah-kantor-pos-medan-yang-kini-jadi-pusat-kuliner-milenial',
    ],
    [
        'slug' => 'gedung-bank-indonesia-lama', 'nama' => 'Gedung Bank Indonesia Lama', 'kategori' => 'sejarah',
        'ringkas' => 'Gedung De Javasche Bank di Jl. Balai Kota, masih dipakai Bank Indonesia.',
        'kecamatan' => 'Medan Barat', 'kelurahan' => 'Kesawan', 'alamat' => 'Jl. Balai Kota No. 4',
        'lat' => 3.59069, 'lng' => 98.67661,
        'jam' => 'Dilihat dari luar, masih kantor Bank Indonesia', 'jam_contoh' => true, 'singgah' => 10,
        'cerita' => ['Bangunan bergaya Eropa ini dulu kantor De Javasche Bank dan kini masih menjadi kantor Bank Indonesia, sehingga hanya bisa dinikmati dari luar.'],
        'sumber' => 'https://en.wikipedia.org/wiki/List_of_colonial_buildings_in_Medan',
    ],
    [
        'slug' => 'gedung-avros', 'nama' => 'Gedung AVROS (BKS-PPS)', 'kategori' => 'sejarah',
        'ringkas' => 'Gedung asosiasi pengusaha perkebunan masa kolonial di sudut Jl. Ahmad Yani.',
        'kecamatan' => 'Medan Maimun', 'kelurahan' => 'Aur', 'alamat' => 'Sudut Jl. Jend. Ahmad Yani dan Jl. Palang Merah',
        'lat' => 3.58447, 'lng' => 98.68051,
        'jam' => 'Dilihat dari luar, masih kantor BKS-PPS', 'jam_contoh' => true, 'singgah' => 10,
        'cerita' => ['Dibangun sekitar 1918–1919 untuk AVROS, perkumpulan pengusaha perkebunan karet Sumatera Timur. Sejak 1967 gedung ini menjadi kantor BKS-PPS.'],
        'sumber' => 'https://www.penang-traveltips.com/indonesia/medan/bks-pps-building.htm',
    ],
    [
        'slug' => 'gedung-warenhuis', 'nama' => 'Gedung Warenhuis', 'kategori' => 'sejarah',
        'ringkas' => 'Toserba pertama di Medan dari masa kolonial, direvitalisasi dan dibuka lagi sekitar 2025 sebagai pusat seni dan UMKM.',
        'kecamatan' => 'Medan Barat', 'kelurahan' => 'Kesawan', 'alamat' => 'Jl. Ahmad Yani VII / Jl. Hindu',
        'lat' => 3.58833, 'lng' => 98.67694, 'koordinat_perkiraan' => true,
        'jam' => 'Jam buka belum terverifikasi', 'jam_contoh' => true, 'singgah' => 30,
        'cerita' => ['Warenhuis dibangun sekitar 1916–1919 sebagai pusat perbelanjaan modern pertama di Medan. Setelah lama terbengkalai, gedungnya direvitalisasi pada 2023–2024 dan diresmikan kembali sekitar Februari 2025.'],
        'sumber' => 'https://fahum.umsu.ac.id/info/gedung-warenhuis-diresmikan/',
    ],
    [
        'slug' => 'monumen-sisingamangaraja-xii', 'nama' => 'Monumen Sisingamangaraja XII', 'kategori' => 'sejarah',
        'ringkas' => 'Monumen pahlawan nasional di Teladan Barat. Kondisinya kurang terawat dan pendoponya terbakar Juni 2026.',
        'kecamatan' => 'Medan Kota', 'kelurahan' => 'Teladan Barat', 'alamat' => 'Jl. Sisingamangaraja No. 98',
        'lat' => 3.56498, 'lng' => 98.69292,
        'jam' => 'Ruang terbuka, jam resmi tidak ada', 'jam_contoh' => true, 'singgah' => 15,
        'cerita' => ['Monumen ini dibangun pada 1979 dan diresmikan 1992. Ini monumen, bukan museum: museum Sisingamangaraja XII berada di Balige. Pendopo rumah adat di kompleksnya terbakar pada 22 Juni 2026.'],
        'sumber' => 'https://www.detik.com/sumut/berita/d-8267318/monumen-sisingamangaraja-xii-medan-tak-terawat-begini-penampakannya',
    ],

    // ---------- Museum ----------
    [
        'slug' => 'museum-perjuangan-tni', 'nama' => 'Museum Perjuangan TNI Bukit Barisan', 'kategori' => 'museum',
        'ringkas' => 'Museum perjuangan Kodam I/Bukit Barisan di gedung kolonial Jl. Zainul Arifin.',
        'kecamatan' => 'Medan Petisah', 'kelurahan' => 'Petisah Tengah', 'alamat' => 'Jl. K.H. Zainul Arifin No. 8',
        'lat' => 3.58392, 'lng' => 98.67414,
        'tiket' => null, 'jam' => 'Senin–Jumat, 08.00–15.30 (masuk dengan donasi)', 'jam_buka' => [8, 15], 'jam_contoh' => true, 'singgah' => 45,
        'cerita' => ['Museum ini menyimpan senjata, seragam, dan dokumen perjuangan di Sumatera Utara. Dikelola Kodam I/Bukit Barisan dan hanya buka pada hari kerja.'],
        'sumber' => 'https://mamikos.com/info/museum-terbaik-di-medan-yang-wajib-dikunjungi-gnr/',
    ],
    [
        'slug' => 'rahmat-gallery', 'nama' => 'Rahmat International Wildlife Museum & Gallery', 'kategori' => 'museum',
        'ringkas' => 'Museum satwa awetan dengan koleksi taksidermi besar dari berbagai benua.',
        'kecamatan' => 'Medan Baru', 'kelurahan' => 'Petisah Hulu', 'alamat' => 'Jl. S. Parman No. 309',
        'lat' => 3.57926, 'lng' => 98.66773,
        'tiket' => ['Domestik' => 75000, 'Pelajar atau rombongan' => 25000, 'Wisatawan mancanegara' => 150000], 'harga_contoh' => true,
        'jam' => 'Setiap hari, 09.00–17.00', 'jam_buka' => [9, 17], 'jam_contoh' => true, 'singgah' => 90,
        'cerita' => ['Galeri milik Rahmat Shah ini memajang ratusan satwa awetan dalam diorama habitatnya. Dibuka untuk umum sejak 1990-an.'],
        'sumber' => 'https://www.detik.com/sumut/berita/d-8239652/6-museum-di-medan-yang-wajib-dikunjungi',
    ],
    [
        'slug' => 'museum-situs-kota-cina', 'nama' => 'Museum Situs Kota Cina', 'kategori' => 'museum',
        'ringkas' => 'Museum situs pelabuhan dagang kuno abad ke-11 sampai 14 di Medan Marelan.',
        'kecamatan' => 'Medan Marelan', 'kelurahan' => 'Paya Pasir', 'alamat' => 'Jl. Kota Cina',
        'lat' => 3.72324, 'lng' => 98.65675,
        'tiket' => ['Umum' => 12000], 'harga_contoh' => true,
        'jam' => 'Kamis–Minggu, 09.00–17.00', 'jam_buka' => [9, 17], 'jam_contoh' => true, 'singgah' => 60,
        'cerita' => ['Di Kota Cina ditemukan pecahan keramik, arca, dan sisa permukiman pelabuhan dagang yang ramai jauh sebelum kota Medan berdiri. Temuannya dipamerkan di museum kecil di lokasi situs.'],
        'sumber' => 'https://mamikos.com/info/museum-terbaik-di-medan-yang-wajib-dikunjungi-gnr/?halaman=2',
    ],

];
