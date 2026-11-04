@extends('layouts.situs')
@section('foto-kaki', 'kesawan-arsip.jpg')

@php
    use App\Support\Katalog;
    $semuaTempat = Katalog::tempat();
    $jumlahKecamatan = collect($semuaTempat)->pluck('kecamatan')->unique()->count();
    $istana = Katalog::get('istana-maimun');
    $unggulan = ['istana-maimun', 'masjid-raya-al-mashun', 'tjong-a-fie-mansion'];
    $heroSlide = [
        ['berkas' => 'istana-maimun.jpg', 'nama' => 'Istana Maimun', 'sub' => 'Medan Maimun, warisan Kesultanan Deli 1888', 'alt' => 'Fasad kuning Istana Maimun dengan halaman hijau di Medan'],
        ['berkas' => 'masjid-raya.jpg', 'nama' => 'Masjid Raya Al-Mashun', 'sub' => 'Medan Kota, dibangun Sultan Ma’mun Al Rasyid 1906', 'alt' => 'Masjid Raya Al-Mashun dengan kubah segi delapan dan halaman luas'],
        ['berkas' => 'tjong-a-fie-mansion.jpg', 'nama' => 'Tjong A Fie Mansion', 'sub' => 'Medan Barat, rumah saudagar Kesawan 1900', 'alt' => 'Halaman depan rumah bersejarah Tjong A Fie Mansion di Kesawan'],
        ['berkas' => 'graha-maria-annai-velangkanni.jpg', 'nama' => 'Graha Maria Annai Velangkanni', 'sub' => 'Medan Tuntungan, arsitektur Indo-Mogul', 'alt' => 'Menara bertingkat Graha Maria Annai Velangkanni di Medan'],
    ];
    $petaJson = collect($petaTempat)->map(fn ($t) => [
        'slug' => $t['slug'],
        'nama' => $t['nama'],
        'lat' => $t['lat'],
        'lng' => $t['lng'],
        'kategori' => $t['kategori'],
        'url' => route('tempat', $t['slug']),
        'harga' => Katalog::rupiah(Katalog::hargaTermurah($t)),
        'tiket' => ! empty($t['tiket']) ? route('tiket.pilih', $t['slug']) : null,
    ])->values();
    $fmtMenit = fn ($m) => intdiv($m, 60) ? intdiv($m, 60).' jam'.($m % 60 ? ' '.($m % 60).' mnt' : '') : $m.' mnt';
    $urutTempat = collect($semuaTempat)->sortBy(fn ($t) => ($pos = array_search($t['slug'], $unggulan, true)) !== false ? $pos : 10)->values();
@endphp

