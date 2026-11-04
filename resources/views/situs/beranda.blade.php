@extends('layouts.situs')
@section('tanpa-kepala', '1')
@section('foto-kaki', 'kesawan-arsip.jpg')

@php
    use App\Support\Katalog;
    $u = session('pengguna');
    $semuaTempat = Katalog::tempat();
    $jumlahKecamatan = collect($semuaTempat)->pluck('kecamatan')->unique()->count();
    $berlabel = ['istana-maimun', 'masjid-raya-al-mashun', 'tjong-a-fie-mansion', 'lapangan-merdeka', 'kuil-shri-mariamman'];
    $istana = Katalog::get('istana-maimun');
    $dekatIstana = collect($semuaTempat)->reject(fn ($t) => $t['slug'] === 'istana-maimun')->map(fn ($t) => $t + ['jarak' => Katalog::jarakMeter($istana, $t)])->sortBy('jarak')->take(4);
    $petaJson = collect($petaTempat)->map(fn ($t) => ['slug' => $t['slug'], 'nama' => $t['nama'], 'lat' => $t['lat'], 'lng' => $t['lng'], 'kategori' => $t['kategori'], 'url' => route('tempat', $t['slug']), 'harga' => Katalog::rupiah(Katalog::hargaTermurah($t)), 'tiket' => ! empty($t['tiket']) ? route('tiket.pilih', $t['slug']) : null])->values();
    $fmtMenit = fn ($m) => intdiv($m, 60) ? intdiv($m, 60).' j '.($m % 60).' m' : $m.' m';
@endphp

