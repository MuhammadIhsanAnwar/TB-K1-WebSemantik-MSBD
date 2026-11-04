@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $fmtMenit = fn ($m) => intdiv($m, 60) ? intdiv($m, 60).' j '.($m % 60).' m' : $m.' m';
    $rute = collect($singgah)->map(fn ($s) => ['nama' => $s['tempat']['nama'], 'lat' => $s['tempat']['lat'], 'lng' => $s['tempat']['lng'], 'url' => route('tempat', $s['tempat']['slug']), 'harga' => Katalog::rupiah(Katalog::hargaTermurah($s['tempat'])), 'tiket' => ! empty($s['tempat']['tiket']) ? route('tiket.pilih', $s['tempat']['slug']) : null])->values();
    $totalTiket = collect($tiket)->sum(fn ($t) => Katalog::hargaTermurah($t));
@endphp
@section('judul', $j['nama'])
@section('deskripsi', $j['ringkas'])
@section('foto-kaki', $j['foto'])

@section('isi')
<div class="jalur-detail">
    <article class="jalur-detail__baca">
        <nav class="remah" aria-label="Remah roti"><a href="{{ route('beranda') }}">Medan</a><span aria-hidden="true">›</span><a href="{{ route('jalur') }}">Jalur tematik</a></nav>
        <h1 style="font-size:var(--s-head);margin-top:40px">{{ $j['nama'] }}</h1>
        <p class="detail__ringkas">{{ $j['ringkas'] }}</p>
        <div class="ringkas-angka">
            <p><b>{{ count($singgah) }} tempat</b>, {{ number_format($j['meter'] / 1000, 1, ',', '.') }} km jalan kaki dan becak, {{ $fmtMenit($j['menit']) }} termasuk singgah.</p>
        </div>
        <div class="detail__aksi">
            <a class="tombol" href="{{ route('tempat', $singgah[0]['tempat']['slug']) }}">Mulai dari {{ $singgah[0]['tempat']['nama'] }}</a>
            <a class="tombol tombol--garis" href="{{ route('rencana', ['minat' => array_values(array_unique(array_map(fn ($s) => $s['tempat']['kategori'], $singgah))), 'durasi' => max(2, min(8, (int) ceil($j['menit'] / 120) * 2)), 'moda' => ['jalan', 'becak'], 'mulai' => $singgah[0]['tempat']['slug']]) }}">Ubah jadi rencanaku</a>
        </div>

        <ol class="lini">
            @foreach ($singgah as $i => $s)
                @if ($s['pindah'])
                    <li class="lini__pindah" aria-label="Perpindahan"><span></span><span>{{ $s['pindah']['moda'] }} {{ number_format($s['pindah']['meter'], 0, ',', '.') }} m, sekitar {{ $s['pindah']['menit'] }} menit{{ $s['pindah']['biaya'] ? ', ± '.Katalog::rupiah($s['pindah']['biaya']) : '' }}</span></li>
                @endif
                <li class="lini__singgah">
                    <span class="lini__nomor">{{ $i + 1 }}</span>
                    <div>
                        <h2 style="font-size:1.75rem"><a href="{{ route('tempat', $s['tempat']['slug']) }}">{{ $s['tempat']['nama'] }}</a></h2>
                        <p class="redup kecil" style="margin-top:4px">Singgah {{ $s['tempat']['singgah'] }} menit · <x-harga :tempat="$s['tempat']" /></p>
                        <p style="margin-top:10px">{{ $s['tempat']['cerita'][0] }}</p>
                        @if ($i > 0)
                            @php $rantai = Katalog::benangMerah($singgah[$i - 1]['tempat']['slug'], $s['tempat']['slug']); @endphp
                            @if ($rantai && count($rantai) <= 3)
                                <p class="alasan" style="margin-top:10px;color:var(--gold-text)">{{ collect($rantai)->pluck('kalimat')->implode(' ') }}</p>
                            @endif
                        @endif
                        @if ($s['tempat']['foto'])<x-foto :berkas="$s['tempat']['foto']" :alt="$s['tempat']['nama']" />@endif
                    </div>
                </li>
            @endforeach
        </ol>

        @if ($tiket)
            <section class="tiket-blok" aria-labelledby="judul-tiket-jalur">
                <div>
                    <h2 id="judul-tiket-jalur">{{ count($tiket) }} tempat di jalur ini bertiket</h2>
                    <p class="tiket-blok__harga">{{ Katalog::rupiah($totalTiket) }}</p>
                    <p class="tiket-blok__rincian">per orang dewasa, {{ collect($tiket)->pluck('nama')->implode(' dan ') }} <span class="contoh" style="color:inherit">harga contoh</span></p>
                </div>
                <div style="display:grid;gap:8px">
                    @foreach ($tiket as $t)
                        <a class="tombol tombol--kecil" href="{{ route('tiket.pilih', $t['slug']) }}">Tiket {{ $t['nama'] }}</a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="detail__seksi" aria-labelledby="judul-lain">
            <h2 id="judul-lain">Jalur lain</h2>
            <div class="rekomendasi">
                @foreach ($lain as $l)
                    <a href="{{ route('jalur.detail', $l['slug']) }}"><x-foto :berkas="$l['foto']" :alt="$l['nama']" /><span><b>{{ $l['nama'] }}</b><span class="redup">{{ count($l['singgah']) }} tempat · {{ number_format($l['meter'] / 1000, 1, ',', '.') }} km</span></span></a>
                @endforeach
            </div>
        </section>
    </article>
    <div class="jalur-detail__peta" data-peta data-peta-mode="rute" data-rute="{{ json_encode($rute) }}" tabindex="0" aria-label="Peta rute {{ $j['nama'] }}">
        <p class="peta-diam">Peta rute dimuat di sini.</p>
    </div>
</div>
@endsection