@section('isi')
{{-- 1. HERO: Small name, open sky --}}
<section class="mv-hero" data-hero-crossfade aria-labelledby="judul-utama">
    <div class="mv-hero__panggung" aria-hidden="true">
        @foreach ($heroSlide as $idx => $s)
            <figure class="mv-hero__slide {{ $idx === 0 ? 'aktif' : '' }}" data-hero-slide data-nama="{{ $s['nama'] }}" data-sub="{{ $s['sub'] }}">
                <x-foto :berkas="$s['berkas']" :alt="$s['alt']" :utama="$idx === 0" :segera="$idx === 0" />
            </figure>
        @endforeach
        <div class="mv-hero__lapisan"></div>
    </div>

    <div class="mv-hero__isi">
        <div class="mv-hero__utama">
            <h1 id="judul-utama" data-naik style="--i:0">Jelajahi Medan lewat ceritanya</h1>
            <p class="mv-hero__sub" data-naik style="--i:1">Panduan wisata Kota Medan yang menautkan {{ count($semuaTempat) }} tempat, tokoh sejarah, budaya, dan kuliner khas. Cari tujuan, pesan tiket masuk, dan kumpulkan stempel di lokasi.</p>

            <div class="mv-hero__cari-bungkus" data-naik style="--i:2">
                <form action="{{ route('cari') }}" method="get" role="search" data-cari-cepat>
                    <div class="mv-hero__cari">
                        <label class="sr-only" for="input-cari-beranda">Cari tempat, tokoh, atau kuliner di Medan</label>
                        <input id="input-cari-beranda" type="search" name="q" placeholder="Cari tempat, tokoh, atau kuliner (coba: soto dekat istana)" autocomplete="off" required>
                        <button class="mv-btn mv-btn--utama" type="submit">Cari</button>
                    </div>
                    <div class="mv-saran-kotak" data-ui data-saran-hasil role="region" aria-live="polite" hidden></div>
                </form>
                <div class="mv-hero__saran-cepat">
                    <span>Coba ketik:</span>
                    <a href="{{ route('cari', ['q' => 'istana maimon']) }}">istana maimon</a>
                    <a href="{{ route('cari', ['q' => 'tjong a fie']) }}">Tjong A Fie</a>
                    <a href="{{ route('cari', ['q' => 'soto kesawan']) }}">Soto Kesawan</a>
                    <a href="{{ route('tanya') }}">Tanya asisten</a>
                </div>
            </div>

            <div class="mv-hero__aksi" data-naik style="--i:3">
                <a class="mv-btn mv-btn--utama" href="#tempat">Lihat tempat wisata</a>
                <a class="mv-tautan mv-tautan--terang" href="#tiket">Pesan tiket masuk</a>
            </div>
        </div>

        <div class="mv-hero__info-foto" data-naik style="--i:2">
            <div class="mv-hero__label-foto">
                <b data-hero-nama>{{ $heroSlide[0]['nama'] }}</b>
                <span data-hero-sub>{{ $heroSlide[0]['sub'] }}</span>
            </div>
            <div class="mv-hero__kontrol-slide" role="group" aria-label="Kontrol foto sampul">
                @foreach ($heroSlide as $idx => $s)
                    <button type="button" class="mv-hero__titik nums" data-hero-ke="{{ $idx }}" aria-label="Foto {{ $s['nama'] }}" aria-current="{{ $idx === 0 ? 'true' : 'false' }}">{{ $idx + 1 }}</button>
                @endforeach
                <button type="button" class="mv-hero__titik" data-hero-jeda aria-pressed="false" aria-label="Jeda pergantian foto otomatis" style="width:auto;padding-inline:12px">Jeda</button>
            </div>
        </div>
    </div>
</section>

{{-- 2. GALLERY: Photo tiles with label strips --}}
<section class="mv-seksi-tempat" id="tempat" aria-labelledby="judul-tempat">
    <div class="mv-wadah">
        <div class="mv-kepala-seksi">
            <h2 id="judul-tempat">Tiga belas tempat pilihan</h2>
            <p>{{ count($semuaTempat) }} tujuan di {{ $jumlahKecamatan }} kecamatan Medan, lengkap dengan jam kunjungan, perkiraan biaya, dan kaitan sejarahnya di jaringan cerita.</p>
        </div>

        <div class="mv-saring-baris" data-saring-beranda role="group" aria-label="Saring kategori tempat">
            <button type="button" data-filter="semua" aria-pressed="true">Semua <span class="nums">({{ count($semuaTempat) }})</span></button>
            @foreach ($kategori as $k => $grup)
                @continue(! $grup['tempat'])
                <button type="button" data-filter="{{ $k }}" aria-pressed="false">{{ $grup['label'] }} <span class="nums">({{ count($grup['tempat']) }})</span></button>
            @endforeach
        </div>

        <div class="mv-grid-tempat" data-grid-beranda>
            @foreach ($urutTempat as $idx => $t)
                @php
                    $adalahLebar = in_array($t['slug'], $unggulan, true);
                    $nadaPita = ['hijau', 'krem', 'kuning'][$idx % 3];
                    $sedangBuka = Katalog::bukaSekarang($t);
                    $berkasFoto = $t['foto'] ?: 'peta-medan-1930-kota-lama.jpg';
                @endphp
                <a class="mv-kartu-tempat {{ $adalahLebar ? 'mv-kartu-tempat--lebar' : '' }}" href="{{ route('tempat', $t['slug']) }}" data-kategori="{{ $t['kategori'] }}">
                    <div class="mv-kartu-tempat__media">
                        <x-foto :berkas="$berkasFoto" :alt="$t['nama']" :kecil="! $adalahLebar" />
                    </div>
                    <div class="mv-kartu-tempat__pita mv-kartu-tempat__pita--{{ $nadaPita }}">
                        <span>{{ Katalog::KATEGORI[$t['kategori']] }}</span>
                        <span>{{ $t['kecamatan'] }}</span>
                    </div>
                    <div class="mv-kartu-tempat__isi">
                        <strong class="mv-kartu-tempat__nama">{{ $t['nama'] }}</strong>
                        <span class="mv-kartu-tempat__meta">{{ \Illuminate\Support\Str::limit($t['ringkas'], $adalahLebar ? 115 : 80) }}</span>
                        <div class="mv-kartu-tempat__kaki">
                            <span><x-harga :tempat="$t" /></span>
                            <span class="mv-lencana-jam {{ $sedangBuka ? 'mv-lencana-jam--buka' : 'mv-lencana-jam--tutup' }}">{{ $sedangBuka ? 'Buka' : 'Tutup' }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- 3. SHOWCASE: Square plate, spare text (Benang merah kota) --}}
