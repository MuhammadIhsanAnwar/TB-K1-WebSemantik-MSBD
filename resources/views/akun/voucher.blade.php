@extends('layouts.situs')
@section('judul', 'Voucher saya')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul"><div><h1>Voucher saya</h1><p>Tunjukkan kode ke mitra saat membayar.</p></div><a class="p-btn" href="{{ route('paspor') }}">Tukar poin lagi</a></div>
    @include('partials.subnav-akun')
    @forelse ($penukaran as $p)
        <div class="kupon">
            <div>
                <h2>{{ $p['nama'] }}</h2>
                <p class="redup">{{ $p['usaha'] }}, ditukar {{ $p['poin'] }} poin</p>
                <p class="kode" style="margin-top:8px">{{ $p['kode'] }}</p>
            </div>
            <span class="p-pill {{ $p['dipakai'] ? '' : 'pos' }}">{{ $p['dipakai'] ? 'Sudah dipakai' : 'Aktif' }}</span>
        </div>
    @empty
        <div class="kosong"><p>Belum ada voucher. Kumpulkan poin dengan check-in, lalu tukar di Paspor Digital.</p><a class="tombol" href="{{ route('paspor') }}">Buka paspor</a></div>
    @endforelse
</div>
@endsection
