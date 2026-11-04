@extends('layouts.situs')
@php
    $kreditFoto = \App\Support\Katalog::kredit('kesawan-arsip.jpg');
@endphp
@section('judul', 'Masuk')
@section('tanpa-kepala', '1')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-otentik">
    <div class="mv-otentik__visual">
        <x-foto berkas="kesawan-arsip.jpg" alt="" :utama="true" />
        <div class="mv-otentik__lapisan" aria-hidden="true"></div>

        <div class="mv-otentik__visual-atas" data-naik style="--i:0">
            <a class="mv-otentik__logo" href="{{ route('beranda') }}" aria-label="MedanVisit, kembali ke beranda">
                <img src="{{ asset('images/logo/horizontal-gelap.svg') }}" alt="MedanVisit" width="736" height="256">
            </a>
        </div>

        <div class="mv-otentik__visual-bawah">
            <p class="mv-otentik__tajuk-sisi" data-naik style="--i:1">Satu akun wisata Medan.</p>
            <ul class="mv-otentik__manfaat" data-naik style="--i:2">
                <li>Simpan e-tiket ber-QR dan buka kapan saja di loket masuk</li>
                <li>Klaim stempel GPS di 13 tempat dan kumpulkan poin UMKM</li>
                <li>Simpan dan bagikan rencana perjalanan setengah hari</li>
            </ul>
            <div class="mv-otentik__galeri" data-naik style="--i:3" aria-label="Tempat wisata di MedanVisit">
                <figure>
                    <x-foto berkas="istana-maimun.jpg" alt="Istana Maimun" :kecil="true" />
                    <figcaption>Istana Maimun</figcaption>
                </figure>
                <figure>
                    <x-foto berkas="masjid-raya.jpg" alt="Masjid Raya Al-Mashun" :kecil="true" />
                    <figcaption>Masjid Raya</figcaption>
                </figure>
                <figure>
                    <x-foto berkas="tjong-a-fie-mansion.jpg" alt="Tjong A Fie Mansion" :kecil="true" />
                    <figcaption>Tjong A Fie</figcaption>
                </figure>
            </div>
            @if ($kreditFoto)
                <p class="mv-otentik__kredit" data-naik style="--i:4">Foto latar: {{ $kreditFoto }}</p>
            @endif
        </div>
    </div>

    <div class="mv-otentik__panel">
        <nav class="mv-otentik__panel-atas" aria-label="Navigasi autentikasi" data-naik style="--i:1">
            <a class="mv-tautan" href="{{ route('beranda') }}">Kembali ke beranda</a>
            <span>Belum punya akun? <a class="mv-tautan" href="{{ route('daftar', ['kembali' => $kembali]) }}">Daftar gratis</a></span>
        </nav>

        <div class="mv-otentik__kotak">
            <h1 data-naik style="--i:2">Masuk ke akun</h1>
            <p class="mv-otentik__pengantar" data-naik style="--i:3">Buka kembali e-tiket aktif, koleksi stempel Paspor Digital, dan rencana kunjunganmu di Medan.</p>

            <form method="post" action="{{ route('masuk') }}" novalidate style="margin-top:28px">
                @csrf
                <input type="hidden" name="kembali" value="{{ $kembali }}">

                <div class="isian" data-naik style="--i:4">
                    <label for="email">Alamat email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" required @error('email') aria-invalid="true" aria-describedby="galat-email" @enderror>
                    @error('email')<p class="isian__galat" id="galat-email">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:5">
                    <label for="password">Kata sandi</label>
                    <div class="mv-sandi-bungkus">
                        <input id="password" type="password" name="password" autocomplete="current-password" required aria-describedby="bantu-password @error('password') galat-password @enderror" @error('password') aria-invalid="true" @enderror>
                        <button type="button" class="mv-sandi-toggle" data-toggle-sandi="password" aria-pressed="false">Tampilkan</button>
                    </div>
                    <p class="isian__bantuan" id="bantu-password">Minimal 8 karakter.</p>
                    @error('password')<p class="isian__galat" id="galat-password">{{ $message }}</p>@enderror
                </div>

                <fieldset class="isian mv-kotak-proto" data-naik style="--i:6">
                    <legend>Peran sesi prototipe</legend>
                    <p class="isian__bantuan">Database autentikasi belum disambungkan, sehingga peran dipilih langsung untuk pengujian antarmuka.</p>
                    <div class="pilih-banyak" style="margin-top:8px">
                        @foreach (['wisatawan' => 'Wisatawan', 'mitra' => 'Mitra UMKM', 'kurator' => 'Kurator'] as $v => $l)
                            <label><input type="radio" name="peran" value="{{ $v }}" @checked(old('peran', 'wisatawan') === $v)>{{ $l }}</label>
                        @endforeach
                    </div>
                    @error('peran')<p class="isian__galat">{{ $message }}</p>@enderror
                </fieldset>

                <button class="mv-btn mv-btn--utama mv-btn--lebar" type="submit" style="margin-top:24px" data-naik style="--i:7">Masuk</button>
            </form>
        </div>

        <div class="mv-otentik__panel-bawah" data-naik style="--i:8">
            <a class="mv-tautan" href="{{ route('ketentuan') }}">Ketentuan &amp; privasi</a>
            <span>MedanVisit, Kota Medan</span>
        </div>
    </div>
</div>
@endsection