<section class="mv-benang" data-benang aria-labelledby="judul-benang">
    <div class="mv-wadah">
        <div class="mv-benang__grid">
            <figure class="mv-benang__plat">
                <x-foto berkas="peta-medan-1930-kota-lama.jpg" alt="Potongan peta Medan 1930: Esplanade di utara hingga Istana Sultan Deli di selatan" />
                @foreach ($benang as $i => $b)
                    @php
                        $titik = collect($b['langkah'])->flatMap(fn ($l) => [$l['dari'], $l['ke']])->unique()
                            ->map(fn ($s) => Katalog::get($s))->filter(fn ($e) => $e['jenis'] === 'tempat' && Katalog::posisiKotaLama($e))
                            ->map(fn ($e) => ['e' => $e, 'p' => Katalog::posisiKotaLama($e)])->values();
                        $d = $titik->map(fn ($x, $j) => ($j ? 'L' : 'M').$x['p']['x'].' '.$x['p']['y'])->implode(' ');
                    @endphp
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" data-panel-benang="{{ $i }}" @if ($i) hidden @endif aria-hidden="true">
                        <path class="utas-alas" d="{{ $d }}" pathLength="220"/>
                        <path class="utas" d="{{ $d }}" pathLength="220"/>
                        @foreach ($titik as $x)
                            <circle class="simpul" cx="{{ $x['p']['x'] }}" cy="{{ $x['p']['y'] }}" r="1.9"/>
                            @php $lw = mb_strlen($x['e']['nama']) * 1.75 + 3; $kiri = $x['p']['x'] > 62; @endphp
                            <g class="nama-simpul" transform="translate({{ $kiri ? $x['p']['x'] - 3 - $lw : $x['p']['x'] + 3 }} {{ $x['p']['y'] - 2.6 }})">
                                <rect width="{{ $lw }}" height="5.2"/>
                                <text x="1.5" y="3.9">{{ $x['e']['nama'] }}</text>
                            </g>
                        @endforeach
                    </svg>
                @endforeach
                <figcaption>Peta kota lama Medan, 1930</figcaption>
            </figure>

            <div class="mv-benang__sisi">
                <div>
                    <h2 id="judul-benang" data-muncul>Benang merah kota</h2>
                    <p style="margin-top:12px;color:var(--mv-redup)" data-muncul style="--i:1">Setiap tempat di Medan saling bertaut lewat tokoh pendiri, kawasan sejarah, dan tradisi kulinernya. Pilih pasangan tempat untuk melihat jejak hubungannya.</p>
                </div>

                <div class="mv-benang__tab" role="tablist" aria-label="Pasangan tempat bersejarah">
                    @foreach ($benang as $i => $b)
                        <button type="button" role="tab" id="tab-benang-{{ $i }}" aria-controls="panel-benang-{{ $i }}" aria-selected="{{ $i ? 'false' : 'true' }}" tabindex="{{ $i ? '-1' : '0' }}" data-tab-benang="{{ $i }}">
                            {{ $b['dari']['nama'] }} &amp; {{ \Illuminate\Support\Str::of($b['ke']['nama'])->replace(' Al-Mashun', '') }}
                        </button>
                    @endforeach
                </div>

                @foreach ($benang as $i => $b)
                    <div class="mv-benang__panel" role="tabpanel" id="panel-benang-{{ $i }}" aria-labelledby="tab-benang-{{ $i }}" data-panel-benang="{{ $i }}" @if ($i) hidden @endif>
                        <p class="mv-benang__tanya">Bagaimana {{ $b['dari']['nama'] }} terhubung dengan {{ $b['ke']['nama'] }}?</p>
                        <ol class="mv-benang__langkah">
                            @foreach ($b['langkah'] as $l)
                                <li>{{ $l['kalimat'] }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach

                <div class="mv-benang__aksi">
                    @foreach ($benang as $i => $b)
                        <a class="mv-btn mv-btn--hijau" href="{{ route('jelajah', ['dari' => $b['dari']['slug'], 'ke' => $b['ke']['slug']]) }}" data-panel-benang="{{ $i }}" @if ($i) hidden @endif>Buka di jelajah graf</a>
                    @endforeach
                    <a class="mv-btn mv-btn--garis" href="{{ route('jelajah') }}">Hubungkan dua tempat lain</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 4. PRICING: Prices like a menu (Tiket tanpa antre) --}}
