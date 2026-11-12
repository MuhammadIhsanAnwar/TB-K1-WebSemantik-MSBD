@extends('layouts.situs')
@php
    use App\Support\Katalog;
    use App\Http\Controllers\SitusController;
    $kelas = Katalog::KELAS[$e['jenis']];
    $tempatTerkait = collect($grafMini['simpul'])->keys()->map(fn ($s) => Katalog::get($s))
        ->filter(fn ($x) => $x['jenis'] === 'tempat')->values();
    $judulKelompok = ['tempat' => 'Tempat', 'tokoh' => 'Tokoh', 'kerajaan' => 'Kerajaan', 'peristiwa' => 'Peristiwa', 'budaya' => 'Budaya', 'kuliner' => 'Kuliner', 'transportasi' => 'Transportasi'];
@endphp
@section('judul', $e['nama'])
@section('deskripsi', $e['ringkas'])

@section('isi')
<div class="detail">
    <article class="detail__baca">
        <header class="detail__kepala">
            <nav class="remah" aria-label="Remah roti">
                <a href="{{ route('beranda') }}">Medan</a><span aria-hidden="true">›</span>
                <a href="{{ route('jelajah') }}">Jelajah</a><span aria-hidden="true">›</span>
                <span>{{ $kelas }}</span>
            </nav>
            <h1>{{ $e['nama'] }}</h1>
            <p class="detail__ringkas">{{ $e['ringkas'] }}</p>
            <div class="detail__tanda">
                <x-kelas :jenis="$e['jenis']" />
                @if (! empty($e['masa']))<span class="kecil">{{ $e['masa'] }}</span>@endif
                <span class="kecil">{{ count($tetangga) }} hubungan langsung</span>
            </div>
            <div class="detail__aksi">
                <a class="tombol tombol--hijau" href="{{ route('jelajah', ['dari' => $e['slug'], 'ke' => $tempatTerkait->first()['slug'] ?? (array_key_first(Katalog::tempat()) ?? $e['slug'])]) }}">Buka di jelajah graf</a>
                <a class="tombol tombol--garis" href="{{ route('tanya', ['q' => $e['nama']]) }}">Tanya soal {{ \Illuminate\Support\Str::limit($e['nama'], 18) }}</a>
            </div>
        </header>

        <section class="detail__seksi" aria-labelledby="judul-terhubung">
            <h2 id="judul-terhubung">Terhubung dengan</h2>
            @include('partials.terhubung', ['pusat' => $e['slug']])
        </section>

        @if ($tempatTerkait->isNotEmpty())
            <section class="detail__seksi" aria-labelledby="judul-tempat-terkait">
                <h2 id="judul-tempat-terkait">Tempat yang bisa didatangi</h2>
                <div class="rekomendasi">
                    @foreach ($tempatTerkait as $d)
                        @php $rantai = Katalog::benangMerah($e['slug'], $d['slug']); @endphp
                        <a href="{{ route('tempat', $d['slug']) }}">
                            @if ($d['foto'])<x-foto :berkas="$d['foto']" :alt="$d['nama']" :kecil="true" :segera="true" />@else<span class="tanpa-foto" aria-hidden="true"></span>@endif
                            <span>
                                <b>{{ $d['nama'] }}</b>
                                <span class="redup">{{ Katalog::KATEGORI[$d['kategori']] }} di {{ $d['kecamatan'] }}, <x-harga :tempat="$d" /></span>
                                @if ($rantai)<span class="alasan">{{ $rantai[0]['kalimat'] }}</span>@endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if (! empty($e['foto']) && \App\Support\Katalog::kredit($e['foto']))
            <p class="kecil redup detail__seksi">Foto: {{ \App\Support\Katalog::kredit($e['foto']) }}</p>
        @endif
    </article>

    <div class="detail__layar">
        @if (! empty($e['foto']))
            <x-foto :berkas="$e['foto']" :alt="$e['nama']" :utama="true" class="geser-pelan" />
            <div class="detail__bilah"><span>{{ $e['nama'] }}</span><span>{{ $kelas }}</span></div>
        @else
            <div class="layar-graf"><x-graf-mini :graf="$grafMini" :pusat="$e['slug']" :lebar="700" :tinggi="860" :maks2="10" /></div>
            <div class="detail__bilah"><span>Jaringan {{ $e['nama'] }}</span><span>{{ count($grafMini['simpul']) - 1 }} simpul dalam 2 langkah</span></div>
        @endif
    </div>
</div>
@endsection
