@extends('layouts.situs')
@section('judul', 'Verifikasi email')
@section('tanpa-kepala', '1')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-otentik">
    <div class="mv-otentik__panel" style="margin-inline:auto">
        <div class="mv-otentik__kotak">
            <h1>Verifikasi email</h1>
            <p class="mv-otentik__pengantar">{{ $pesan }}</p>
            @if ($email)
                <form method="post" action="{{ route('verifikasi.kirim') }}" style="margin-top:24px">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <button class="mv-btn mv-btn--utama mv-btn--lebar" type="submit">Kirim ulang email verifikasi</button>
                </form>
            @endif
            <p style="margin-top:20px"><a class="mv-tautan" href="{{ route('masuk') }}">Kembali ke halaman masuk</a></p>
        </div>
    </div>
</div>
@endsection
