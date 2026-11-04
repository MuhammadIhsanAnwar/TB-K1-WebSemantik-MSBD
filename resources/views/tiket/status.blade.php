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
            <svg class="stempel" viewBox="0 0 200 200" role="img" aria-label="Stempel tiket terbit">
                <defs><path id="lingkar-terbit" d="M 100,100 m -72,0 a 72,72 0 1,1 144,0 a 72,72 0 1,1 -144,0"/></defs>
                <circle cx="100" cy="100" r="94" fill="none" stroke="#e3a51c" stroke-width="5"/>
                <circle cx="100" cy="100" r="84" fill="none" stroke="#e3a51c" stroke-width="1.5"/>
                <text fill="#e3a51c"><textPath href="#lingkar-terbit" startOffset="25%" text-anchor="middle">TIKET RESMI MEDANVISIT</textPath></text>
                <path d="M76 102l16 16 32-36" fill="none" stroke="#f6f1e4" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1>E-tiketmu sudah terbit</h1>
            <p>{{ collect($p['item'])->map(fn ($i) => $i['jumlah'].' '.mb_strtolower($i['jenis']))->implode(', ') }} untuk {{ $t['nama'] }} pada {{ $tgl->translatedFormat('l, j F Y') }}. Kode pesanan <strong>{{ $p['kode'] }}</strong> siap ditunjukkan di loket masuk.</p>
            <div class="sukses__aksi">
                <a class="mv-btn mv-btn--utama" href="{{ route('tiket.e', $p['kode']) }}">Buka e-tiket &amp; kode QR</a>
                <a class="mv-btn mv-btn--garis-terang" href="{{ route('tempat', $t['slug']) }}">Kembali ke {{ $t['nama'] }}</a>
            </div>
        </div>
    </div>
@else
<div class="mv-tiket-ruang" data-tiket="status">
    <div class="alat__judul">
        <div>
            <h1>Pesanan {{ $p['kode'] }}</h1>
            <p>{{ $t['nama'] }}, {{ $tgl->translatedFormat('l, j F Y') }}</p>
        </div>
    </div>

    <ol class="mv-langkah-tiket" aria-label="Tahap pemesanan tiket">
        <li class="selesai"><span class="mv-langkah-tiket__no">1</span><span>Pilih tiket</span></li>
        <li class="selesai"><span class="mv-langkah-tiket__no">2</span><span>Data pemesan</span></li>
        <li aria-current="step"><span class="mv-langkah-tiket__no">3</span><span>Pembayaran</span></li>
        <li><span class="mv-langkah-tiket__no">4</span><span>E-tiket terbit</span></li>
    </ol>

    <div class="tiket-alur">
        <section class="p-panel" aria-labelledby="judul-bayar">
            @if ($p['status'] === 'menunggu')
                <header>
                    <h2 id="judul-bayar">Selesaikan pembayaran</h2>
                    <span class="p-pill warn">Menunggu pembayaran</span>
                </header>
                <p>Selesaikan sebelum batas waktu berakhir agar pesanan tidak dibatalkan otomatis.</p>
                <p class="hitung-mundur nums" data-batas="{{ $p['batas_bayar'] }}" role="timer" aria-live="off" style="margin:12px 0 20px">15:00</p>

                @if ($p['metode'] === 'qris')
                    <div style="display:grid;justify-items:start;gap:10px">
                        <div class="mv-etiket__qr" data-qr="QRIS-PROTOTIPE-{{ $p['kode'] }}-{{ $p['total'] }}" data-qr-label="Kode QRIS contoh untuk pesanan {{ $p['kode'] }}"></div>
                        <p class="kecil redup">Pindai melalui aplikasi bank atau dompet digital. Kode QRIS ini merupakan simulasi prototipe dan tidak menarik saldo nyata.</p>
                    </div>
                @elseif ($p['metode'] === 'va')
                    <div class="p-field">
                        <span class="label" style="font-weight:600">Nomor virtual account <span class="contoh">contoh</span></span>
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:6px">
                            <span class="mv-etiket__kode">{{ $vaNomor }}</span>
                            <button class="mv-btn mv-btn--garis mv-btn--kecil" type="button" data-salin="{{ $vaNomor }}">Salin nomor</button>
                        </div>
                    </div>
                    <details class="lipat" style="margin-top:16px">
                        <summary>Petunjuk transfer virtual account</summary>
                        <p>Buka menu Transfer Virtual Account di aplikasi m-banking, masukkan nomor di atas, lalu periksa nama penerima MedanVisit beserta nominal tagihan sebelum konfirmasi.</p>
                    </details>
                @else
                    <p>Lanjutkan pembayaran melalui aplikasi {{ $p['metode'] === 'ewallet' ? 'dompet digital' : 'bank penerbit kartu' }} sesuai nominal pesanan.</p>
                @endif

                <div class="p-pesan p-pesan--info" style="margin-top:20px">Mode prototipe: klik tombol simulasi di bawah untuk menerbitkan e-tiket secara langsung.</div>

                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
                    <form method="post" action="{{ route('pesanan.simulasi', $p['kode']) }}">
                        @csrf
                        <button class="mv-btn mv-btn--utama" type="submit">Simulasikan pembayaran berhasil</button>
                    </form>
                    <form method="post" action="{{ route('pesanan.batal', $p['kode']) }}">
                        @csrf
                        <button class="mv-btn mv-btn--bahaya" type="submit">Batalkan pesanan</button>
                    </form>
                </div>
            @elseif ($p['status'] === 'kedaluwarsa')
                <header>
                    <h2 id="judul-bayar">Batas waktu pembayaran berakhir</h2>
                    <span class="p-pill neg">Kedaluwarsa</span>
                </header>
                <p>Pesanan melewati batas 15 menit sehingga dibatalkan otomatis tanpa ada dana yang ditarik.</p>
                <a class="mv-btn mv-btn--utama" style="margin-top:16px" href="{{ route('tiket.pilih', $t['slug']) }}">Buat pesanan baru</a>
            @else
                <header>
                    <h2 id="judul-bayar">Pesanan telah dibatalkan</h2>
                    <span class="p-pill">Dibatalkan</span>
                </header>
                <p>Pesanan ini dibatalkan atas permintaanmu sebelum pembayaran selesai.</p>
                <a class="mv-btn mv-btn--utama" style="margin-top:16px" href="{{ route('tiket.pilih', $t['slug']) }}">Pesan ulang tiket</a>
            @endif
        </section>

        <aside class="p-panel ringkasan-pesanan" aria-labelledby="judul-ringkasan">
            <header>
                <h2 id="judul-ringkasan">Ringkasan</h2>
                <span class="meta">{{ $metode[$p['metode']] }}</span>
            </header>
            <div class="p-baris nums">
                @foreach ($p['item'] as $i)
                    <div><span>{{ $i['jumlah'] }} × {{ $i['jenis'] }}</span><span class="kanan">{{ Katalog::rupiah($i['subtotal']) }}</span></div>
                @endforeach
            </div>
            <div class="total nums"><span>Total bayar</span><b>{{ Katalog::rupiah($p['total']) }}</b></div>
            <p class="kecil redup" style="margin-top:12px">Pemesan: {{ $p['pemesan']['nama'] }} ({{ $p['pemesan']['email'] }})</p>
        </aside>
    </div>
</div>
@endif
@endsection
