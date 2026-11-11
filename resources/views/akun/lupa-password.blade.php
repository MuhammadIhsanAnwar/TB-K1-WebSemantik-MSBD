@extends('layouts.situs')
@section('judul', 'Lupa password')
@section('tanpa-kepala', '1')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-otentik">
    <div class="mv-otentik__panel" style="margin-inline:auto">
        <div class="mv-otentik__kotak">
            <h1>Lupa password?</h1>
            <p class="mv-otentik__pengantar">Masukkan email akun manualmu. Kami akan mengirim tautan untuk membuat password baru.</p>
            @if (session('pesan'))<p class="isian__bantuan">{{ session('pesan') }}</p>@endif
            <form method="post" action="{{ route('password.email') }}" novalidate style="margin-top:28px">
                @csrf
                <div class="isian">
                    <label for="email">Alamat email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required @error('email') aria-invalid="true" @enderror>
                    @error('email')<p class="isian__galat">{{ $message }}</p>@enderror
                </div>
                <button class="mv-btn mv-btn--utama mv-btn--lebar" type="submit" style="margin-top:24px">Kirim email reset</button>
            </form>
            <p style="margin-top:20px"><a class="mv-tautan" href="{{ route('masuk') }}">Kembali ke halaman masuk</a></p>
        </div>
    </div>
</div>
@endsection
