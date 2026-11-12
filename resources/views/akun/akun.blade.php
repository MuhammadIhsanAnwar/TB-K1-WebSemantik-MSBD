@extends('layouts.situs')
@section('judul', 'Akun')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul">
        <div><h1>{{ $u['nama'] }}</h1><p class="mv-meta-garis"><span>{{ $u['email'] }}</span></p></div>
        <div style="display:flex;gap:8px;align-items:center">
            <a class="p-btn" href="{{ route('akun.pengaturan') }}">Pengaturan akun</a>
            <form method="post" action="{{ route('keluar') }}">@csrf<button class="p-btn" type="submit">Keluar</button></form>
        </div>
    </div>
    @include('partials.subnav-akun')
    <div class="p-kpi">
        <div><div class="label">Pesanan tiket</div><div class="nilai">{{ $pesanan }}</div><div class="ket">semua status</div></div>
        <div><div class="label">Rencana</div><div class="nilai">{{ $rencana }}</div><div class="ket">tersimpan</div></div>
    </div>
    <div class="p-grid">
        <section class="p-panel s6" aria-labelledby="judul-langkah">
            <header><h2 id="judul-langkah">Yang bisa kamu lakukan</h2></header>
            <div class="p-baris">
                <a href="{{ route('tiket.saya') }}"><span class="dua"><b>Lihat e-tiketmu</b><span>{{ $pesanan }} pesanan</span></span><span class="kanan">Tiket saya</span></a>
                <a href="{{ route('rencana') }}"><span class="dua"><b>Susun rute perjalanan</b><span>Dari minat, waktu, dan cara berpindah</span></span><span class="kanan">Perencana</span></a>
            </div>
        </section>
    </div>
</div>
@endsection
