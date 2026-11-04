@extends('layouts.panel')
@php
    use App\Support\Katalog;
    $maks = max(1, max($perKecamatan));
    $tingkat = fn ($n) => $n === 0 ? 0 : min(4, (int) ceil($n / $maks * 4));
    $kerja = count($ulasanMenunggu) + count($celah) + $tanpaSumber->count();
@endphp
@section('judul', 'Ringkasan')

@section('isi')
<div class="p-judul">
    <div>
        <h1>Ringkasan</h1>
        <p class="sub">{{ $kerja ? $kerja.' hal menunggu kurator: '.count($ulasanMenunggu).' ulasan, '.count($celah).' kata kunci tanpa hasil, '.$tanpaSumber->count().' relasi belum terverifikasi.' : 'Tidak ada antrean. Semua ulasan dan kata kunci sudah ditangani.' }}</p>
    </div>
    <div class="p-judul__aksi">
        <nav class="p-seg" aria-label="Periode">
            @foreach ([7, 30, 90] as $p)<a href="{{ route('admin.ringkasan', ['periode' => $p]) }}" @if ($periode === $p) aria-current="true" @endif>{{ $p }} hari</a>@endforeach
        </nav>
        <a class="p-btn p-btn--utama" href="{{ route('admin.ulasan') }}">Periksa ulasan</a>
    </div>
</div>

<div class="p-kpi">
    <div><div class="label">Check-in</div><div class="nilai">{{ number_format($checkin, 0, ',', '.') }}</div><div class="ket">{{ $periode }} hari terakhir</div></div>
    <div><div class="label">Wisatawan aktif</div><div class="nilai">{{ number_format($pengguna, 0, ',', '.') }}</div><div class="ket">pernah check-in dalam periode ini</div></div>
    <div><div class="label">Tiket terjual</div><div class="nilai">{{ number_format($tiket, 0, ',', '.') }}</div><div class="ket">{{ Katalog::rupiah($pendapatan) }} dari pesanan lunas</div></div>
    <div><div class="label">Voucher ditukar</div><div class="nilai">{{ number_format($penukaran, 0, ',', '.') }}</div><div class="ket">di semua mitra</div></div>
</div>

<div class="p-grid rata">
    <section class="p-panel s7" aria-labelledby="judul-antrean">
        <header><h2 id="judul-antrean">Antrean kerja kurator</h2><span class="meta">dari penggunaan prototipe</span></header>
        <h3 style="font-size:13px;color:var(--p-muted);font-weight:500;margin-bottom:4px">Ulasan menunggu moderasi</h3>
        @if ($ulasanMenunggu)
            <div class="p-baris">
                @foreach (array_slice($ulasanMenunggu, 0, 4) as $ul)
                    <div><span class="dua"><b>{{ Katalog::get($ul['tempat'])['nama'] }}</b><span>{{ $ul['nama'] }} · {{ \Illuminate\Support\Str::limit($ul['teks'], 60) }}</span></span><a class="p-btn kanan" href="{{ route('admin.ulasan') }}">Periksa</a></div>
                @endforeach
            </div>
        @else
            <div class="p-kosong" style="min-height:44px">Tidak ada ulasan yang menunggu.</div>
        @endif
        <h3 style="font-size:13px;color:var(--p-muted);font-weight:500;margin:20px 0 4px">Dicari tetapi tanpa hasil · dari {{ $jumlahCari }} pencarian</h3>
        @if ($celah)
            <div class="p-baris">
                @foreach (array_slice($celah, 0, 5, true) as $q => $n)
                    <div><span>“{{ $q }}”</span><span class="kanan num">{{ $n }}×</span></div>
                @endforeach
            </div>
        @else
            <div class="p-kosong" style="min-height:44px">Semua pencarian menemukan hasil.</div>
        @endif
        <h3 style="font-size:13px;color:var(--p-muted);font-weight:500;margin:20px 0 4px">Relasi bersumber survei, perlu verifikasi</h3>
        <div class="p-baris">
            @foreach ($tanpaSumber->take(4) as $r)
                <div><span>{{ Katalog::kalimat($r, $r[0]) }}</span><a class="p-tautan-kartu kanan" href="{{ route('admin.relasi', ['predikat' => $r[1]]) }}">Buka</a></div>
            @endforeach
        </div>
    </section>

    <section class="p-panel s5" aria-labelledby="judul-sebaran">
        <header><h2 id="judul-sebaran">Check-in per kecamatan</h2><span class="meta">{{ $periode }} hari</span></header>
        <div class="p-sebaran" role="img" aria-label="Peta petak 21 kecamatan Kota Medan, warna makin gelap makin banyak check-in">
            @foreach ($perKecamatan as $k => $n)
                <span data-tingkat="{{ $tingkat($n) }}" title="{{ $k }}: {{ $n }} check-in">{{ str_replace('Medan ', '', $k) }}</span>
            @endforeach
        </div>
        <div class="p-batang" style="margin-top:16px">
            @foreach (array_slice($perKecamatan, 0, 6, true) as $k => $n)
                <div class="p-batang__baris {{ $n ? '' : 'nol' }}">
                    <span class="p-batang__isi" style="width: {{ $n ? max(4, $n / $maks * 88) : 0 }}%"></span>
                    <span>{{ $k }}</span><span>{{ $n }}</span>
                </div>
            @endforeach
        </div>
        @if (! $checkin)<p class="kecil" style="color:var(--p-muted);margin-top:8px">Belum ada check-in dalam {{ $periode }} hari. Angka muncul setelah wisatawan check-in di lokasi.</p>@endif
    </section>

    <section class="p-panel s7" aria-labelledby="judul-lengkapi">
        <header><h2 id="judul-lengkapi">Tempat yang perlu dilengkapi</h2><span class="meta">{{ $tanpaFoto->count() }} tanpa foto</span></header>
        <div class="p-baris">
            @foreach ($tanpaFoto as $t)
                <div><span class="dua"><b>{{ $t['nama'] }}</b><span>{{ Katalog::KATEGORI[$t['kategori']] }} · belum ada foto berlisensi</span></span><a class="p-btn kanan" href="{{ route('admin.entitas.ubah', $t['slug']) }}">Ubah</a></div>
            @endforeach
        </div>
    </section>
    <section class="p-panel s5" aria-labelledby="judul-usulan">
        <header><h2 id="judul-usulan">Usulan relasi baru</h2><a class="p-tautan-kartu" href="{{ route('admin.relasi') }}">Editor relasi</a></header>
        @if ($usulan)
            <div class="p-baris">
                @foreach (array_slice(array_reverse($usulan), 0, 4) as $us)
                    <div><span class="dua"><b>{{ Katalog::get($us['subjek'])['nama'] }} {{ Katalog::LABEL_RELASI[$us['predikat']] }} {{ Katalog::get($us['objek'])['nama'] }}</b><span>oleh {{ $us['oleh'] }}</span></span></div>
                @endforeach
            </div>
        @else
            <div class="p-kosong">Belum ada usulan relasi.</div>
        @endif
    </section>
</div>
@endsection
