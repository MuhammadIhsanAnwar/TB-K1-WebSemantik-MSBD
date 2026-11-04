@extends('layouts.situs')
@section('judul', 'Halaman ini khusus peran lain')
@section('foto-kaki', 'peta-medan-1930-kota-lama.jpg')

@section('isi')
<header class="kepala-halaman kepala-halaman--tunggal">
    <h1>Halaman ini khusus peran lain</h1>
</header>
<div class="isi-halaman">
    <p class="lead">Akunmu tidak punya akses ke halaman ini. Masuk dengan akun yang sesuai, misalnya akun mitra atau kurator.</p>
    <p style="margin-top:28px"><a class="tombol tombol--hijau" href="{{ route('beranda') }}">Kembali ke beranda</a></p>
</div>
@endsection
