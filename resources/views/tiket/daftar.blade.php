@extends('layouts.situs')
@php use App\Support\Katalog; $label = ['aktif' => 'Aktif', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan']; @endphp
@section('judul', 'Tiket saya')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul"><div><h1>Tiket saya</h1><p>E-tiket dan pesanan yang belum dibayar.</p></div></div>
    @include('partials.subnav-akun')
    <nav class="p-tabs" aria-label="Status tiket">
        @foreach ($label as $k => $l)
            <a href="{{ route('tiket.saya', ['tab' => $k]) }}" @if ($tab === $k) aria-current="true" @endif>{{ $l }}<span class="n">{{ $kelompok[$k]->count() }}</span></a>
        @endforeach
    </nav>
    @if ($kelompok[$tab]->isNotEmpty())
        <div class="p-panel">
            <table class="p-tabel jadi-daftar">
                <thead><tr><th>Tempat</th><th>Tanggal</th><th>Tiket</th><th class="n">Total</th><th class="n">Status</th></tr></thead>
                <tbody>
                    @foreach ($kelompok[$tab] as $p)
                        @php $t = Katalog::get($p['tempat']); @endphp
                        <tr>
                            <td><a class="nama" href="{{ $p['status'] === 'lunas' ? route('tiket.e', $p['kode']) : route('pesanan', $p['kode']) }}">{{ $t['nama'] }}</a><span class="sub kode">{{ $p['kode'] }}</span></td>
                            <td class="sekunder">{{ \Illuminate\Support\Carbon::parse($p['tanggal'])->locale('id')->translatedFormat('j M Y') }}</td>
                            <td class="sekunder">{{ collect($p['item'])->map(fn ($i) => $i['jumlah'].' '.mb_strtolower($i['jenis']))->implode(', ') }}</td>
                            <td class="n">{{ Katalog::rupiah($p['total']) }}</td>
                            <td class="n">
                                @php $st = ! empty($p['dipakai']) ? ['Sudah dipakai', ''] : ['lunas' => ['Lunas', 'pos'], 'menunggu' => ['Menunggu pembayaran', 'warn'], 'kedaluwarsa' => ['Kedaluwarsa', 'neg'], 'dibatalkan' => ['Dibatalkan', '']][$p['status']]; @endphp
                                <span class="p-pill {{ $st[1] }}">{{ $st[0] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="kosong">
            <p>{{ ['aktif' => 'Belum ada tiket aktif.', 'selesai' => 'Belum ada tiket yang sudah dipakai.', 'dibatalkan' => 'Tidak ada pesanan yang dibatalkan.'][$tab] }}</p>
            @if ($tab === 'aktif')<a class="tombol" href="{{ route('peta') }}">Cari tempat bertiket</a>@endif
        </div>
    @endif
</div>
@endsection
