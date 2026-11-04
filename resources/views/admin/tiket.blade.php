@extends('layouts.panel')
@php use App\Support\Katalog; $hasil = session('hasil'); $lunas = $pesanan->where('status', 'lunas'); @endphp
@section('judul', 'Tiket dan transaksi')

@section('isi')
<div class="p-judul"><div><h1>Tiket dan transaksi</h1><p class="sub">{{ $lunas->count() }} pesanan lunas, {{ Katalog::rupiah($lunas->sum('total')) }}. Pembayaran masih simulasi sampai Midtrans tersambung.</p></div></div>
<div class="p-grid">
    <section class="p-panel s4" aria-labelledby="judul-validasi">
        <header><h2 id="judul-validasi">Validasi di loket</h2></header>
        <form class="p-form" method="post" action="{{ route('admin.tiket.validasi') }}" novalidate>
            @csrf
            <div class="p-field"><label for="kode">Kode e-tiket</label><input id="kode" name="kode" class="kode" placeholder="MV-XXXX-0000" autocomplete="off" value="{{ old('kode') }}" @error('kode') aria-invalid="true" aria-describedby="g-kode" @enderror>@error('kode')<span class="galat" id="g-kode">{{ $message }}</span>@enderror</div>
            <div><button class="p-btn p-btn--utama p-btn--besar" type="submit">Periksa tiket</button></div>
        </form>
        @if ($hasil)<div class="p-hasil {{ $hasil['status'] }}" role="status"><b>{{ $hasil['judul'] }}</b><span>{{ $hasil['teks'] }}</span></div>@endif
        <div class="p-pemisah"></div>
        <h2>Harga tiket</h2>
        <div class="p-baris" style="margin-top:6px">
            @foreach ($bertiket as $t)
                <div><span class="dua"><b>{{ $t['nama'] }}</b><span>{{ collect($t['tiket'])->map(fn ($h, $j) => $j.' '.Katalog::rupiah($h))->implode(' · ') }}</span></span><a class="p-tautan-kartu kanan" href="{{ route('admin.entitas.ubah', $t['slug']) }}">Ubah</a></div>
            @endforeach
        </div>
    </section>
    <section class="p-panel s8" aria-labelledby="judul-pesanan">
        <header><h2 id="judul-pesanan">Pesanan</h2><span class="meta">{{ $pesanan->count() }} pesanan</span></header>
        @if ($pesanan->isNotEmpty())
            <table class="p-tabel jadi-daftar">
                <thead><tr><th>Kode</th><th>Pemesan</th><th>Tempat dan tanggal</th><th class="n">Total</th><th class="n">Status</th></tr></thead>
                <tbody>
                    @foreach ($pesanan as $p)
                        @php $st = ! empty($p['dipakai']) ? ['Dipakai', ''] : ['lunas' => ['Lunas', 'pos'], 'menunggu' => ['Menunggu', 'warn'], 'kedaluwarsa' => ['Kedaluwarsa', 'neg'], 'dibatalkan' => ['Dibatalkan', '']][$p['status']]; @endphp
                        <tr>
                            <td><span class="kode">{{ $p['kode'] }}</span></td>
                            <td class="sekunder">{{ $p['pemesan']['nama'] }}<span class="sub">{{ $p['pemesan']['email'] }}</span></td>
                            <td class="sekunder">{{ Katalog::get($p['tempat'])['nama'] }}<span class="sub">{{ \Illuminate\Support\Carbon::parse($p['tanggal'])->locale('id')->translatedFormat('j M Y') }}</span></td>
                            <td class="n">{{ Katalog::rupiah($p['total']) }}</td>
                            <td class="n"><span class="p-pill {{ $st[1] }}">{{ $st[0] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-kosong">Belum ada pesanan tiket.</div>
        @endif
    </section>
</div>
@endsection
