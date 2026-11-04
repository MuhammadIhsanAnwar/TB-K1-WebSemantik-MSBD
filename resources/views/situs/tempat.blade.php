@extends('layouts.situs')
@php
    use App\Support\Katalog;
    use App\Http\Controllers\SitusController;
    $t = $e;
    $u = session('pengguna');
    $buka = Katalog::bukaSekarang($t);
    $gagal = session('checkin_gagal');
    $kategori = Katalog::KATEGORI[$t['kategori']];
    $posisi = Katalog::posisi1930($t);
    $kreditFoto = collect([$t['foto'] ?? null, $t['foto_lama'] ?? null, $t['foto_arsip'] ?? null])->filter()->map(fn ($f) => Katalog::kredit($f))->filter();
    $tingkatRamai = $ramai >= 8 ? 'Ramai' : ($ramai >= 3 ? 'Sedang' : ($ramai > 0 ? 'Lengang' : null));
@endphp
@section('judul', $t['nama'])
@section('deskripsi', $t['ringkas'])
@if ($t['foto']) @section('foto-kaki', $t['foto']) @endif

@push('kepala')
    <script type="application/ld+json">{!! json_encode(\App\Support\Semantik::jsonldTempat($t), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('isi')
<div class="detail">
    <article class="detail__baca">
        <header class="detail__kepala">
            <nav class="remah" aria-label="Remah roti">
                <a href="{{ route('beranda') }}">Medan</a><span aria-hidden="true">›</span>
                <span>{{ $t['kecamatan'] }}</span><span aria-hidden="true">›</span>
                <span>{{ $kategori }}</span>
            </nav>
            <h1>{{ $t['nama'] }}</h1>
            <p class="detail__ringkas">{{ $t['ringkas'] }}</p>
            <div class="detail__tanda">
                <x-kelas jenis="tempat" :label="$kategori" />
                <span class="kecil">{{ $t['kelurahan'] }}, {{ $t['kecamatan'] }}</span>
                <span class="kecil" style="font-weight:600;color:{{ $buka ? 'var(--success)' : 'var(--danger)' }}">{{ $buka ? 'Buka sekarang' : 'Tutup sekarang' }}</span>
            </div>
            <div class="detail__aksi">
                @if ($u)
                    <form method="post" action="{{ route('checkin', $t['slug']) }}" data-checkin>
                        @csrf
                        <input type="hidden" name="lat"><input type="hidden" name="lng"><input type="hidden" name="akurasi">
                        <button class="tombol {{ $t['tiket'] ? 'tombol--hijau' : '' }}" type="submit">Check-in di sini</button>
                        <p class="kecil" data-pesan-checkin role="status" aria-live="polite" style="margin-top:8px"></p>
                    </form>
                @else
                    <a class="tombol {{ $t['tiket'] ? 'tombol--hijau' : '' }}" href="{{ route('masuk', ['kembali' => route('tempat', $t['slug'], false)]) }}">Masuk untuk check-in</a>
                @endif
                <a class="tombol tombol--garis" href="{{ route('rencana', ['minat' => ['sejarah', 'religi', 'kuliner'], 'durasi' => 4, 'moda' => ['jalan', 'becak'], 'mulai' => $t['slug']]) }}">Rute dari sini</a>
            </div>
            @if ($gagal)
                <div class="pesan pesan--galat" role="alert" style="margin-top:20px">
                    @if (! empty($gagal['sudah']))
                        Kamu sudah check-in di {{ $t['nama'] }} hari ini. Stempelnya sudah ada di paspormu.
                    @else
                        Kamu berada {{ number_format($gagal['jarak'], 0, ',', '.') }} m dari {{ $t['nama'] }}. Check-in diterima dalam radius {{ $gagal['radius'] }} m, jadi mendekatlah ke gerbang lalu coba lagi.
                    @endif
                </div>
            @endif
            @error('lat')<p class="isian__galat" role="alert" style="margin-top:12px">{{ $message }}</p>@enderror
        </header>

        @if ($t['tiket'])
            <section class="tiket-blok" aria-labelledby="judul-tiket">
                <div>
                    <h2 id="judul-tiket">Tiket masuk</h2>
                    <p class="tiket-blok__harga">{{ Katalog::rupiah(Katalog::hargaTermurah($t)) }}</p>
                    <p class="tiket-blok__rincian">
                        @foreach ($t['tiket'] as $jenis => $harga){{ $jenis }} {{ Katalog::rupiah($harga) }}@if (! $loop->last) · @endif @endforeach
                        @if (! empty($t['harga_contoh'])) <span class="contoh" style="color:inherit">harga contoh</span>@endif
                    </p>
                </div>
                <a class="tombol" href="{{ route('tiket.pilih', $t['slug']) }}">Pesan tiket</a>
            </section>
        @endif

        <section class="detail__seksi" aria-labelledby="judul-info">
            <h2 id="judul-info">Info kunjungan</h2>
            <dl class="fakta">
                <dt>Alamat</dt><dd>{{ $t['alamat'] }}, {{ $t['kelurahan'] }}, {{ $t['kecamatan'] }}</dd>
                <dt>Jam buka</dt><dd>{{ $t['jam'] }} @if (! empty($t['jam_contoh']))<span class="contoh">contoh</span>@endif</dd>
                @if ($t['fasilitas'])<dt>Fasilitas</dt><dd>{{ implode(', ', $t['fasilitas']) }}</dd>@endif
                <dt>Cara ke sana</dt><dd>{{ implode('. ', $t['cara']) }}.</dd>
                <dt>Check-in</dt><dd>Dalam radius {{ $t['radius_checkin'] }} m dari titik tempat ini</dd>
            </dl>
            <div class="ramai" style="margin-top:28px">
                <div class="ramai__judul"><span>Ramai sekarang?</span><span>{{ $tingkatRamai ?? 'Belum ada data' }}</span></div>
                <div class="ramai__trek" aria-hidden="true"><div class="ramai__isi" style="width: {{ min(100, $ramai * 10) }}%"></div></div>
                <p class="kecil">{{ $ramai }} check-in lewat MedanVisit dalam 3 jam terakhir.</p>
            </div>
        </section>

        <section class="detail__seksi cerita" aria-labelledby="judul-cerita">
            <h2 id="judul-cerita">Cerita</h2>
            @foreach ($t['cerita'] as $p)
                <p>{{ $p }}</p>
            @endforeach
            @if (! empty($t['foto_arsip']))
                <figure class="cerita__arsip">
                    <x-foto :berkas="$t['foto_arsip']" :alt="$t['foto_arsip_keterangan']" />
                    <figcaption>{{ $t['foto_arsip_keterangan'] }}</figcaption>
                </figure>
            @endif
            <p class="kecil redup" style="margin-top:20px">Cerita dalam bahasa Inggris, Mandarin, dan Melayu sedang disiapkan kurator.</p>
        </section>

        @if ($posisi && $t['foto'])
            @php
                // potongan 3:2 dari peta 1422×1600 yang diperbesar 2,2 kali; pusat potongan dijepit supaya tepi peta tidak terlihat
                $px = $posisi['x'] / 14.22; $py = $posisi['y'] / 16;
                $cx = max(22.73, min(77.27, $px)); $cy = max(13.47, min(86.53, $py));
                $kreditPeta = Katalog::kredit('peta-medan-1930.webp');
            @endphp
            <section class="detail__seksi" aria-labelledby="judul-peta-lama">
                <h2 id="judul-peta-lama">Di peta Medan 1930</h2>
                <div class="peta-lama" style="--px: {{ round($cx, 2) }}; --py: {{ round($cy, 2) }}; --dx: {{ round(($px - $cx) * 2.2, 2) }}; --dy: {{ round(($py - $cy) * 3.713, 2) }}">
                    <img src="{{ asset('images/foto/peta-medan-1930.webp') }}" width="1422" height="1600" loading="lazy" decoding="async" alt="Potongan peta Medan tahun 1930 di sekitar {{ $t['nama'] }}">
                    <span class="peta-lama__titik" aria-hidden="true"></span>
                </div>
                <p class="peta-lama__ket">Lingkaran kuning menandai letak {{ $t['nama'] }} hari ini, di atas peta kota tahun 1930.@if (! empty($t['koordinat_perkiraan'])) Titiknya masih perkiraan.@endif @if ($kreditPeta) Peta: {{ $kreditPeta }}.@endif</p>
            </section>
        @endif

        <section class="detail__seksi" aria-labelledby="judul-terhubung">
            <h2 id="judul-terhubung">Terhubung dengan</h2>
            @include('partials.terhubung', ['pusat' => $t['slug']])
            <p style="margin-top:20px"><a class="tautan" href="{{ route('jelajah', ['dari' => $t['slug'], 'ke' => $dekat[0]['slug'] ?? 'istana-maimun']) }}">Buka di jelajah graf</a></p>
        </section>

        <section class="detail__seksi" aria-labelledby="judul-dekat">
            <h2 id="judul-dekat">Dekat dari sini</h2>
            <div class="rekomendasi">
                @foreach ($dekat as $d)
                    @php $rantai = Katalog::benangMerah($t['slug'], $d['slug']); @endphp
                    <a href="{{ route('tempat', $d['slug']) }}">
                        @if ($d['foto'])<x-foto :berkas="$d['foto']" :alt="$d['nama']" :kecil="true" :segera="true" />@else<span class="tanpa-foto" aria-hidden="true"></span>@endif
                        <span>
                            <b>{{ $d['nama'] }}</b>
                            <span class="redup">{{ number_format($d['jarak'], 0, ',', '.') }} m, jalan kaki sekitar {{ max(1, round($d['jarak'] / 75)) }} menit</span>
                            @if ($rantai && count($rantai) <= 3 && $rantai[0]['relasi'][1] !== 'beradaDi')
                                <span class="alasan">{{ count($rantai) === 1 ? $rantai[0]['kalimat'] : end($rantai)['kalimat'] }}</span>
                            @else
                                <span class="alasan">{{ Katalog::KATEGORI[$d['kategori']] }}, {{ $d['kecamatan'] }}</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="detail__seksi" id="ulasan" aria-labelledby="judul-ulasan">
            <h2 id="judul-ulasan">Ulasan</h2>
            @if ($ulasan)
                <div class="ulasan-daftar">
                    @foreach ($ulasan as $ul)
                        <article>
                            <p><b>{{ $ul['nama'] }}</b> <x-bintang :n="$ul['bintang']" /></p>
                            <p>{{ $ul['teks'] }}</p>
                            <p class="kecil redup">{{ \Illuminate\Support\Carbon::parse($ul['waktu'])->locale('id')->translatedFormat('j F Y') }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="kosong"><p>Belum ada ulasan untuk {{ $t['nama'] }}. Ulasan pertama akan tampil di sini setelah diperiksa kurator.</p></div>
            @endif

            @if ($u)
                <form class="form-ulasan" method="post" action="{{ route('ulasan.tulis', $t['slug']) }}" novalidate>
                    @csrf
                    <fieldset class="isian">
                        <legend>Bintang</legend>
                        <div class="pilih-banyak" style="margin-top:8px">
                            @for ($b = 1; $b <= 5; $b++)
                                <label><input type="radio" name="bintang" value="{{ $b }}" @checked(old('bintang') == $b)>{{ $b }}</label>
                            @endfor
                        </div>
                        @error('bintang')<p class="isian__galat">{{ $message }}</p>@enderror
                    </fieldset>
                    <div class="isian">
                        <label for="teks-ulasan">Ceritakan kunjunganmu</label>
                        <textarea id="teks-ulasan" name="teks" maxlength="600" @error('teks') aria-invalid="true" aria-describedby="galat-teks" @enderror>{{ old('teks') }}</textarea>
                        @error('teks')<p class="isian__galat" id="galat-teks">{{ $message }}</p>@enderror
                    </div>
                    <button class="tombol tombol--hijau" type="submit" style="margin-top:20px">Kirim ulasan</button>
                </form>
            @else
                <p style="margin-top:20px"><a class="tautan" href="{{ route('masuk', ['kembali' => route('tempat', $t['slug'], false).'#ulasan']) }}">Masuk untuk menulis ulasan</a></p>
            @endif
        </section>

        @if ($kreditFoto->isNotEmpty())
            <p class="kecil redup detail__seksi">Foto: {{ $kreditFoto->implode(' · ') }}</p>
        @endif
    </article>

    <div class="detail__layar">
        @if (! empty($t['foto_lama']))
            <div class="lorong" data-lorong>
                <x-foto :berkas="$t['foto']" :alt="$t['nama'].' kini'" :utama="true" style="object-position: {{ $t['foto_posisi'] ?? '50% 50%' }}" />
                <x-foto :berkas="$t['foto_lama']" :alt="$t['nama'].', '.$t['tahun_foto_lama']" class="lorong__lama" :utama="true" style="object-position: {{ $t['foto_lama_posisi'] ?? '50% 50%' }}" />
                <span class="lorong__label lorong__label--kiri">Dulu, {{ $t['tahun_foto_lama'] }}</span>
                <span class="lorong__label lorong__label--kanan">Kini</span>
                <label class="sr-only" for="geser-lorong">Geser untuk membandingkan foto dulu dan kini</label>
                <input class="lorong__geser" id="geser-lorong" type="range" min="0" max="100" value="50">
                <span class="lorong__pegangan" aria-hidden="true"></span>
            </div>
            <div class="detail__bilah"><span>Lorong Waktu: geser untuk membandingkan</span><span>{{ $t['kecamatan'] }}</span></div>
        @elseif ($t['foto'])
            <x-foto :berkas="$t['foto']" :alt="$t['foto_keterangan'] ?? $t['nama']" :utama="true" class="geser-pelan" style="object-position: {{ $t['foto_posisi'] ?? '50% 50%' }}" />
            <div class="detail__bilah"><span>{{ $t['foto_keterangan'] ?? $t['nama'] }}</span><span>{{ $t['kecamatan'] }}</span></div>
        @elseif ($posisi)
            <img src="{{ asset('images/foto/peta-medan-1930.webp') }}" width="1422" height="1600" fetchpriority="high" alt="Letak {{ $t['nama'] }} di peta Medan 1930">
            <svg class="layar__peta" viewBox="0 0 1422 1600" preserveAspectRatio="xMidYMid slice" aria-hidden="true" style="position:absolute;inset:0">
                <circle cx="{{ $posisi['x'] }}" cy="{{ $posisi['y'] }}" r="60" fill="none" stroke="#e3a51c" stroke-width="10"/>
                <circle class="titik" cx="{{ $posisi['x'] }}" cy="{{ $posisi['y'] }}" r="20"/>
            </svg>
            <div class="detail__bilah"><span>Letaknya di peta Medan 1930</span><span>{{ $t['kecamatan'] }}</span></div>
        @endif
    </div>
</div>
@endsection
