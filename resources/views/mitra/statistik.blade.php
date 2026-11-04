@extends('layouts.panel')
@php
    $label = collect(array_keys($perHari))->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->locale('id')->translatedFormat('j M'))->values();
    $grafik = ['jenis' => 'batang', 'label' => $label, 'nilai' => array_values($perHari), 'sorot' => 29, 'xAt' => [0, 7, 14, 21, 29]];
@endphp
@section('judul', 'Statistik')

@section('isi')
<div class="p-judul">
    <div><h1>Statistik</h1><p class="sub">Penukaran voucher usahamu lewat MedanVisit, 30 hari terakhir.</p></div>
</div>
<div class="p-kpi">
    <div class="utama"><div class="label">Voucher ditukar</div><div class="nilai">{{ $ditukar }}</div><div class="ket">30 hari terakhir</div></div>
    <div><div class="label">Sudah dipakai di kasir</div><div class="nilai">{{ $dipakai }}</div><div class="ket">{{ $ditukar ? round($dipakai / $ditukar * 100) : 0 }}% dari yang ditukar</div></div>
    <div><div class="label">Voucher aktif</div><div class="nilai">{{ $voucher }}</div><div class="ket">jenis voucher</div></div>
</div>
<section class="p-panel" aria-labelledby="judul-grafik">
    <div class="p-grafik-kepala"><h3 id="judul-grafik">Penukaran per hari</h3><span class="meta">hari ini disorot</span></div>
    <div class="p-grafik" data-grafik="{{ json_encode($grafik) }}" aria-label="Grafik penukaran voucher per hari selama 30 hari"></div>
    @if (! $ditukar)<p class="kecil" style="color:var(--p-muted);margin-top:8px">Belum ada penukaran dalam 30 hari terakhir; garis nol tetap digambar.</p>@endif
</section>
@endsection