<section class="mv-seksi-tiket" id="tiket" aria-labelledby="judul-tiket">
    <div class="mv-wadah">
        <div class="mv-menu-tiket">
            <div class="mv-menu-tiket__kiri">
                <h2 id="judul-tiket" data-muncul>Tiket masuk tanpa antre</h2>
                <p style="color:var(--mv-redup)" data-muncul style="--i:1">Pilih tanggal kunjungan dan jumlah pengunjung, bayar dengan QRIS atau virtual account, lalu tunjukkan e-tiket ber-QR di loket masuk.</p>
                <figure class="mv-menu-tiket__potret" data-muncul style="--i:2">
                    <x-foto berkas="istana-maimun.jpg" alt="Arsitektur kuning khas Melayu Deli di Istana Maimun" :kecil="true" />
                </figure>
            </div>

            <div class="mv-menu-tiket__daftar">
                @foreach ($bertiket as $t)
                    <article class="mv-baris-tiket">
                        <div class="mv-baris-tiket__atas">
                            <a class="mv-baris-tiket__judul" href="{{ route('tempat', $t['slug']) }}">{{ $t['nama'] }}</a>
                            <span class="mv-baris-tiket__mulai">Mulai {{ Katalog::rupiah(Katalog::hargaTermurah($t)) }}</span>
                        </div>
                        <p>{{ $t['ringkas'] }}</p>
                        <div class="mv-baris-tiket__tarif nums">
                            @foreach ($t['tiket'] as $jenis => $harga)
                                <span>{{ $jenis }}: <b>{{ Katalog::rupiah($harga) }}</b></span>
                            @endforeach
                            @if (! empty($t['harga_contoh']))
                                <span class="contoh">harga contoh</span>
                            @endif
                        </div>
                        <div class="mv-baris-tiket__bawah">
                            <span class="mv-meta-garis kecil redup">
                                <span>{{ $t['kecamatan'] }}</span>
                                <span>Buka {{ $t['jam'] }} @if (! empty($t['jam_contoh']))<span class="contoh">contoh</span>@endif</span>
                            </span>
                            <a class="mv-btn mv-btn--utama mv-btn--kecil" href="{{ route('tiket.pilih', $t['slug']) }}" aria-label="Pesan tiket {{ $t['nama'] }}">Pesan tiket</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- 5. WORK: Loose scatter with pauses (Jalur siap jalan) --}}
