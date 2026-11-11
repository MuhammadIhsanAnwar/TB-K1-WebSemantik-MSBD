@extends('layouts.situs')
@section('judul', 'Password baru')
@section('tanpa-kepala', '1')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-otentik">
    <div class="mv-otentik__panel" style="margin-inline:auto">
        <div class="mv-otentik__kotak">
            <h1>Buat password baru</h1>
            <p class="mv-otentik__pengantar">Gunakan password yang kuat untuk melindungi akunmu.</p>
            <form method="post" action="{{ route('password.update') }}" novalidate style="margin-top:28px">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="isian">
                    <label for="password">Password baru</label>
                    <input id="password" type="password" name="password" autocomplete="new-password" required>
                    <p class="isian__bantuan">Minimal 8 karakter, huruf kecil, huruf kapital, angka, dan simbol.</p>
                    @error('password')<p class="isian__galat">{{ $message }}</p>@enderror
                </div>
                <div class="isian">
                    <label for="password_confirmation">Ulangi password baru</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                </div>
                <button class="mv-btn mv-btn--utama mv-btn--lebar" type="submit" style="margin-top:24px">Simpan password baru</button>
            </form>
        </div>
    </div>
</div>
@endsection
