@extends('layouts.situs')
@php
    $statusLabel = ['aktif' => ['Aktif, siap dipindai', 'pos'], 'dipakai' => ['Sudah dipakai', ''], 'kedaluwarsa' => ['Tanggal lewat', 'neg']][$status];
@endphp
@section('judul', 'E-tiket '.$t['nama'])
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-tiket-ruang" data-tiket="etiket">
    <div class="alat__judul tidak-dicetak">
        <div>
            <h1>E-tiket masuk</h1>
            <p>Tunjukkan kode QR di bawah kepada petugas loket {{ $t['nama'] }}.</p>
        </div>
        <a class="mv-btn mv-btn--garis mv-btn--kecil" href="{{ route('tiket.saya') }}">Semua tiket saya</a>
    </div>

    <article class="mv-etiket" aria-labelledby="judul-etiket">
        <div class="mv-etiket__atas">
            <img src="{{ asset('images/logo/tanpa-tagline-gelap.svg') }}" alt="MedanVisit" width="736" height="256">
            <p class="mv-etiket__jenis">E-TIKET RESMI MEDANVISIT</p>
            <h2 id="judul-etiket">{{ $t['nama'] }}</h2>
        </div>

        <dl class="mv-etiket__rincian">
            <div>
                <dt>Tanggal kunjungan</dt>
                <dd>{{ $tanggal->translatedFormat('l, j F Y') }}</dd>
            </div>
            <div>
                <dt>Jam operasional</dt>
                <dd>{{ $t['jam'] }}</dd>
            </div>
            <div>
                <dt>Jumlah pengunjung</dt>
                <dd>{{ collect($p['item'])->map(fn ($i) => $i['jumlah'].' '.mb_strtolower($i['jenis']))->implode(', ') }}</dd>
            </div>
            <div>
                <dt>Nama pemesan</dt>
                <dd>{{ $p['pemesan']['nama'] }}</dd>
            </div>
        </dl>

        <div class="mv-etiket__potong" aria-hidden="true"></div>

        <div class="mv-etiket__qr-blok">
            <div class="mv-etiket__qr" data-qr="{{ $p['kode'] }}" data-qr-label="Kode QR e-tiket {{ $p['kode'] }}"></div>
            <p class="mv-etiket__kode">{{ $p['kode'] }}</p>
            <span class="p-pill {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
            <p class="kecil redup">Naikkan kecerahan layar ponsel saat menunjukkan kode QR di pintu masuk.</p>
        </div>
    </article>

    <div class="tidak-dicetak" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px">
        <button class="mv-btn mv-btn--utama" type="button" data-cetak>Simpan PDF / Cetak</button>
        <a class="mv-btn mv-btn--hijau" href="{{ route('tempat', $t['slug']) }}">Halaman tempat</a>
        <a class="mv-btn mv-btn--garis" href="https://www.openstreetmap.org/?mlat={{ $t['lat'] }}&mlon={{ $t['lng'] }}#map=18/{{ $t['lat'] }}/{{ $t['lng'] }}" rel="noopener">Petunjuk arah</a>
    </div>
</div>
@endsection
