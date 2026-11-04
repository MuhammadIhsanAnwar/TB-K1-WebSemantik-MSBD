@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $berikut = $gelar['berikut'];
    $batasKini = collect(Katalog::GELAR)->filter(fn ($n) => $n === $gelar['nama'])->keys()->first() ?? 0;
    $persen = $berikut ? round(($poin['total'] - $batasKini) / max(1, $berikut['batas'] - $batasKini) * 100) : 100;
@endphp
@section('judul', 'Paspor Digital')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul">
        <div><h1>Paspor Digital</h1><p>Stempel {{ count($stempel) }} dari {{ $jumlahTempat }} tempat</p></div>
        <a class="p-btn" href="{{ route('peta') }}">Cari tempat di peta</a>
    </div>
    @include('partials.subnav-akun')
    <div class="paspor">
        <div>
            <section class="kartu-gelar" aria-labelledby="judul-gelar">
                <h2 class="sr-only" id="judul-gelar">Gelar dan poin</h2>
                <p class="gelar">{{ $gelar['nama'] }}</p>
                <p class="poin"><b>{{ $poin['total'] }}</b> poin</p>
                <div class="trek" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $persen }}" aria-label="Kemajuan menuju gelar berikutnya"><span style="width: {{ $persen }}%"></span></div>
                <p class="ket">{{ $berikut ? ($berikut['batas'] - $poin['total']).' poin lagi menuju '.$berikut['nama'] : 'Gelar tertinggi sudah kamu raih' }}</p>
                <details>
                    <summary>Semua gelar</summary>
                    <ol>@foreach (Katalog::GELAR as $batas => $nama)<li>{{ $nama }}, {{ $batas }} poin</li>@endforeach</ol>
                </details>
            </section>

            <section class="p-panel" style="margin-top:16px" aria-labelledby="judul-tantangan">
                <header><h2 id="judul-tantangan">Tantangan minggu ini</h2><span class="meta">{{ $tantangan['kini'] }}/{{ $tantangan['target'] }}</span></header>
                <p>{{ $tantangan['judul'] }}</p>
                <div class="ramai__trek" style="margin-top:12px;background:var(--p-raised)" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $tantangan['target'] }}" aria-valuenow="{{ $tantangan['kini'] }}" aria-label="Kemajuan tantangan"><div class="ramai__isi" style="width: {{ $tantangan['kini'] / $tantangan['target'] * 100 }}%"></div></div>
            </section>

            <section class="p-panel" aria-labelledby="judul-badge">
                <header><h2 id="judul-badge">Badge</h2></header>
                @forelse ($poin['badge'] as $b)
                    <div class="p-baris"><div><span class="dua"><b>{{ $b['nama'] }}</b><span>{{ $b['keterangan'] }}</span></span></div></div>
                @empty
                    <div class="p-kosong">Badge pertama: check-in di dua tempat bersejarah berbeda untuk mendapat Pemburu Sejarah dan bonus 25 poin.</div>
                @endforelse
            </section>
        </div>

        <div>
            <section class="p-panel" aria-labelledby="judul-stempel">
                <header><h2 id="judul-stempel">Stempel</h2><a class="p-tautan-kartu" href="{{ route('peta') }}">Lihat peta</a></header>
                <div class="grid-stempel">
                    @foreach ($stempel as $s)
                        @php $t = Katalog::get($s['tempat']); @endphp
                        <a href="{{ route('tempat', $t['slug']) }}">
                            <x-stempel :tempat="$t" :tanggal="\Illuminate\Support\Carbon::parse($s['waktu'])->format('d.m.y')" :id="'st-'.$s['id']" />
                            <span>{{ $t['nama'] }}</span>
                        </a>
                    @endforeach
                    @foreach ($belumDikunjungi as $t)
                        <a class="slot" href="{{ route('tempat', $t['slug']) }}">
                            <svg viewBox="0 0 200 200" aria-hidden="true"><circle cx="100" cy="100" r="92" fill="none" stroke="#b9b19c" stroke-width="3" stroke-dasharray="8 8"/><text x="100" y="112" text-anchor="middle" fill="#56635c" style="font:700 34px var(--font-text)">?</text></svg>
                            <span>Belum: {{ $t['nama'] }}</span>
                        </a>
                    @endforeach
                </div>
                @if (! $stempel)
                    <p class="kecil redup" style="margin-top:16px">Stempel didapat dengan check-in di lokasi. Buka halaman tempat saat kamu berada di sana, lalu tekan Check-in di sini.</p>
                @endif
            </section>

            <section class="p-panel" aria-labelledby="judul-tukar">
                <header><h2 id="judul-tukar">Tukar poin</h2><a class="p-tautan-kartu" href="{{ route('voucher') }}">Voucher saya</a></header>
                @forelse ($voucher as $v)
                    <div class="voucher-baris">
                        <span><b>{{ $v['nama'] }}</b><span>{{ $v['usaha'] }}, sisa {{ $v['stok'] }}, berlaku sampai {{ \Illuminate\Support\Carbon::parse($v['berlaku'])->locale('id')->translatedFormat('j M Y') }}</span></span>
                        <form method="post" action="{{ route('paspor.tukar', $v['id']) }}">
                            @csrf
                            <button class="p-btn {{ $poin['total'] >= $v['poin'] ? 'p-btn--utama' : '' }}" type="submit" @if ($poin['total'] < $v['poin']) aria-describedby="kurang-{{ $v['id'] }}" @endif>Tukar {{ $v['poin'] }} poin</button>
                            @if ($poin['total'] < $v['poin'])<span class="sr-only" id="kurang-{{ $v['id'] }}">Poinmu belum cukup</span>@endif
                        </form>
                    </div>
                @empty
                    <div class="p-kosong">Belum ada voucher dari mitra UMKM. Voucher muncul di sini setelah mitra membuatnya.</div>
                @endforelse
            </section>
        </div>
    </div>
</div>
@endsection