@section('isi')
<div class="panggung" data-panggung>
    <div class="panggung__baca">
        <section class="sampul" aria-labelledby="judul-utama">
            <div class="sampul__baris">
                <nav aria-label="Jelajah">
                    <a href="{{ route('jelajah') }}">Jelajah</a>
                    <a href="{{ route('peta') }}">Peta</a>
                    <a href="{{ route('jalur') }}">Jalur</a>
                </nav>
                <a class="sampul__logo" href="{{ route('beranda') }}" aria-label="MedanVisit">
                    <img src="{{ asset('images/logo/tanpa-tagline-terang.svg') }}" alt="MedanVisit" width="736" height="256">
                </a>
                <nav aria-label="Akun dan bantuan">
                    <a href="{{ route('tanya') }}" class="hanya-lebar">Tanya</a>
                    @if ($u)
                        <a href="{{ route('akun') }}" class="hanya-lebar">{{ $u['nama'] }}</a>
                    @else
                        <a href="{{ route('masuk') }}" class="hanya-lebar">Masuk</a>
                    @endif
                    <button class="kepala__menu" type="button" data-buka-menu aria-haspopup="dialog" aria-controls="lembar-menu">Menu</button>
                </nav>
            </div>
            <div class="sampul__isi">
                <h1 id="judul-utama" data-naik>Jelajahi Medan lewat ceritanya</h1>
                <p class="sampul__teks" data-naik style="--i:1">Panduan wisata Kota Medan yang menghubungkan istana, rumah ibadah, kuliner, dan tokohnya. Cari tempat, pesan tiket masuk, dan kumpulkan stempel.</p>
                <form class="tanya-medan" action="{{ route('cari') }}" method="get" role="search" data-naik style="--i:2">
                    <label>
                        <span class="sr-only">Cari tempat, tokoh, atau kuliner</span>
                        <input type="search" name="q" placeholder="soto dekat istana" autocomplete="off" required>
                    </label>
                    <button class="tombol" type="submit">Cari</button>
                </form>
                <p class="sampul__contoh" data-naik style="--i:3">Salah ketik tetap ketemu, coba <a href="{{ route('cari', ['q' => 'istana maimon']) }}">istana maimon</a>. Atau <a href="{{ route('tanya') }}">tanya asisten</a>.</p>
            </div>
        </section>

        <section class="daftar-besar" aria-labelledby="judul-tempat">
            <h2 class="kata-raksasa" id="judul-tempat"><span class="sr-only">Tempat</span><svg viewBox="0 0 1000 330" aria-hidden="true"><text x="0" y="245" textLength="1000" lengthAdjust="spacingAndGlyphs" font-size="330">Tempat</text></svg></h2>
            <p class="daftar-besar__teks">{{ count($semuaTempat) }} tempat di {{ $jumlahKecamatan }} kecamatan, dari istana Kesultanan Deli sampai deretan toko bika ambon. Buka satu jenis untuk melihat letaknya di peta 1930.</p>
            @foreach ($kategori as $k => $grup)
                @continue(! $grup['tempat'])
                <details class="bilah" data-kategori="{{ $k }}" @if ($loop->first) open @endif>
                    <summary>{{ $grup['label'] }} <span class="jumlah">{{ count($grup['tempat']) }}</span><span class="plus" aria-hidden="true"></span></summary>
                    <div class="bilah__isi">
                        @foreach ($grup['tempat'] as $t)
                            <a class="baris-tempat" href="{{ route('tempat', $t['slug']) }}" data-sorot="{{ $t['slug'] }}" data-label="{{ $t['nama'] }}" data-sub="{{ $t['kecamatan'] }} · {{ $t['jam'] }}">
                                <strong>{{ $t['nama'] }}</strong>
                                <x-harga :tempat="$t" />
                                <span class="redup">{{ $t['kecamatan'] }} · {{ $t['jam'] }}@if (! empty($t['jam_contoh'])) <span class="contoh">contoh</span>@endif</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </section>
    </div>

    <div class="panggung__layar">
        <img class="layar__gambar aktif" data-layar="peta" src="{{ asset('images/foto/peta-medan-1930.webp') }}" width="1422" height="1600" fetchpriority="high"
             alt="Peta Kota Medan tahun 1930 berskala 1:20.000, dengan Esplanade, stasiun, Sungai Deli, dan Istana Sultan Deli bernomor 15.">
        @foreach ($semuaTempat as $t)
            @if ($t['foto'])
                @php [$w, $h] = \App\Support\Foto::ukuran($t['foto']); @endphp
                <img class="layar__gambar" data-layar="{{ $t['slug'] }}" src="{{ asset('images/foto/'.$t['foto']) }}" width="{{ $w }}" height="{{ $h }}" loading="lazy" decoding="async" alt="{{ $t['nama'] }}">
            @endif
        @endforeach
        <svg class="layar__peta" data-layar-peta viewBox="0 0 1422 1600" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            @foreach ($semuaTempat as $t)
                @php $p = Katalog::posisi1930($t); @endphp
                @continue(! $p)
                <g data-titik="{{ $t['slug'] }}" data-kategori="{{ $t['kategori'] }}">
                    <circle class="titik" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="16"/>
                    @if (in_array($t['slug'], $berlabel, true))
                        @php $lbl = ['masjid-raya-al-mashun' => 'Masjid Raya', 'tjong-a-fie-mansion' => 'Tjong A Fie', 'kuil-shri-mariamman' => 'Kampung Madras'][$t['slug']] ?? $t['nama']; @endphp
                        @php $lw = mb_strlen($lbl) * 20.5 + 30; @endphp
                        @php $kiri = $t['slug'] === 'istana-maimun' || $p['x'] > 980; $dy = $t['slug'] === 'kuil-shri-mariamman' ? -58 : 0; @endphp
                        <g class="titik-label" transform="translate({{ $kiri ? $p['x'] - 26 - $lw : $p['x'] + 26 }} {{ $p['y'] - 26 + $dy }})">
                            <rect width="{{ $lw }}" height="52"/>
                            <text x="14" y="37">{{ $lbl }}</text>
                        </g>
                    @endif
                </g>
            @endforeach
        </svg>
        <div class="layar__bilah">
            <span data-bilah-kiri>Peta Medan, 1930</span>
            <span data-bilah-kanan>Skala 1:20.000 · kini 21 kecamatan</span>
        </div>
    </div>
</div>

