@extends('layouts.situs')
@section('judul', 'Kredit foto')

@section('isi')
<header class="kepala-halaman kepala-halaman--besar">
    <h1>Kredit foto</h1>
    <p>Semua foto di MedanVisit berasal dari koleksi terbuka. Foto lama dari arsip KITLV (Universitas Leiden) dan Nationaal Archief; foto kini dari kontributor Wikimedia Commons.</p>
</header>
<div class="isi-halaman">
    <ul class="daftar-kredit">
        @foreach ($kredit as $berkas => $k)
            <li>
                <x-foto :berkas="$berkas" :alt="$k['title']" :kecil="is_file(public_path('images/foto/kecil/'.pathinfo($berkas, PATHINFO_FILENAME).'.jpg'))" :segera="$loop->index < 6" />
                <div>
                    <p><b>{{ $k['title'] }}</b></p>
                    <p class="kecil">{{ $k['creator'] }}{{ $k['date'] ? ', '.$k['date'] : '' }}. Lisensi: <a href="{{ $k['licenseUrl'] }}" rel="noopener">{{ $k['license'] }}</a>. <a href="{{ $k['page'] }}" rel="noopener">Halaman sumber</a>.</p>
                    @php
                        // catatan dari alat pengunduh berbahasa Inggris; catatan share-alike diterjemahkan, catatan tim tetap
                        $catatan = collect($k['notes'] ?? [])->map(fn ($n) => str_starts_with($n, 'Share-alike')
                            ? 'Lisensi share-alike: versi yang diubah (diwarnai ulang, digabung dengan gambar lain) harus dibagikan dengan lisensi yang sama.'
                            : $n)->unique();
                    @endphp
                    @if ($catatan->isNotEmpty())<p class="kecil redup">{{ $catatan->implode(' ') }}</p>@endif
                </div>
            </li>
        @endforeach
    </ul>
    <p class="kecil redup" style="margin-top:40px">Peta dasar interaktif © kontributor OpenStreetMap. Logo MedanVisit milik tim MedanVisit.</p>
</div>
@endsection
