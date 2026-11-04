@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $tgl = \Illuminate\Support\Carbon::parse($p['tanggal'])->locale('id');
    $vaNomor = '8808'.substr(preg_replace('/\D/', '', $p['kode']), 0, 4).substr(preg_replace('/\D/', '', $p['pemesan']['hp']), -6);
@endphp
@section('judul', 'Pesanan '.$p['kode'])
@section('tanpa-kaki', '1')
@if ($p['status'] === 'lunas') @section('tanpa-kepala', '1') @endif

@section('isi')
@if ($p['status'] === 'lunas')
    <div class="sukses" data-tiket="sukses">
        <div class="sukses__isi">
            <svg class="stempel" viewBox="0 0 200 200" role="img" aria-label="Tiket terbit">
                <defs><path id="lingkar-terbit" d="M 100,100 m -72,0 a 72,72 0 1,1 144,0 a 72,72 0 1,1 -144,0"/></defs>
                <circle cx="100" cy="100" r="94" fill="none" stroke="#e3a51c" stroke-width="5"/><circle cx="100" cy="100" r="84" fill="none" stroke="#e3a51c" stroke-width="1.5"/>
                <text fill="#e3a51c"><textPath href="#lingkar-terbit" startOffset="25%" text-anchor="middle">TIKET TERBIT · MEDANVISIT</textPath></text>
                <path d="M76 102l16 16 32-36" fill="none" stroke="#f6f1e4" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1>Tiketmu sudah terbit</h1>
            <p>{{ collect($p['item'])->map(fn ($i) => $i['jumlah'].' '.mb_strtolower($i['jenis']))->implode(', ') }} untuk {{ $t['nama'] }}, {{ $tgl->translatedFormat('l, j F Y') }}. Salinan e-tiket dikirim ke {{ $p['pemesan']['email'] }} setelah layanan email tersambung.</p>
            <div class="sukses__aksi">
                <a class="tombol" href="{{ route('tiket.e', $p['kode']) }}">Lihat e-tiket</a>
                <a class="tombol tombol--garis" style="color:var(--on-green);box-shadow:inset 0 0 0 2px var(--on-green-muted)" href="{{ route('tempat', $t['slug']) }}">Kembali ke {{ $t['nama'] }}</a>
            </div>
        </div>
    </div>
@else
<div class="alat" data-tiket="status">
    <div class="alat__judul"><div><h1>Pesanan {{ $p['kode'] }}</h1><p>{{ $t['nama'] }} · {{ $tgl->translatedFormat('l, j F Y') }}</p></div></div>
    <ol class="langkah-beli" aria-label="Langkah pembelian">
        <li data-n="1" class="selesai">Pilih tiket</li><li data-n="2" class="selesai">Data pemesan</li><li data-n="3" aria-current="step">Bayar</li><li data-n="4">E-tiket</li>
    </ol>
    <div class="tiket-alur">
        <section class="p-panel" aria-labelledby="judul-bayar">
            @if ($p['status'] === 'menunggu')
                <header><h2 id="judul-bayar">Selesaikan pembayaran</h2><span class="p-pill warn">Menunggu pembayaran</span></header>
                <p>Bayar sebelum batas waktu, atau pesanan otomatis batal.</p>
                <p class="hitung-mundur" data-batas="{{ $p['batas_bayar'] }}" role="timer" aria-live="off" style="margin:12px 0 20px">15:00</p>
                @if ($p['metode'] === 'qris')
                    <div style="display:grid;justify-items:start;gap:8px">
                        <div class="etiket__qr" data-qr="QRIS-PROTOTIPE-{{ $p['kode'] }}-{{ $p['total'] }}" data-qr-label="Kode QRIS contoh untuk pesanan {{ $p['kode'] }}"></div>
                        <p class="kecil redup">Pindai dengan aplikasi bank atau dompet digital. Kode ini hanya contoh prototipe dan tidak bisa dibayar.</p>
                    </div>
                @elseif ($p['metode'] === 'va')
                    <div class="p-field">
                        <span class="label" style="font-weight:600">Nomor virtual account <span class="contoh">contoh</span></span>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><span class="etiket__kode">{{ $vaNomor }}</span><button class="p-btn" type="button" data-salin="{{ $vaNomor }}">Salin</button></div>
                    </div>
                    <details class="lipat" style="margin-top:16px"><summary>Cara bayar lewat m-banking</summary><p>Buka menu Transfer, pilih Virtual Account, masukkan nomor di atas, lalu periksa nama MedanVisit dan totalnya sebelum mengonfirmasi.</p></details>
                @else
                    <p>Buka aplikasi {{ $p['metode'] === 'ewallet' ? 'dompet digital' : 'bank penerbit kartu' }} lewat tautan dari payment gateway.</p>
                @endif
                <div class="p-pesan p-pesan--info" style="margin-top:20px">Prototipe: notifikasi pembayaran dari Midtrans belum tersambung. Tombol di bawah menggantikan notifikasi itu.</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
                    <form method="post" action="{{ route('pesanan.simulasi', $p['kode']) }}">@csrf<button class="p-btn p-btn--utama p-btn--besar" type="submit">Simulasikan pembayaran berhasil</button></form>
                    <form method="post" action="{{ route('pesanan.batal', $p['kode']) }}">@csrf<button class="p-btn p-btn--besar p-btn--bahaya" type="submit">Batalkan pesanan</button></form>
                </div>
            @elseif ($p['status'] === 'kedaluwarsa')
                <header><h2 id="judul-bayar">Waktu pembayaran habis</h2><span class="p-pill neg">Kedaluwarsa</span></header>
                <p>Pesanan ini tidak dibayar dalam 15 menit, jadi tiketnya tidak terbit dan tidak ada dana yang ditarik.</p>
                <a class="p-btn p-btn--utama p-btn--besar" style="margin-top:16px" href="{{ route('tiket.pilih', $t['slug']) }}">Pesan ulang</a>
            @else
                <header><h2 id="judul-bayar">Pesanan dibatalkan</h2><span class="p-pill">Dibatalkan</span></header>
                <p>Pesanan ini dibatalkan sebelum dibayar.</p>
                <a class="p-btn p-btn--utama p-btn--besar" style="margin-top:16px" href="{{ route('tiket.pilih', $t['slug']) }}">Pesan ulang</a>
            @endif
        </section>
        <aside class="p-panel ringkasan-pesanan" aria-labelledby="judul-ringkasan">
            <header><h2 id="judul-ringkasan">Ringkasan</h2><span class="meta">{{ $metode[$p['metode']] }}</span></header>
            <div class="p-baris">
                @foreach ($p['item'] as $i)<div><span>{{ $i['jumlah'] }} × {{ $i['jenis'] }}</span><span class="kanan">{{ Katalog::rupiah($i['subtotal']) }}</span></div>@endforeach
            </div>
            <div class="total"><span>Total</span><b>{{ Katalog::rupiah($p['total']) }}</b></div>
            <p class="kecil redup" style="margin-top:12px">Atas nama {{ $p['pemesan']['nama'] }}, {{ $p['pemesan']['email'] }}</p>
        </aside>
    </div>
</div>
@endif
@endsection
