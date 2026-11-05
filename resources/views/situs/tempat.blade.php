@extends('layouts.situs')
@php
    use App\Support\Foto;
    use App\Support\Katalog;
    $t = $e;
    $u = session('pengguna');
    $buka = Katalog::bukaSekarang($t);
    $gagal = session('checkin_gagal');
    $kategori = Katalog::KATEGORI[$t['kategori']];
    $posisi = Katalog::posisi1930($t);
    $kreditFoto = collect([$t['foto'] ?? null, $t['foto_lama'] ?? null, $t['foto_arsip'] ?? null])->filter()->map(fn ($f) => Katalog::kredit($f))->filter();
    $tingkatRamai = $ramai >= 8 ? 'Ramai' : ($ramai >= 3 ? 'Sedang' : ($ramai > 0 ? 'Lengang' : null));
    $kreditUtama = ! empty($t['foto']) ? Katalog::kredit($t['foto']) : null;
@endphp
@section('judul', $t['nama'])
@section('deskripsi', $t['ringkas'])
@if ($t['foto']) @section('foto-kaki', $t['foto']) @endif

@push('kepala')
    <script type="application/ld+json">{!! json_encode(\App\Support\Semantik::jsonldTempat($t), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('isi')
<section class="mv-tempat-atas" aria-labelledby="judul-tempat-detail">
    <div class="mv-wadah">
        <nav class="mv-remah" aria-label="Jejak halaman">
            <a href="{{ route('beranda') }}">Beranda</a>
            <span aria-hidden="true">›</span>
            <a href="{{ route('peta') }}">Tempat &amp; Peta</a>
            <span aria-hidden="true">›</span>
            <span>{{ $kategori }}</span>
        </nav>

        <div class="mv-tempat-kepala">
            <div class="mv-tempat-kepala__teks">
                <h1 id="judul-tempat-detail">{{ $t['nama'] }}</h1>
                <p class="mv-tempat-kepala__ringkas">{{ $t['ringkas'] }}</p>
                <div class="mv-tempat-tanda">
                    <x-kelas jenis="tempat" :label="$kategori" />
                    <span class="kecil">{{ implode(', ', array_filter([$t['kelurahan'], $t['kecamatan']])) }}</span>
                    <span class="mv-lencana-jam {{ $buka ? 'mv-lencana-jam--buka' : 'mv-lencana-jam--tutup' }}">{{ $buka ? 'Buka sekarang' : 'Tutup sekarang' }}</span>
                </div>
            </div>

            <div class="mv-tempat-kepala__meta">
                @if ($t['tiket'])
                    <a class="mv-btn mv-btn--utama" href="{{ route('tiket.pilih', $t['slug']) }}">Pesan tiket mulai {{ Katalog::rupiah(Katalog::hargaTermurah($t)) }}</a>
                @endif
                <a class="mv-btn mv-btn--garis" href="{{ route('rencana', ['minat' => ['sejarah', 'religi', 'kuliner'], 'durasi' => 4, 'moda' => ['jalan', 'becak'], 'mulai' => $t['slug']]) }}">Rute dari sini</a>
            </div>
        </div>

        @if ($gagal)
            <div class="pesan pesan--galat" role="alert" style="margin-top:20px">
                @if (! empty($gagal['sudah']))
                    Kamu sudah check-in di {{ $t['nama'] }} hari ini. Stempelnya sudah tersimpan di Paspor Digitalmu.
                @else
                    Jarakmu saat ini {{ number_format($gagal['jarak'], 0, ',', '.') }} m dari {{ $t['nama'] }}. Check-in diterima dalam radius {{ $gagal['radius'] }} m, jadi mendekatlah ke area pintu masuk lalu coba lagi.
                @endif
            </div>
        @endif
        @error('lat')<p class="isian__galat" role="alert" style="margin-top:12px">{{ $message }}</p>@enderror

        <div class="mv-tempat-media">
            @if (! empty($t['foto_lama']))
                <div class="lorong" data-lorong>
                    <x-foto :berkas="$t['foto']" :alt="$t['nama'].' masa kini'" :utama="true" style="object-position: {{ $t['foto_posisi'] ?? '50% 50%' }}" />
                    <x-foto :berkas="$t['foto_lama']" :alt="$t['nama'].' tahun '.$t['tahun_foto_lama']" class="lorong__lama" :utama="true" style="object-position: {{ $t['foto_lama_posisi'] ?? '50% 50%' }}" />
                    <span class="lorong__label lorong__label--kiri">Dulu, {{ $t['tahun_foto_lama'] }}</span>
                    <span class="lorong__label lorong__label--kanan">Kini</span>
                    <label class="sr-only" for="geser-lorong">Geser untuk membandingkan foto dulu dan kini</label>
                    <input class="lorong__geser" id="geser-lorong" type="range" min="0" max="100" value="50">
                    <span class="lorong__pegangan" aria-hidden="true"></span>
                </div>
                <div class="mv-tempat-media__bilah">
                    <span>Lorong Waktu: geser pengatur di tengah foto untuk membandingkan wajah {{ $t['tahun_foto_lama'] }} dan masa kini</span>
                    <button type="button" class="mv-perbesar-btn" data-buka-lightbox data-src="{{ Foto::url($t['foto']) }}" data-judul="{{ $t['nama'] }}" data-kredit="{{ $kreditUtama ? 'Foto: '.$kreditUtama : '' }}">Perbesar foto</button>
                </div>
            @elseif ($t['foto'])
                <x-foto :berkas="$t['foto']" :alt="$t['foto_keterangan'] ?? $t['nama']" :utama="true" style="object-position: {{ $t['foto_posisi'] ?? '50% 50%' }}" />
                <div class="mv-tempat-media__bilah">
                    <span>{{ $t['foto_keterangan'] ?? $t['nama'] }}, {{ $t['kecamatan'] }}</span>
                    <button type="button" class="mv-perbesar-btn" data-buka-lightbox data-src="{{ Foto::url($t['foto']) }}" data-judul="{{ $t['nama'] }}" data-kredit="{{ $kreditUtama ? 'Foto: '.$kreditUtama : '' }}">Perbesar foto</button>
                </div>
            @elseif ($posisi)
                <img src="{{ asset('images/foto/peta-medan-1930.webp') }}" width="1422" height="1600" fetchpriority="high" alt="Letak {{ $t['nama'] }} pada peta Kota Medan tahun 1930">
                <div class="mv-tempat-media__bilah">
                    <span>Letak kawasan {{ $t['nama'] }} pada peta Medan tahun 1930</span>
                    <span>{{ $t['kecamatan'] }}</span>
                </div>
            @endif
        </div>
    </div>
</section>

<nav class="mv-lompat-seksi tidak-dicetak" data-ui aria-label="Lompat ke bagian halaman">
    <div class="mv-wadah mv-lompat-seksi__dalam">
        <a href="#info">Info kunjungan</a>
        <a href="#cerita">Cerita sejarah</a>
        @if ($posisi && $t['foto'])
            <a href="#peta-1930">Peta 1930</a>
        @endif
        <a href="#terhubung">Terhubung dengan</a>
        <a href="#dekat">Dekat dari sini</a>
        <a href="#ulasan">Ulasan</a>
    </div>
</nav>

<div class="mv-wadah mv-tempat-isi">
    <article class="mv-tempat-kolom">
        <section class="mv-seksi-detail" id="info" aria-labelledby="judul-info">
            <h2 id="judul-info">Info kunjungan</h2>
            <dl class="mv-fakta-grid">
                <div class="mv-fakta-kotak">
                    <dt>Alamat lengkap</dt>
                    <dd>{{ implode(', ', array_filter([$t['alamat'], $t['kelurahan'], $t['kecamatan']])) }}</dd>
                </div>
                <div class="mv-fakta-kotak">
                    <dt>Jam operasional</dt>
                    <dd>{{ $t['jam'] }} @if (! empty($t['jam_contoh']))<span class="contoh">contoh</span>@endif</dd>
                </div>
                @if ($t['fasilitas'])
                    <div class="mv-fakta-kotak">
                        <dt>Fasilitas tersedia</dt>
                        <dd>{{ implode(', ', $t['fasilitas']) }}</dd>
                    </div>
                @endif
                <div class="mv-fakta-kotak">
                    <dt>Cara menuju lokasi</dt>
                    <dd>{{ implode('. ', $t['cara']) }}.</dd>
                </div>
            </dl>

            <div class="ramai" style="margin-top:20px;padding:18px 20px;background:var(--mv-putih)">
                <div class="ramai__judul">
                    <span>Kepadatan pengunjung saat ini</span>
                    <span>{{ $tingkatRamai ?? 'Belum ada catatan jam ini' }}</span>
                </div>
                <div class="ramai__trek" aria-hidden="true">
                    <div class="ramai__isi" style="width: {{ min(100, $ramai * 10) }}%"></div>
                </div>
                <p class="kecil redup"><span class="nums">{{ $ramai }}</span> check-in tercatat lewat MedanVisit dalam 3 jam terakhir.</p>
            </div>
        </section>

        <section class="mv-seksi-detail cerita" id="cerita" aria-labelledby="judul-cerita">
            <h2 id="judul-cerita">Cerita tempat ini</h2>
            @foreach ($t['cerita'] as $p)
                <p>{{ $p }}</p>
            @endforeach
            @if (! empty($t['foto_arsip']))
                <figure class="cerita__arsip">
                    <x-foto :berkas="$t['foto_arsip']" :alt="$t['foto_arsip_keterangan']" />
                    <figcaption>{{ $t['foto_arsip_keterangan'] }}</figcaption>
                </figure>
            @endif
            <p class="kecil redup" style="margin-top:20px">Narasi bahasa Inggris, Mandarin, dan Melayu sedang disiapkan oleh kurator.</p>
        </section>

        @if ($posisi && $t['foto'])
            @php
                $px = $posisi['x'] / 14.22; $py = $posisi['y'] / 16;
                $cx = max(22.73, min(77.27, $px)); $cy = max(13.47, min(86.53, $py));
                $kreditPeta = Katalog::kredit('peta-medan-1930.webp');
            @endphp
            <section class="mv-seksi-detail" id="peta-1930" aria-labelledby="judul-peta-lama">
                <h2 id="judul-peta-lama">Di peta Medan 1930</h2>
                <div class="peta-lama" style="--px: {{ round($cx, 2) }}; --py: {{ round($cy, 2) }}; --dx: {{ round(($px - $cx) * 2.2, 2) }}; --dy: {{ round(($py - $cy) * 3.713, 2) }}">
                    <img src="{{ asset('images/foto/peta-medan-1930.webp') }}" width="1422" height="1600" loading="lazy" decoding="async" alt="Potongan peta Medan tahun 1930 di sekitar {{ $t['nama'] }}">
                    <span class="peta-lama__titik" aria-hidden="true"></span>
                </div>
                <p class="peta-lama__ket">Lingkaran kuning menandai letak {{ $t['nama'] }} hari ini di atas peta kota tahun 1930.@if (! empty($t['koordinat_perkiraan'])) Titiknya berupa perkiraan kawasan.@endif @if ($kreditPeta) Sumber peta: {{ $kreditPeta }}.@endif</p>
            </section>
        @endif

        <section class="mv-seksi-detail" id="terhubung" aria-labelledby="judul-terhubung">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:16px;flex-wrap:wrap;margin-bottom:18px">
                <h2 id="judul-terhubung" style="margin-bottom:0">Terhubung di jaringan cerita</h2>
                <a class="mv-tautan" href="{{ route('jelajah', ['dari' => $t['slug'], 'ke' => $dekat[0]['slug'] ?? 'istana-maimun']) }}">Jelajahi graf penuh</a>
            </div>
            @include('partials.terhubung', ['pusat' => $t['slug']])
        </section>

        <section class="mv-seksi-detail" id="dekat" aria-labelledby="judul-dekat">
            <h2 id="judul-dekat">Dekat dari sini</h2>
            <div class="rekomendasi">
                @foreach ($dekat as $d)
                    @php $rantai = Katalog::benangMerah($t['slug'], $d['slug']); @endphp
                    <a href="{{ route('tempat', $d['slug']) }}" style="background:var(--mv-putih);padding:14px">
                        @if ($d['foto'])<x-foto :berkas="$d['foto']" :alt="$d['nama']" :kecil="true" :segera="true" />@else<span class="tanpa-foto" aria-hidden="true"></span>@endif
                        <span>
                            <b>{{ $d['nama'] }}</b>
                            <span class="redup nums">{{ number_format($d['jarak'], 0, ',', '.') }} m, sekitar {{ max(1, round($d['jarak'] / 75)) }} menit jalan kaki</span>
                            @if ($rantai && count($rantai) <= 3 && $rantai[0]['relasi'][1] !== 'beradaDi')
                                <span class="alasan">{{ count($rantai) === 1 ? $rantai[0]['kalimat'] : end($rantai)['kalimat'] }}</span>
                            @else
                                <span class="alasan">{{ Katalog::KATEGORI[$d['kategori']] }} di {{ $d['kecamatan'] }}</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="mv-seksi-detail" id="ulasan" aria-labelledby="judul-ulasan">
            <h2 id="judul-ulasan">Ulasan pengunjung</h2>
            @if ($ulasan)
                <div class="ulasan-daftar">
                    @foreach ($ulasan as $ul)
                        <article style="background:var(--mv-putih);padding:18px 20px">
                            <p><b>{{ $ul['nama'] }}</b> <x-bintang :n="$ul['bintang']" /></p>
                            <p>{{ $ul['teks'] }}</p>
                            <p class="kecil redup">{{ \Illuminate\Support\Carbon::parse($ul['waktu'])->locale('id')->translatedFormat('j F Y') }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="kosong" style="background:var(--mv-putih);padding:22px 24px">
                    <p>Belum ada ulasan terverifikasi untuk {{ $t['nama'] }}. Ulasan pertama yang dikirim pengunjung akan tampil di sini setelah diperiksa kurator.</p>
                </div>
            @endif

            @if ($u)
                <form class="form-ulasan" method="post" action="{{ route('ulasan.tulis', $t['slug']) }}" novalidate style="margin-top:24px;background:var(--mv-putih);padding:24px">
                    @csrf
                    <fieldset class="isian">
                        <legend>Penilaian kunjunganmu</legend>
                        <div class="pilih-banyak" style="margin-top:8px">
                            @for ($b = 1; $b <= 5; $b++)
                                <label><input type="radio" name="bintang" value="{{ $b }}" @checked(old('bintang') == $b)>{{ $b }} bintang</label>
                            @endfor
                        </div>
                        @error('bintang')<p class="isian__galat">{{ $message }}</p>@enderror
                    </fieldset>
                    <div class="isian">
                        <label for="teks-ulasan">Ceritakan pengalamanmu di sini</label>
                        <textarea id="teks-ulasan" name="teks" maxlength="600" placeholder="Tips waktu berkunjung, suasana, atau hal menarik yang kamu temukan…" @error('teks') aria-invalid="true" aria-describedby="galat-teks" @enderror>{{ old('teks') }}</textarea>
                        @error('teks')<p class="isian__galat" id="galat-teks">{{ $message }}</p>@enderror
                    </div>
                    <button class="mv-btn mv-btn--hijau" type="submit" style="margin-top:18px">Kirim ulasan</button>
                </form>
            @else
                <p style="margin-top:18px">
                    <a class="mv-tautan" href="{{ route('masuk', ['kembali' => route('tempat', $t['slug'], false).'#ulasan']) }}">Masuk untuk menulis ulasan kunjungan</a>
                </p>
            @endif

            @if ($kreditFoto->isNotEmpty())
                <p class="kecil redup" style="margin-top:28px">Kredit foto: {{ $kreditFoto->implode('; ') }}</p>
            @endif
        </section>
    </article>

    <aside class="mv-kartu-samping" data-ui aria-labelledby="judul-samping-kunjungan">
        <h2 id="judul-samping-kunjungan">Tiket &amp; check-in</h2>

        @if ($t['tiket'])
            <div class="mv-kartu-samping__harga">
                <p class="mv-kartu-samping__angka">Mulai {{ Katalog::rupiah(Katalog::hargaTermurah($t)) }}</p>
                <div class="mv-tarif-samping">
                    @foreach ($t['tiket'] as $jenis => $harga)
                        <div class="mv-tarif-samping__baris"><span>{{ $jenis }}</span><b class="nums">{{ Katalog::rupiah($harga) }}</b></div>
                    @endforeach
                    @if (! empty($t['harga_contoh']))
                        <div><span class="contoh">harga contoh</span></div>
                    @endif
                </div>
            </div>
            <a class="mv-btn mv-btn--utama mv-btn--lebar" href="{{ route('tiket.pilih', $t['slug']) }}">Pesan tiket masuk</a>
        @else
            <div class="mv-kartu-samping__harga">
                <div class="mv-kartu-samping__angka">Tanpa tiket</div>
                <p class="kecil redup" style="margin-top:4px">Terbuka tanpa biaya tiket masuk pada jam kunjungan.</p>
            </div>
        @endif

        <div class="mv-kartu-samping__blok">
            <b>Stempel Paspor Digital</b>
            <p class="kecil redup">Berada dalam radius <span class="nums">{{ $t['radius_checkin'] }}</span> m dari lokasi untuk klaim stempel dan +10 poin.</p>
            @if ($u)
                <form method="post" action="{{ route('checkin', $t['slug']) }}" data-checkin>
                    @csrf
                    <input type="hidden" name="lat"><input type="hidden" name="lng"><input type="hidden" name="akurasi">
                    <button class="mv-btn {{ $t['tiket'] ? 'mv-btn--hijau' : 'mv-btn--utama' }} mv-btn--lebar" type="submit">Check-in di lokasi ini</button>
                    <p class="kecil" data-pesan-checkin role="status" aria-live="polite" style="margin-top:8px"></p>
                </form>
            @else
                <a class="mv-btn {{ $t['tiket'] ? 'mv-btn--hijau' : 'mv-btn--utama' }} mv-btn--lebar" href="{{ route('masuk', ['kembali' => route('tempat', $t['slug'], false)]) }}">Masuk untuk check-in</a>
            @endif
        </div>

        <div class="mv-kartu-samping__blok">
            <a class="mv-btn mv-btn--garis mv-btn--kecil mv-btn--lebar" href="https://www.openstreetmap.org/?mlat={{ $t['lat'] }}&mlon={{ $t['lng'] }}#map=18/{{ $t['lat'] }}/{{ $t['lng'] }}" rel="noopener">Petunjuk arah di peta</a>
        </div>
    </aside>
</div>
@endsection
