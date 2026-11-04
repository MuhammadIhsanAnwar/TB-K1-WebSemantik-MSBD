@extends('layouts.situs')
@section('judul', 'Daftar')
@section('tanpa-kepala', '1')
@section('tanpa-kaki', '1')

@section('isi')
<div class="otentik">
    <div class="otentik__foto">
        <x-foto berkas="istana-maimun-1900.jpg" alt="" :utama="true" />
        <div class="otentik__foto-isi">
            <a href="{{ route('beranda') }}" aria-label="MedanVisit, ke beranda"><img src="{{ asset('images/logo/horizontal-gelap.svg') }}" alt="MedanVisit" width="736" height="256"></a>
            <p>Satu akun untuk tiket masuk, stempel di setiap tempat, dan rencana yang bisa dibagikan.</p>
        </div>
    </div>
    <div class="otentik__form">
        <h1>Buat akun</h1>
        <p>Sudah punya akun? <a class="tautan" href="{{ route('masuk', ['kembali' => $kembali]) }}">Masuk</a></p>
        <form method="post" action="{{ route('daftar') }}" novalidate>
            @csrf
            <input type="hidden" name="kembali" value="{{ $kembali }}">
            <div class="isian">
                <label for="nama">Nama di paspor</label>
                <input id="nama" type="text" name="nama" value="{{ old('nama') }}" autocomplete="name" maxlength="60" required @error('nama') aria-invalid="true" aria-describedby="galat-nama" @enderror>
                @error('nama')<p class="isian__galat" id="galat-nama">{{ $message }}</p>@enderror
            </div>
            <div class="isian">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required aria-describedby="bantu-email @error('email') galat-email @enderror" @error('email') aria-invalid="true" @enderror>
                <p class="isian__bantuan" id="bantu-email">E-tiket dikirim ke alamat ini.</p>
                @error('email')<p class="isian__galat" id="galat-email">{{ $message }}</p>@enderror
            </div>
            <div class="isian">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" autocomplete="new-password" required aria-describedby="bantu-password @error('password') galat-password @enderror" @error('password') aria-invalid="true" @enderror>
                <p class="isian__bantuan" id="bantu-password">Minimal 8 karakter.</p>
                @error('password')<p class="isian__galat" id="galat-password">{{ $message }}</p>@enderror
            </div>
            <div class="isian">
                <label for="password_confirmation">Ulangi kata sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>
            <div class="isian">
                <label class="centang"><input type="checkbox" name="setuju" value="1" @checked(old('setuju'))><span>Saya setuju dengan <a class="tautan" href="{{ route('ketentuan') }}">ketentuan &amp; privasi</a>.</span></label>
                @error('setuju')<p class="isian__galat">{{ $message }}</p>@enderror
            </div>
            <button class="tombol tombol--lebar" type="submit">Daftar</button>
        </form>
    </div>
</div>
@endsection
