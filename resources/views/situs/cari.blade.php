@extends('layouts.situs')
@php
    use App\Support\Katalog;
    use App\Http\Controllers\SitusController;
    $semua = array_sum($perJenis);
@endphp
@section('judul', $q ? 'Hasil untuk “'.$q.'”' : 'Cari')

@section('isi')
<header class="kepala-halaman">
    <h1>{{ $q ? 'Hasil untuk “'.$q.'”' : 'Cari di MedanVisit' }}</h1>
    <form class="tanya-medan kepala-halaman__cari" action="{{ route('cari') }}" method="get" role="search" style="max-width: none">
        <label><span class="sr-only">Cari tempat, tokoh, atau kuliner</span><input type="search" name="q" value="{{ $q }}" placeholder="istana, kuliner melayu, tjong a fie" required></label>
        <button class="tombol" type="submit">Cari</button>
    </form>
</header>

<div class="hasil-cari">
    <div class="hasil-cari__utama">
        @if ($q && $semua)
            <nav class="saring" aria-label="Saring jenis">
                <a href="{{ route('cari', ['q' => $q, 'catat' => 0]) }}" @if (! $jenis) aria-current="true" @endif>Semua <span class="nums">{{ $semua }}</span></a>
                @foreach ($perJenis as $j => $n)
                    <a href="{{ route('cari', ['q' => $q, 'jenis' => $j, 'catat' => 0]) }}" @if ($jenis === $j) aria-current="true" @endif><span class="kelas" data-kelas="{{ $j }}" style="color:inherit">{{ Katalog::KELAS[$j] }}</span> <span class="nums">{{ $n }}</span></a>
                @endforeach
            </nav>
            @foreach ($hasil as $h)
                @php $rel = collect(Katalog::tetangga($h['slug'], false))->first(); @endphp
                <a class="butir-hasil" href="{{ SitusController::urlEntitas($h) }}">
                    <span>
                        <h2>{{ $h['nama'] }}</h2>
                        <span class="jenis"><x-kelas :jenis="$h['jenis']" :label="$h['jenis'] === 'tempat' ? Katalog::KATEGORI[$h['kategori']] : null" />@if (! empty($h['kecamatan']))<span class="kecil">{{ $h['kecamatan'] }}</span>@endif</span>
                        <p>{{ $h['ringkas'] }}</p>
                        @if ($rel)<p class="relasi">{{ Katalog::kalimat($rel['relasi'], $h['slug']) }}</p>@endif
                    </span>
                    @if (! empty($h['foto']))<x-foto :berkas="$h['foto']" :alt="$h['nama']" :kecil="true" :segera="true" />@else<span class="tanpa-foto" aria-hidden="true"></span>@endif
                </a>
            @endforeach
        @elseif ($q)
            <div class="kosong">
                <p><b>Belum ada hasil untuk “{{ $q }}”.</b></p>
                @if ($koreksi)
                    <p>Mungkin maksudmu <a class="tautan" href="{{ route('cari', ['q' => $koreksi]) }}">{{ $koreksi }}</a>?</p>
                @else
                    <p>Kata kunci ini dicatat untuk kurator, supaya tempatnya bisa ditambahkan. Coba kata lain, misalnya nama tempat, kuliner, atau tokoh.</p>
                @endif
                <a class="tombol" href="{{ route('tanya', ['q' => $q]) }}">Tanyakan ke asisten</a>
            </div>
        @else
            <p class="lead">Ketik nama tempat, kuliner, tokoh, atau kecamatan. Salah ketik tetap ketemu.</p>
            <div class="rekomendasi" style="margin-top:32px">
                @foreach ($cepat as $c)
                    <a href="{{ route('tempat', $c['slug']) }}">
                        @if ($c['foto'])<x-foto :berkas="$c['foto']" :alt="$c['nama']" />@else<span class="tanpa-foto" aria-hidden="true"></span>@endif
                        <span><b>{{ $c['nama'] }}</b><span class="redup">{{ Katalog::KATEGORI[$c['kategori']] }} · {{ $c['kecamatan'] }}</span></span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
    <aside class="hasil-cari__samping" aria-labelledby="judul-asisten">
        <div class="ajak-asisten">
            <h2 id="judul-asisten">Butuh jawaban yang lebih lengkap?</h2>
            <p>Asisten menyusun jawaban dari data yang sama: harga tiket, jarak, rute, dan kuliner di sekitar.</p>
            <a class="tombol" href="{{ route('tanya', $q ? ['q' => $q] : []) }}">Tanya asisten</a>
        </div>
    </aside>
</div>
@endsection
