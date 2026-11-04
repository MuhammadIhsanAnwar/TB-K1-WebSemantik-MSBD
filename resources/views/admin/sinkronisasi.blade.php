@extends('layouts.panel')
@section('judul', 'Sinkronisasi')

@section('isi')
<div class="p-judul">
    <div><h1>Sinkronisasi</h1><p class="sub">{{ $diperiksa ? 'Diperiksa '.$diperiksa->locale('id')->translatedFormat('j F Y, H.i.s') : 'Status koneksi ke basis data dan endpoint SPARQL.' }}</p></div>
    <a class="p-btn p-btn--utama" href="{{ route('admin.sinkronisasi', ['periksa' => 1]) }}">{{ $diperiksa ? 'Periksa lagi' : 'Periksa koneksi' }}</a>
</div>
@if ($hasil)
    <section class="p-panel" aria-labelledby="judul-hasil">
        <header><h2 id="judul-hasil">Hasil pemeriksaan</h2></header>
        <div class="p-baris">
            @foreach ($hasil as $h)
                <div style="align-items:flex-start"><span class="dua" style="flex:1"><b>{{ $h['label'] }}</b><span>{{ $h['teks'] }}</span></span><span class="p-pill {{ $h['ok'] ? 'pos' : 'neg' }}">{{ $h['ok'] ? 'Tersambung' : 'Gagal' }}</span></div>
            @endforeach
        </div>
    </section>
@else
    <div class="p-kosong">Belum diperiksa. Pemeriksaan mencoba menyambung ke kedua basis data PostgreSQL dan endpoint SPARQL, paling lama sekitar 12 detik.</div>
@endif
<section class="p-panel" aria-labelledby="judul-alur">
    <header><h2 id="judul-alur">Alur data</h2></header>
    <div class="p-baris">
        <div><span class="dua"><b>PostgreSQL 1</b><span>Atribut tempat, akun, check-in, poin, voucher</span></span></div>
        <div><span class="dua"><b>PostgreSQL 2</b><span>Jenis tiket, pesanan, pembayaran</span></span></div>
        <div><span class="dua"><b>Apache Jena Fuseki</b><span>Ontologi dan relasi dalam RDF, dijawab lewat SPARQL; ekspor tersedia di <a href="{{ route('data.turtle') }}" style="text-decoration:underline">medanvisit.ttl</a></span></span></div>
        <div><span class="dua"><b>Penyimpanan sementara</b><span>Selama basis data belum tersambung, pesanan, check-in, dan ulasan prototipe disimpan di cache berkas</span></span></div>
    </div>
</section>
@endsection
