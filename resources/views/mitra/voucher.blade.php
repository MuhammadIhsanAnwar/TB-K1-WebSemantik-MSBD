@extends('layouts.panel')
@section('judul', 'Voucher & stok')

@section('isi')
<div class="p-judul">
    <div><h1>Voucher & stok</h1><p class="sub">Wisatawan menukar poin Paspor Digital dengan voucher ini. Stok berkurang setiap kali ditukar.</p></div>
</div>
<div class="p-grid">
    <section class="p-panel s7" aria-labelledby="judul-daftar">
        <header><h2 id="judul-daftar">Voucher usahamu</h2><span class="meta">{{ count($voucher) }} voucher</span></header>
        @if ($voucher)
            <table class="p-tabel jadi-daftar">
                <thead><tr><th>Voucher</th><th class="n">Poin</th><th class="n">Ditukar</th><th class="n">Sisa stok</th></tr></thead>
                <tbody>
                    @foreach ($voucher as $v)
                        <tr>
                            <td><b>{{ $v['nama'] }}</b><span class="sub">Berlaku sampai {{ \Illuminate\Support\Carbon::parse($v['berlaku'])->locale('id')->translatedFormat('j M Y') }}</span></td>
                            <td class="n sekunder">{{ $v['poin'] }}</td>
                            <td class="n sekunder">{{ $v['ditukar'] }}</td>
                            <td class="n">@if ($v['stok'] < 1)<span class="p-pill neg">Habis</span>@else{{ $v['stok'] }} <span style="color:var(--p-muted)">/ {{ $v['stok_awal'] }}</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-kosong">Belum ada voucher. Buat voucher pertama di samping.</div>
        @endif
    </section>
    <section class="p-panel s5" aria-labelledby="judul-buat">
        <header><h2 id="judul-buat">Buat voucher</h2></header>
        <form class="p-form" method="post" action="{{ route('mitra.voucher') }}" novalidate>
            @csrf
            <div class="p-field">
                <label for="nama">Isi voucher</label>
                <input id="nama" name="nama" value="{{ old('nama') }}" maxlength="60" placeholder="Diskon 10% bika ambon" required @error('nama') aria-invalid="true" aria-describedby="g-nama" @enderror>
                @error('nama')<span class="galat" id="g-nama">{{ $message }}</span>@enderror
            </div>
            <div class="p-dua">
                <div class="p-field">
                    <label for="poin">Poin yang ditukar</label>
                    <input id="poin" name="poin" value="{{ old('poin', 50) }}" inputmode="numeric" class="num" aria-describedby="b-poin @error('poin') g-poin @enderror" @error('poin') aria-invalid="true" @enderror>
                    <span class="bantu" id="b-poin">10–500. Satu check-in memberi 10 poin.</span>
                    @error('poin')<span class="galat" id="g-poin">{{ $message }}</span>@enderror
                </div>
                <div class="p-field">
                    <label for="stok">Stok</label>
                    <input id="stok" name="stok" value="{{ old('stok', 20) }}" inputmode="numeric" class="num" @error('stok') aria-invalid="true" aria-describedby="g-stok" @enderror>
                    @error('stok')<span class="galat" id="g-stok">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="p-field">
                <label for="berlaku">Berlaku sampai</label>
                <input id="berlaku" type="date" name="berlaku" value="{{ old('berlaku', now()->addMonth()->toDateString()) }}" min="{{ now()->addDay()->toDateString() }}" @error('berlaku') aria-invalid="true" aria-describedby="g-berlaku" @enderror>
                @error('berlaku')<span class="galat" id="g-berlaku">{{ $message }}</span>@enderror
            </div>
            <div><button class="p-btn p-btn--utama" type="submit">Terbitkan voucher</button></div>
        </form>
    </section>
</div>
@endsection
