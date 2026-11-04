@extends('layouts.situs')
@section('judul', 'Sesi sudah habis')
@section('foto-kaki', 'peta-medan-1930-kota-lama.jpg')

@section('isi')
<header class="kepala-halaman kepala-halaman--tunggal">
    <h1>Sesi sudah habis</h1>
</header>
<div class="isi-halaman">
    <p class="lead">Halaman terlalu lama terbuka sehingga formulirnya kedaluwarsa. Muat ulang halaman lalu kirim lagi.</p>
    <p style="margin-top:28px"><a class="tombol tombol--hijau" href="{{ route('beranda') }}">Kembali ke beranda</a></p>
</div>
@endsection
