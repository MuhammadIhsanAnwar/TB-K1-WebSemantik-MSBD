@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $label = ['aktif' => 'Aktif', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'];
@endphp
@section('judul', 'Tiket saya')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-tiket-ruang">
    <div class="alat__judul">
        <div>
            <h1>Tiket saya</h1>
            <p>Daftar e-tiket siap pakai dan pesanan yang menunggu pembayaran.</p>
        </div>
        <a class="mv-btn mv-btn--utama mv-btn--kecil" href="{{ route('beranda') }}#tiket">Pesan tiket baru</a>
    </div>

    @include('partials.subnav-akun')

    <nav class="p-tabs" aria-label="Status tiket">
        @foreach ($label as $k => $l)
            <a href="{{ route('tiket.saya', ['tab' => $k]) }}" @if ($tab === $k) aria-current="true" @endif>
                {{ $l }}<span class="n nums">{{ $kelompok[$k]->count() }}</span>
            </a>
        @endforeach
    </nav>

    @if ($kelompok[$tab]->isNotEmpty())
        <div class="p-panel">
            <table class="p-tabel jadi-daftar">
                <thead>
                    <tr>
                        <th>Tujuan wisata</th>
                        <th>Tanggal kunjungan</th>
                        <th>Rincian tiket</th>
                        <th class="n">Total</th>
                        <th class="n">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($kelompok[$tab] as $p)
                        @php $t = Katalog::get($p['tempat']); @endphp
                        <tr>
                            <td>
                                <a class="nama" href="{{ $p['status'] === 'lunas' ? route('tiket.e', $p['kode']) : route('pesanan', $p['kode']) }}">{{ $t['nama'] }}</a>
                                <span class="sub nums">{{ $p['kode'] }}</span>
                            </td>
                            <td class="sekunder">{{ \Illuminate\Support\Carbon::parse($p['tanggal'])->locale('id')->translatedFormat('j M Y') }}</td>
                            <td class="sekunder">{{ collect($p['item'])->map(fn ($i) => $i['jumlah'].' '.mb_strtolower($i['jenis']))->implode(', ') }}</td>
                            <td class="n nums">{{ Katalog::rupiah($p['total']) }}</td>
                            <td class="n">
                                @php $st = ! empty($p['dipakai']) ? ['Sudah dipakai', ''] : ['lunas' => ['Siap dipakai', 'pos'], 'menunggu' => ['Menunggu bayar', 'warn'], 'kedaluwarsa' => ['Kedaluwarsa', 'neg'], 'dibatalkan' => ['Dibatalkan', '']][$p['status']]; @endphp
                                <span class="p-pill {{ $st[1] }}">{{ $st[0] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="kosong" style="background:var(--mv-putih);padding:32px 24px">
            <p>{{ ['aktif' => 'Belum ada e-tiket aktif atau pesanan yang menunggu pembayaran.', 'selesai' => 'Belum ada riwayat tiket yang selesai dipakai.', 'dibatalkan' => 'Tidak ada pesanan tiket yang dibatalkan.'][$tab] }}</p>
            @if ($tab === 'aktif')
                <a class="mv-btn mv-btn--utama" href="{{ route('beranda') }}#tiket" style="margin-top:16px">Lihat tempat bertiket</a>
            @endif
        </div>
    @endif
</div>
@endsection
