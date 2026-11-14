@extends('layouts.situs')
@section('judul', 'Akun')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul">
        <div style="display:flex;gap:16px;align-items:center">
            @if (!empty($u['foto_profil']) && is_file(public_path('storage/'.$u['foto_profil'])))
                <img src="{{ asset('storage/'.$u['foto_profil']) }}?v={{ filemtime(public_path('storage/'.$u['foto_profil'])) }}"
                     alt="Foto profil {{ $u['nama'] }}" width="64" height="64"
                     style="object-fit:cover;border-radius:50%">
            @else
                <span aria-hidden="true" style="display:grid;place-items:center;width:64px;height:64px;border-radius:50%;background:var(--mv-permukaan-2,#eee);font-size:24px;font-weight:600">{{ mb_strtoupper(mb_substr($u['nama'] ?? '?', 0, 1)) }}</span>
            @endif
            <div>
                <h1>{{ $u['nama'] }}</h1>
                <p class="mv-meta-garis"><span>{{ $u['email'] }}</span>@if (!empty($u['peran']))<span>{{ ucfirst($u['peran']) }}</span>@endif</p>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <a class="p-btn" href="{{ route('akun.pengaturan') }}">Pengaturan akun</a>
            <form method="post" action="{{ route('keluar') }}">@csrf<button class="p-btn" type="submit">Keluar</button></form>
        </div>
    </div>
    @include('partials.subnav-akun')
    <div class="p-kpi">
        <div><div class="label">Pesanan tiket</div><div class="nilai">{{ $pesanan }}</div><div class="ket">semua status</div></div>
        <div><div class="label">Rencana</div><div class="nilai">{{ $rencana }}</div><div class="ket">tersimpan</div></div>
        <div><div class="label">Ulasan</div><div class="nilai">{{ $ulasan }}</div><div class="ket">yang kamu tulis</div></div>
    </div>
    <div class="p-grid">
        <section class="p-panel s6" aria-labelledby="judul-langkah">
            <header><h2 id="judul-langkah">Yang bisa kamu lakukan</h2></header>
            <div class="p-baris">
                <a href="{{ route('tiket.saya') }}"><span class="dua"><b>Lihat e-tiketmu</b><span>{{ $pesanan }} pesanan</span></span><span class="kanan">Tiket saya</span></a>
                <a href="{{ route('rencana') }}"><span class="dua"><b>Susun rute perjalanan</b><span>Dari minat, waktu, dan cara berpindah</span></span><span class="kanan">Perencana</span></a>
                <a href="{{ route('ulasan.saya') }}"><span class="dua"><b>Ulasan yang kamu tulis</b><span>{{ $ulasan }} ulasan</span></span><span class="kanan">Ulasan saya</span></a>
                <a href="{{ route('akun.pengaturan') }}"><span class="dua"><b>Perbarui profil</b><span>Nama, foto, nomor HP, password</span></span><span class="kanan">Pengaturan</span></a>
            </div>
        </section>
    </div>
</div>
@endsection