<section class="benang" data-benang aria-labelledby="judul-benang">
    <figure class="benang__plat">
        <x-foto berkas="peta-medan-1930-kota-lama.jpg" alt="Potongan peta Medan 1930: Esplanade di utara, Istana Sultan Deli di selatan, dihubungkan Paleisweg." />
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
                    @php $lw = mb_strlen($x['e']['nama']) * 1.75 + 3; @endphp
                    @php $kiri = $x['p']['x'] > 62; @endphp
                    <g class="nama-simpul" transform="translate({{ $kiri ? $x['p']['x'] - 3 - $lw : $x['p']['x'] + 3 }} {{ $x['p']['y'] - 2.6 }})">
                        <rect width="{{ $lw }}" height="5.2"/>
                        <text x="1.5" y="3.9">{{ $x['e']['nama'] }}</text>
                    </g>
                @endforeach
            </svg>
        @endforeach
        <figcaption>Kota lama di peta 1930</figcaption>
    </figure>
    <div class="benang__sisi">
        <div class="benang__tengah">
            <h2 class="judul" id="judul-benang" data-muncul>Benang merah</h2>
            <div class="tab-benang" role="tablist" aria-label="Pasangan tempat">
                @foreach ($benang as $i => $b)
                    <button type="button" role="tab" id="tab-benang-{{ $i }}" aria-controls="panel-benang-{{ $i }}" aria-selected="{{ $i ? 'false' : 'true' }}" tabindex="{{ $i ? '-1' : '0' }}" data-tab-benang="{{ $i }}">{{ $b['dari']['nama'] }} &amp; {{ \Illuminate\Support\Str::of($b['ke']['nama'])->replace(' Al-Mashun', '') }}</button>
                @endforeach
            </div>
            @foreach ($benang as $i => $b)
                <div role="tabpanel" id="panel-benang-{{ $i }}" aria-labelledby="tab-benang-{{ $i }}" data-panel-benang="{{ $i }}" @if ($i) hidden @endif>
                    <p class="benang__pasangan">Apa hubungan {{ $b['dari']['nama'] }} dengan {{ $b['ke']['nama'] }}?</p>
                    <ol class="benang__langkah">
                        @foreach ($b['langkah'] as $l)
                            <li>{{ $l['kalimat'] }}</li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>
        <div class="benang__aksi">
            @foreach ($benang as $i => $b)
                <a href="{{ route('jelajah', ['dari' => $b['dari']['slug'], 'ke' => $b['ke']['slug']]) }}" data-panel-benang="{{ $i }}" @if ($i) hidden @endif>Lihat di jelajah graf</a>
            @endforeach
            <a href="{{ route('jelajah') }}">Pilih dua tempat sendiri</a>
        </div>
    </div>
</section>

