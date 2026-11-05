@extends('layouts.situs')
@php
    $populer = collect(['istana-maimun', 'masjid-raya-al-mashun', 'tjong-a-fie-mansion', 'kuil-shri-mariamman'])->map(fn ($s) => \App\Support\Katalog::get($s));
@endphp
@section('judul', 'Belum ada di peta')
@section('foto-kaki', 'peta-medan-1930-kota-lama.jpg')

@section('isi')
<header class="kepala-halaman kepala-halaman--besar">
    <h1>Belum ada di peta</h1>
    <p>Halaman yang kamu cari tidak ditemukan. Mungkin alamatnya salah ketik, atau tempatnya belum masuk MedanVisit.</p>
</header>
<div class="isi-halaman">
    <form class="tanya-medan" action="{{ route('cari') }}" method="get" role="search">
        <label><span class="sr-only">Cari tempat</span><input type="search" name="q" placeholder="Cari tempat, tokoh, atau kuliner" required></label>
        <button class="tombol" type="submit">Cari</button>
    </form>
    <h2 class="judul" style="margin-top:72px;font-size:2rem">Tempat yang paling sering dibuka</h2>
    <div class="tempat-populer" style="margin-top:24px">
        @foreach ($populer as $t)
            <a href="{{ route('tempat', $t['slug']) }}">
                <x-foto :berkas="$t['foto'] ?: 'peta-medan-1930-kota-lama.jpg'" :alt="$t['nama']" :kecil="true" :segera="true" />
                <b>{{ $t['nama'] }}</b>
                <span class="kecil redup">{{ \App\Support\Katalog::KATEGORI[$t['kategori']] }}, {{ $t['kecamatan'] }}</span>
            </a>
        @endforeach
    </div>
    <p style="margin-top:40px"><a class="tombol tombol--hijau" href="{{ route('beranda') }}">Kembali ke beranda</a></p>
</div>
@endsection
