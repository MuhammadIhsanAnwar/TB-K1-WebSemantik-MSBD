@extends('layouts.panel')
@php $hasil = session('hasil'); @endphp
@section('judul', 'Penukaran')

@section('isi')
<div class="p-judul">
    <div><h1>Penukaran</h1><p class="sub">Masukkan kode yang ditunjukkan wisatawan. Kode yang valid langsung ditandai sudah dipakai.</p></div>
</div>
<div class="p-grid">
    <section class="p-panel s5" aria-labelledby="judul-cek">
        <header><h2 id="judul-cek">Cek kode voucher</h2></header>
        <form class="p-form" method="post" action="{{ route('mitra.penukaran') }}" novalidate>
            @csrf
            <div class="p-field">
                <label for="kode">Kode voucher</label>
                <input id="kode" name="kode" value="{{ old('kode') }}" maxlength="20" placeholder="V-XXXXXX" autocomplete="off" autocapitalize="characters" class="kode" required @error('kode') aria-invalid="true" aria-describedby="g-kode" @enderror>
                @error('kode')<span class="galat" id="g-kode">{{ $message }}</span>@enderror
            </div>
            <div><button class="p-btn p-btn--utama p-btn--besar" type="submit">Periksa dan pakai</button></div>
        </form>
        @if ($hasil)
            <div class="p-hasil {{ $hasil['status'] }}" role="status"><b>{{ $hasil['judul'] }}</b><span>{{ $hasil['teks'] }}</span></div>
        @endif
    </section>
    <section class="p-panel s7" aria-labelledby="judul-riwayat">
        <header><h2 id="judul-riwayat">Riwayat penukaran</h2><span class="meta">{{ count($riwayat) }} penukaran</span></header>
        @if ($riwayat)
            <table class="p-tabel jadi-daftar">
                <thead><tr><th>Voucher</th><th>Kode</th><th class="n">Status</th></tr></thead>
                <tbody>
                    @foreach ($riwayat as $p)
                        <tr>
                            <td><b>{{ $p['nama'] }}</b><span class="sub">{{ \Illuminate\Support\Carbon::parse($p['waktu'])->locale('id')->translatedFormat('j M Y, H.i') }}</span></td>
                            <td class="sekunder kode">{{ $p['kode'] }}</td>
                            <td class="n"><span class="p-pill {{ $p['dipakai'] ? '' : 'warn' }}">{{ $p['dipakai'] ? 'Sudah dipakai' : 'Belum dipakai' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-kosong">Belum ada wisatawan yang menukar voucher usahamu.</div>
        @endif
    </section>
</div>
@endsection
