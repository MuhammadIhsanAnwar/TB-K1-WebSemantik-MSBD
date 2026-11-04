@extends('layouts.panel')
@section('judul', 'Mitra dan voucher')

@section('isi')
<div class="p-judul"><div><h1>Mitra dan voucher</h1><p class="sub">Usaha lokal yang menerbitkan voucher untuk penukaran poin Paspor Digital.</p></div></div>
<div class="p-grid">
    <section class="p-panel s5" aria-labelledby="judul-mitra">
        <header><h2 id="judul-mitra">Mitra</h2><span class="meta">{{ $mitra->count() }} usaha</span></header>
        @if ($mitra->isNotEmpty())
            <table class="p-tabel">
                <thead><tr><th>Usaha</th><th class="n">Voucher</th><th class="n">Ditukar</th></tr></thead>
                <tbody>@foreach ($mitra as $m)<tr><td><b>{{ $m['usaha'] }}</b></td><td class="n">{{ $m['voucher'] }}</td><td class="n">{{ $m['ditukar'] }}</td></tr>@endforeach</tbody>
            </table>
        @else
            <div class="p-kosong">Belum ada mitra yang menerbitkan voucher. Mitra mendaftar lewat akun berperan mitra UMKM.</div>
        @endif
    </section>
    <section class="p-panel s7" aria-labelledby="judul-voucher">
        <header><h2 id="judul-voucher">Semua voucher</h2></header>
        @if ($voucher->isNotEmpty())
            <table class="p-tabel jadi-daftar">
                <thead><tr><th>Voucher</th><th>Usaha</th><th class="n">Poin</th><th class="n">Sisa stok</th></tr></thead>
                <tbody>@foreach ($voucher as $v)<tr><td><b>{{ $v['nama'] }}</b><span class="sub">sampai {{ \Illuminate\Support\Carbon::parse($v['berlaku'])->locale('id')->translatedFormat('j M Y') }}</span></td><td class="sekunder">{{ $v['usaha'] }}</td><td class="n sekunder">{{ $v['poin'] }}</td><td class="n">{{ $v['stok'] }} <span style="color:var(--p-muted)">/ {{ $v['stok_awal'] }}</span></td></tr>@endforeach</tbody>
            </table>
        @else
            <div class="p-kosong">Belum ada voucher.</div>
        @endif
    </section>
</div>
@endsection