<section class="mv-seksi-jalur" aria-labelledby="judul-jalur">
    <div class="mv-wadah">
        <div class="mv-kepala-seksi">
            <h2 id="judul-jalur">Jalur siap jalan</h2>
            <p>Tiga rute bertema yang dirangkai dari kedekatan lokasi dan benang cerita di lapangan, lengkap dengan jarak tempuh serta waktu singgah.</p>
        </div>

        <div class="mv-jalur-daftar">
            @foreach (array_values($jalur) as $idx => $j)
                <article class="mv-jalur-item {{ $idx % 2 === 1 ? 'mv-jalur-item--balik' : '' }}">
                    <figure class="mv-jalur-item__foto">
                        <x-foto :berkas="$j['foto']" :alt="$j['nama'].': '.$j['ringkas']" />
                    </figure>
                    <div class="mv-jalur-item__teks">
                        <h3>{{ $j['nama'] }}</h3>
                        <dl class="mv-jalur-spek nums">
                            <div>
                                <dd>{{ count($j['singgah']) }} tempat</dd>
                                <dt>Perhentian</dt>
                            </div>
                            <div>
                                <dd>{{ number_format($j['meter'] / 1000, 1, ',', '.') }} km</dd>
                                <dt>Jarak rute</dt>
                            </div>
                            <div>
                                <dd>{{ $fmtMenit($j['menit']) }}</dd>
                                <dt>Perkiraan waktu</dt>
                            </div>
                        </dl>
                        <p>{{ $j['ringkas'] }}</p>
                        <div class="mv-jalur-rute-kecil">
                            <span class="mv-jalur-rute-kecil__label">Urutan singgah</span>
                            <ol class="mv-jalur-singgah">
                                @foreach ($j['singgah'] as $no => $s)
                                    <li><b class="nums">{{ $no + 1 }}.</b> <span>{{ Katalog::get($s)['nama'] ?? $s }}</span></li>
                                @endforeach
                            </ol>
                        </div>
                        <div>
                            <a class="mv-btn mv-btn--hijau mv-btn--kecil" href="{{ route('jalur.detail', $j['slug']) }}">Buka jalur ini</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mv-jalur-kaki" data-muncul>
            <div>
                <b style="font-family:var(--font-display);font-size:1.375rem;color:var(--mv-kuning)">Ingin urutan singgah sesuai waktumu sendiri?</b>
                <p style="margin-top:4px">Perencana Rute menyusun jadwal jalan kaki atau becak motor dari minat dan durasi kunjunganmu, lengkap dengan estimasi biaya.</p>
            </div>
            <a class="mv-btn mv-btn--utama" href="{{ route('rencana', ['minat' => ['sejarah', 'religi', 'kuliner'], 'durasi' => 4, 'moda' => ['jalan', 'becak'], 'mulai' => 'istana-maimun']) }}">Susun rute sendiri</a>
        </div>
    </div>
</section>

{{-- 6. FEATURES: Statement cell, dark rows (Peta dan paspor) --}}
<section class="mv-seksi-paspor" aria-labelledby="judul-peta-paspor">
    <div class="mv-wadah">
        <div class="mv-paspor-grid">
            <div class="mv-paspor-peta">
                <h2 id="judul-peta-paspor">Peta kota dan paspor</h2>
                <p style="color:var(--mv-redup)">Lihat sebaran ke-{{ count($semuaTempat) }} titik di atas peta Kota Medan, lalu kumpulkan stempel resmi setiap kali tiba di lokasi.</p>
                <div class="mv-paspor-peta__kanvas" data-peta data-peta-mode="ringkas" data-pusat="istana-maimun" data-tempat="{{ json_encode($petaJson) }}">
                    <p class="peta-diam" style="padding:20px">Peta interaktif tempat wisata Medan dimuat di sini.</p>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
                    <a class="mv-btn mv-btn--hijau" href="{{ route('peta') }}">Buka peta lengkap</a>
                    <a class="mv-tautan" href="{{ route('paspor') }}">Lihat Paspor Digital</a>
                </div>
            </div>

            <div class="mv-paspor-baris">
                <div class="mv-paspor-butir">
                    <div>
                        <h3>Peta {{ count($semuaTempat) }} titik</h3>
                        <p>Saring berdasarkan kategori, tempat yang sedang buka sekarang, ketersediaan tiket, atau urutkan dari jarak terdekat.</p>
                    </div>
                </div>
                <div class="mv-paspor-butir">
                    <div>
                        <h3>Check-in di lokasi</h3>
                        <p>Stempel hanya terbit dalam radius 60 sampai 150 meter di pelataran tempat wisata, masing-masing bernilai 10 poin.</p>
                    </div>
                </div>
                <div class="mv-paspor-butir">
                    <div>
                        <h3>Stempel &amp; gelar</h3>
                        <p>Kumpulkan stempel berdesain khusus tiap tempat dan naikkan gelarmu dari Pendatang Baru hingga Duta Wisata Medan.</p>
                    </div>
                    <div class="mv-paspor-butir__stempel">
                        <x-stempel :tempat="$istana" />
                    </div>
                </div>
                <div class="mv-paspor-butir">
                    <div>
                        <h3>Voucher mitra UMKM</h3>
                        <p>Gunakan poin perjalananmu untuk potongan kuliner, oleh-oleh bika ambon, dan kedai kopi lokal yang bekerja sama.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 7. FAQ: Questions beside a tall photograph --}}
