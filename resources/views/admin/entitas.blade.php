@extends('layouts.panel')
@php use App\Support\Katalog; use App\Http\Controllers\SitusController; @endphp
@section('judul', 'Entitas')

@section('isi')
<div class="p-judul">
    <div><h1>Entitas</h1><p class="sub">Tempat, tokoh, peristiwa, budaya, dan kuliner yang menjadi simpul graf.</p></div>
    @if (session('pengguna.peran') === 'superadmin')
        <div class="p-judul__aksi">
            <a class="p-btn" href="{{ route('admin.tempat.daftar') }}">Kelola tempat</a>
            <a class="p-btn p-btn--utama" href="{{ route('admin.tempat.baru') }}">Tambah tempat wisata</a>
        </div>
    @endif
</div>
<nav class="p-tabs" aria-label="Jenis entitas">
    <a href="{{ route('admin.entitas', ['q' => $q]) }}" @if (! $jenis) aria-current="true" @endif>Semua<span class="n">{{ array_sum($perJenis) }}</span></a>
    @foreach ($perJenis as $j => $n)
        <a href="{{ route('admin.entitas', ['jenis' => $j, 'q' => $q]) }}" @if ($jenis === $j) aria-current="true" @endif>{{ Katalog::KELAS[$j] }}<span class="n">{{ $n }}</span></a>
    @endforeach
</nav>
<form class="p-alat" action="{{ route('admin.entitas') }}" method="get" role="search">
    @if ($jenis)<input type="hidden" name="jenis" value="{{ $jenis }}">@endif
    <label class="sr-only" for="q">Cari nama</label>
    <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Cari nama…" style="width:280px">
    <span class="isi"></span>
    <span class="hitung">{{ $data->count() }} entitas</span>
</form>
<div class="p-tabel-bungkus">
    <table class="p-tabel jadi-daftar">
        <thead><tr><th>Nama</th><th>Jenis</th><th>Keterangan</th><th class="n">Relasi</th><th class="n"><span class="sr-only">Aksi</span></th></tr></thead>
        <tbody>
            @forelse ($data as $e)
                <tr>
                    <td><a class="nama" href="{{ route('admin.entitas.ubah', $e['slug']) }}">{{ $e['nama'] }}</a>@if ($e['jenis'] === 'tempat')<span class="sub">{{ $e['kecamatan'] }}</span>@endif</td>
                    <td class="sekunder"><span class="kelas" data-kelas="{{ $e['jenis'] }}" style="font-weight:500">{{ Katalog::KELAS[$e['jenis']] }}</span></td>
                    <td class="sekunder" style="color:var(--p-muted)">{{ \Illuminate\Support\Str::limit($e['ringkas'], 70) }}</td>
                    <td class="n">{{ $jumlahRelasi[$e['slug']] ?? 0 }}</td>
                    <td class="n"><a class="p-btn" href="{{ route('admin.entitas.ubah', $e['slug']) }}">Ubah</a></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="p-kosong">Tidak ada entitas bernama “{{ $q }}”.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