<section class="jalur-pin" data-jalur-pin style="--n: {{ count($jalur) }}" aria-labelledby="judul-jalur">
    <div class="jalur-pin__panggung">
        <div class="jalur-pin__kiri">
            <div>
                <h2 id="judul-jalur">Jalur tematik</h2>
                <p>Rute jalan kaki dan becak motor yang disusun dari hubungan di graf, dengan jarak dihitung dari letak setiap tempat.</p>
            </div>
            <nav class="jalur-pin__indeks" aria-label="Jalur tematik">
                @foreach (array_values($jalur) as $i => $j)
                    <a href="{{ route('jalur.detail', $j['slug']) }}" data-jalur="{{ $i }}" @if (! $i) aria-current="true" @endif>
                        <b>{{ $j['nama'] }}</b>
                        <span>{{ count($j['singgah']) }} tempat · {{ number_format($j['meter'] / 1000, 1, ',', '.') }} km · {{ $fmtMenit($j['menit']) }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
        <div class="jalur-pin__foto">
            @foreach (array_values($jalur) as $i => $j)
                <figure data-jalur-foto="{{ $i }}" @class(['aktif' => ! $i])>
                    <x-foto :berkas="$j['foto']" :alt="$j['nama'].': '.$j['ringkas']" />
                    <figcaption>{{ $j['ringkas'] }}</figcaption>
                </figure>
            @endforeach
        </div>
    </div>
    <div class="jalur-pin__langkah" aria-hidden="true"></div>
</section>

<section aria-labelledby="judul-tiket">
    @php $bt = array_values($bertiket); @endphp
    <div class="tiket-dua__kepala">
        <h2 class="judul" id="judul-tiket" data-muncul>Tiket masuk tanpa antre</h2>
        <p data-muncul style="--i:1">Pilih tanggal dan jumlah, bayar dengan QRIS atau virtual account, lalu tunjukkan e-tiket ber-QR di loket.
            @if (isset($bt[2])) Juga bisa: <a class="tautan" href="{{ route('tempat', $bt[2]['slug']) }}">{{ $bt[2]['nama'] }}</a>.@endif</p>
    </div>
    <div class="tiket-dua">
        @foreach (array_slice($bt, 0, 2) as $i => $t)
            <article class="bidang {{ $i ? 'bidang--dua' : 'bidang--satu' }}">
                <h3 class="bidang__judul">{{ $t['nama'] }}</h3>
                <p>{{ $t['ringkas'] }}</p>
                <div class="bidang__bingkai"><x-foto :berkas="$t['foto']" :alt="$t['nama']" :kecil="true" class="bidang__foto geser-pelan" /></div>
                <div class="bidang__bawah">
                    @php $jenis = array_keys($t['tiket']); @endphp
                    <div><p class="bidang__harga">{{ Katalog::rupiah($t['tiket'][$jenis[0]]) }}</p><p class="bidang__rincian">{{ mb_strtolower($jenis[0]) }} · {{ mb_strtolower($jenis[1]) }} {{ Katalog::rupiah($t['tiket'][$jenis[1]]) }} <span class="contoh">harga contoh</span></p></div>
                    <a class="tombol {{ $i ? 'tombol--hijau' : '' }}" href="{{ route('tiket.pilih', $t['slug']) }}">Pesan tiket {{ $t['nama'] }}</a>
                </div>
            </article>
        @endforeach
    </div>
</section>

<section class="slip-seksi" aria-labelledby="judul-rute">
    <div class="slip-seksi__kiri">
        <h2 id="judul-rute" data-muncul>Setengah hari di Medan lama</h2>
        <p data-muncul style="--i:1">Perencana Rute menyusun urutan singgah dari minat, durasi, dan cara berpindahmu, lalu menghitung jarak dan ongkos becaknya. Setiap tempat yang kamu datangi memberi satu stempel di Paspor Digital.</p>
        <a class="tombol" data-muncul style="--i:2" href="{{ route('rencana', ['minat' => ['sejarah', 'religi', 'kuliner'], 'durasi' => 4, 'moda' => ['jalan', 'becak'], 'mulai' => 'istana-maimun']) }}">Susun rute sendiri</a>
    </div>
    <div class="slip" data-muncul style="--i:1" aria-label="Contoh rencana perjalanan">
        @php $pagi = collect($rute['singgah'])->filter(fn ($s) => $s['mulai'] < 11 * 60); @endphp
        @php $siang = collect($rute['singgah'])->filter(fn ($s) => $s['mulai'] >= 11 * 60); @endphp
        @foreach (['Pagi' => $pagi, 'Menjelang siang' => $siang] as $label => $grup)
            @continue($grup->isEmpty())
            <h3>{{ $label }}</h3>
            @foreach ($grup as $s)
                @if ($s['pindah'])
                    <div class="slip__baris redup"><span>{{ $s['pindah']['moda'] }} {{ number_format($s['pindah']['meter'], 0, ',', '.') }} m</span><span class="titik-titik"></span><span class="kanan">{{ $s['pindah']['menit'] }} mnt</span></div>
                @endif
                <div class="slip__baris"><span>{{ Katalog::jamTeks($s['mulai']) }} {{ $s['tempat']['nama'] }}</span><span class="titik-titik"></span><span class="kanan">{{ $s['tempat']['singgah'] }} mnt</span></div>
            @endforeach
        @endforeach
        <div class="slip__akhir"><span>{{ count($rute['singgah']) }} tempat, {{ count($rute['singgah']) }} stempel</span><span>{{ Katalog::rupiah($rute['biaya']) }}</span></div>
        <p class="kecil redup" style="margin-top:12px">Perkiraan tiket masuk dan becak per orang, selesai pukul {{ Katalog::jamTeks($rute['selesai']) }}.</p>
    </div>
</section>

<section class="kunjungan" aria-labelledby="judul-peta">
    <div class="kunjungan__peta" data-peta data-peta-mode="ringkas" data-pusat="istana-maimun"
         data-tempat="{{ json_encode($petaJson) }}">
        <p class="peta-diam">Peta interaktif dimuat di sini.</p>
    </div>
    <div class="kunjungan__kartu">
        <h2 id="judul-peta">Semua tempat di satu peta</h2>
        <p>Mulai dari Istana Maimun. Yang paling dekat:</p>
        <div class="kunjungan__daftar">
            @foreach ($dekatIstana as $t)
                <a href="{{ route('tempat', $t['slug']) }}"><b>{{ $t['nama'] }}</b><span>{{ number_format($t['jarak'], 0, ',', '.') }} m</span></a>
            @endforeach
        </div>
        <a class="tombol tombol--hijau" href="{{ route('peta') }}">Buka peta wisata</a>
    </div>
</section>

<section class="tanya-jawab" aria-labelledby="judul-tanya">
    <div class="tanya-jawab__kiri">
        <h2 id="judul-tanya" data-muncul>Sebelum berangkat</h2>
        <div class="fakta-singkat" data-muncul style="--i:1">
            <p>Tiket dibayar lewat QRIS, virtual account, atau dompet digital.</p>
            <p>Check-in hanya diterima di lokasi, dalam radius 60–150 m.</p>
            <p>Masjid dan rumah ibadah dikunjungi di luar waktu ibadah.</p>
        </div>
        <div class="lipat">
            <details>
                <summary>Bagaimana cara memesan tiket masuk?</summary>
                <p>Buka halaman tempat yang bertiket, pilih tanggal dan jumlah tiket, isi data pemesan, lalu bayar. E-tiket ber-QR langsung terbit di menu Tiket saya dan bisa ditunjukkan di loket.</p>
            </details>
            <details>
                <summary>Apakah e-tiket bisa dibuka tanpa sinyal?</summary>
                <p>Bisa. Dari halaman e-tiket, pilih <strong>Simpan PDF</strong>. Berkas itu tersimpan di ponselmu dan QR-nya tetap bisa dipindai tanpa internet.</p>
            </details>
            <details>
                <summary>Kenapa check-in saya ditolak?</summary>
                <p>Check-in dihitung dari lokasi GPS ponselmu. Kamu harus berada dalam radius tempat itu, misalnya 100 m dari Istana Maimun. Di dalam gedung GPS sering meleset, jadi coba lagi di halaman atau dekat gerbang.</p>
            </details>
            <details>
                <summary>Poin dari Paspor Digital bisa ditukar apa?</summary>
                <p>Setiap check-in memberi 10 poin, dan badge tertentu memberi bonus. Poin ditukar dengan voucher yang disediakan mitra UMKM; jumlah poinnya ditentukan masing-masing mitra.</p>
            </details>
            <details>
                <summary>Cerita tersedia dalam bahasa apa?</summary>
                <p>Saat ini cerita ditulis dalam bahasa Indonesia. Versi Inggris, Mandarin, dan Melayu sedang disiapkan kurator.</p>
            </details>
            <details>
                <summary>Dari mana foto dan fakta sejarahnya?</summary>
                <p>Foto berasal dari Wikimedia Commons dan arsip KITLV dengan lisensi terbuka. Setiap hubungan sejarah mencantumkan sumbernya di halaman tempat dan di <a class="tautan" href="{{ route('data') }}">data terbuka</a>.</p>
            </details>
        </div>
        <div class="tanya-jawab__aksi">
            <a class="tombol" href="{{ route('tanya') }}">Tanya asisten</a>
            <a class="tautan" href="{{ route('ketentuan') }}">Ketentuan pembelian</a>
        </div>
    </div>
    <div class="tanya-jawab__kanan">
        <x-foto berkas="becak-medan.jpg" alt="Becak motor Medan berwarna-warni terparkir di tepi jalan" class="tanya-jawab__tinggi" />
        <x-foto berkas="bika-ambon.jpg" alt="Dua potong bika ambon pandan di piring putih" class="tanya-jawab__persegi" />
    </div>
</section>
@endsection

@push('skrip')
    @include('partials.menu')
@endpush
