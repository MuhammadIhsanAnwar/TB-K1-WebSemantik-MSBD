@extends('layouts.situs')
@section('judul', 'Ada yang tidak beres di pihak kami')
@section('foto-kaki', 'peta-medan-1930-kota-lama.jpg')

@section('isi')
<header class="kepala-halaman kepala-halaman--tunggal">
    <h1>Ada yang tidak beres di pihak kami</h1>
</header>
<div class="isi-halaman">
    <p class="lead">Coba lagi sebentar. Bila terus terjadi, kabari administrator@medanvisit.neoverse.my.id.</p>
    <p style="margin-top:28px"><a class="tombol tombol--hijau" href="{{ route('beranda') }}">Kembali ke beranda</a></p>
</div>
@endsection