<section class="mv-seksi-faq" aria-labelledby="judul-tanya">
    <div class="mv-wadah">
        <div class="mv-faq-grid">
            <div class="mv-faq-kiri">
                <h2 id="judul-tanya" data-muncul>Sebelum berangkat</h2>
                <ul class="mv-faq-fakta" data-muncul style="--i:1">
                    <li>Pembayaran tiket mendukung QRIS, virtual account, dan dompet digital.</li>
                    <li>Check-in GPS diverifikasi langsung dalam radius 60 sampai 150 meter di lokasi.</li>
                    <li>Masjid dan rumah ibadah dikunjungi dengan pakaian sopan di luar waktu ibadah.</li>
                </ul>

                <div class="mv-faq-daftar">
                    <details>
                        <summary>Bagaimana cara memesan tiket masuk?</summary>
                        <p>Buka halaman tempat bertiket, pilih tanggal dan jumlah pengunjung, isi nama serta email pemesan, lalu selesaikan pembayaran. E-tiket ber-QR langsung tersimpan di menu Tiket saya.</p>
                    </details>
                    <details>
                        <summary>Apakah e-tiket tetap bisa dibuka saat sinyal lemah?</summary>
                        <p>Bisa. Pada halaman e-tiket, tekan tombol <strong>Simpan PDF</strong> sebelum berangkat agar kode QR tersimpan di ponsel dan siap dipindai tanpa koneksi internet.</p>
                    </details>
                    <details>
                        <summary>Mengapa check-in saya belum berhasil?</summary>
                        <p>Sistem memeriksa jarak koordinat GPS ponselmu terhadap titik tempat wisata. Jika berada di dalam ruangan tebal yang menghalangi sinyal, cobalah kembali dari halaman terbuka dekat pintu masuk.</p>
                    </details>
                    <details>
                        <summary>Poin dari Paspor Digital bisa ditukar dengan apa?</summary>
                        <p>Setiap check-in menambah 10 poin dan lencana jalur memberi poin tambahan. Poin dapat ditukarkan dengan kode voucher potongan harga dari mitra UMKM Medan di halaman Voucher.</p>
                    </details>
                    <details>
                        <summary>Cerita wisata tersedia dalam bahasa apa saja?</summary>
                        <p>Seluruh cerita saat ini ditulis dalam bahasa Indonesia. Terjemahan bahasa Inggris, Mandarin, dan Melayu sedang disiapkan secara bertahap oleh tim kurator.</p>
                    </details>
                    <details>
                        <summary>Dari mana asal foto arsip dan rujukan sejarahnya?</summary>
                        <p>Foto berasal dari Wikimedia Commons dan koleksi arsip Universitas Leiden (KITLV) berlisensi terbuka. Rujukan tiap hubungan sejarah dapat diperiksa di halaman tempat maupun <a class="mv-tautan" href="{{ route('data') }}" style="min-height:0">data terbuka</a>.</p>
                    </details>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:14px 20px;align-items:center">
                    <a class="mv-btn mv-btn--hijau" href="{{ route('tanya') }}">Tanya asisten MedanVisit</a>
                    <a class="mv-tautan" href="{{ route('ketentuan') }}">Ketentuan pembelian</a>
                </div>
            </div>

            <div class="mv-faq-kanan">
                <x-foto berkas="becak-medan.jpg" alt="Deretan becak motor khas Medan di tepi jalan kota" class="mv-faq-kanan__tinggi" />
                <x-foto berkas="bika-ambon.jpg" alt="Potongan kue bika ambon bersarang khas Medan" class="mv-faq-kanan__kecil" />
            </div>
        </div>
    </div>
</section>
@endsection
