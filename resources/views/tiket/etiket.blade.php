@extends('layouts.situs')
@php $statusLabel = ['aktif' => ['Aktif', 'pos'], 'dipakai' => ['Sudah dipakai', ''], 'kedaluwarsa' => ['Kedaluwarsa', 'neg']][$status]; @endphp
@section('judul', 'E-tiket '.$t['nama'])
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat" data-tiket="etiket">
    <div class="alat__judul tidak-dicetak"><div><h1>E-tiket</h1><p>Tunjukkan QR ini di loket {{ $t['nama'] }}.</p></div><a class="p-btn" href="{{ route('tiket.saya') }}">Tiket saya</a></div>
    <article class="etiket" aria-labelledby="judul-etiket">
        <div class="etiket__atas">
            <img src="{{ asset('images/logo/tanpa-tagline-gelap.svg') }}" alt="MedanVisit" width="736" height="256">
            <p class="jenis">E-tiket masuk</p>
            <h2 id="judul-etiket">{{ $t['nama'] }}</h2>
        </div>
        <div class="etiket__tengah">
            <p><span>Tanggal kunjungan</span>{{ $tanggal->translatedFormat('l, j F Y') }}</p>
            <p><span>Jam buka</span>{{ $t['jam'] }}</p>
            <p><span>Tiket</span>{{ collect($p['item'])->map(fn ($i) => $i['jumlah'].' '.mb_strtolower($i['jenis']))->implode(', ') }}</p>
            <p><span>Atas nama</span>{{ $p['pemesan']['nama'] }}</p>
        </div>
        <div class="etiket__potong" aria-hidden="true"><span></span></div>
        <div class="etiket__bawah">
            <div class="etiket__qr" data-qr="{{ $p['kode'] }}" data-qr-label="Kode QR e-tiket {{ $p['kode'] }}"></div>
            <p class="etiket__kode">{{ $p['kode'] }}</p>
            <span class="p-pill {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
            <p class="kecil redup">Naikkan kecerahan layar saat menunjukkan QR.</p>
        </div>
    </article>
    <div class="tidak-dicetak" style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:20px">
        <button class="p-btn p-btn--utama" type="button" data-cetak>Simpan PDF</button>
        <a class="p-btn" href="https://www.openstreetmap.org/?mlat={{ $t['lat'] }}&mlon={{ $t['lng'] }}#map=18/{{ $t['lat'] }}/{{ $t['lng'] }}" rel="noopener">Petunjuk arah</a>
    </div>
</div>
@endsection
