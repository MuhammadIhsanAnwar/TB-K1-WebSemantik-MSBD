@extends('layouts.situs')
@section('judul', 'Masuk')
@section('tanpa-kepala', '1')
@section('tanpa-kaki', '1')

@section('isi')
<div class="otentik">
    <div class="otentik__foto">
        <x-foto berkas="kesawan-arsip.jpg" alt="" :utama="true" />
        <div class="otentik__foto-isi">
            <a href="{{ route('beranda') }}" aria-label="MedanVisit, ke beranda"><img src="{{ asset('images/logo/horizontal-gelap.svg') }}" alt="MedanVisit" width="736" height="256"></a>
            <p>Masuk untuk memesan tiket, mengumpulkan stempel, dan menyimpan rencana perjalanan.</p>
        </div>
    </div>
    <div class="otentik__form">
        <h1>Masuk</h1>
        <p>Belum punya akun? <a class="tautan" href="{{ route('daftar', ['kembali' => $kembali]) }}">Daftar gratis</a></p>
        <form method="post" action="{{ route('masuk') }}" novalidate>
            @csrf
            <input type="hidden" name="kembali" value="{{ $kembali }}">
            <div class="isian">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="galat-email" @enderror>
                @error('email')<p class="isian__galat" id="galat-email">{{ $message }}</p>@enderror
            </div>
            <div class="isian">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required aria-describedby="bantu-password @error('password') galat-password @enderror" @error('password') aria-invalid="true" @enderror>
                <p class="isian__bantuan" id="bantu-password">Minimal 8 karakter.</p>
                @error('password')<p class="isian__galat" id="galat-password">{{ $message }}</p>@enderror
            </div>
            <fieldset class="isian">
                <legend>Masuk sebagai</legend>
                <p class="isian__bantuan">Khusus prototipe: peran akun dipilih di sini karena database akun belum tersambung.</p>
                <div class="pilih-banyak" style="margin-top:8px">
                    @foreach (['wisatawan' => 'Wisatawan', 'mitra' => 'Mitra UMKM', 'kurator' => 'Kurator'] as $v => $l)
                        <label><input type="radio" name="peran" value="{{ $v }}" @checked(old('peran', 'wisatawan') === $v)>{{ $l }}</label>
                    @endforeach
                </div>
                @error('peran')<p class="isian__galat">{{ $message }}</p>@enderror
            </fieldset>
            <button class="tombol tombol--lebar" type="submit">Masuk</button>
        </form>
        <p class="otentik__alih kecil redup"><a href="{{ route('beranda') }}">Kembali ke beranda</a> · <a href="{{ route('ketentuan') }}">Ketentuan &amp; privasi</a></p>
    </div>
</div>
@endsection
