@extends('layouts.situs')
@php $gelar = \App\Support\Katalog::gelar($poin['total']); @endphp
@section('judul', 'Akun')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul">
        <div><h1>{{ $u['nama'] }}</h1><p class="mv-meta-garis"><span>{{ $u['email'] }}</span><span>{{ $gelar['nama'] }}</span></p></div>
        <form method="post" action="{{ route('keluar') }}">@csrf<button class="p-btn" type="submit">Keluar</button></form>
    </div>
    @include('partials.subnav-akun')
    <div class="p-kpi">
        <div class="utama"><div class="label">Poin</div><div class="nilai">{{ $poin['total'] }}</div><div class="ket">{{ $poin['didapat'] }} didapat, {{ $poin['dipakai'] }} ditukar</div></div>
        <div><div class="label">Stempel</div><div class="nilai">{{ $stempel }}</div><div class="ket">tempat berbeda</div></div>
        <div><div class="label">Pesanan tiket</div><div class="nilai">{{ $pesanan }}</div><div class="ket">semua status</div></div>
        <div><div class="label">Rencana</div><div class="nilai">{{ $rencana }}</div><div class="ket">tersimpan</div></div>
    </div>
    <div class="p-grid">
        <section class="p-panel s6" aria-labelledby="judul-langkah">
            <header><h2 id="judul-langkah">Yang bisa kamu lakukan</h2></header>
            <div class="p-baris">
                <a href="{{ route('peta') }}"><span class="dua"><b>Datangi tempat dan check-in</b><span>Setiap tempat memberi stempel dan 10 poin</span></span><span class="kanan">Buka peta</span></a>
                <a href="{{ route('tiket.saya') }}"><span class="dua"><b>Lihat e-tiketmu</b><span>{{ $pesanan }} pesanan</span></span><span class="kanan">Tiket saya</span></a>
                <a href="{{ route('rencana') }}"><span class="dua"><b>Susun rute perjalanan</b><span>Dari minat, waktu, dan cara berpindah</span></span><span class="kanan">Perencana</span></a>
            </div>
        </section>
        <section class="p-panel s6" aria-labelledby="judul-bahasa">
            <header><h2 id="judul-bahasa">Bahasa cerita</h2><span class="meta">Untuk cerita dan audio guide</span></header>
            <div class="p-seg" role="group" aria-label="Bahasa cerita">
                <button type="button" aria-pressed="true">Indonesia</button>
                <button type="button" disabled aria-disabled="true" title="Segera">English</button>
                <button type="button" disabled aria-disabled="true" title="Segera">中文</button>
                <button type="button" disabled aria-disabled="true" title="Segera">Melayu</button>
            </div>
            <p class="kecil redup" style="margin-top:10px">Bahasa lain segera tersedia setelah kurator selesai menerjemahkan cerita.</p>
        </section>
    </div>
</div>
@endsection
