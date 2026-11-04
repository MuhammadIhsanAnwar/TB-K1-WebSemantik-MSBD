@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $kelas = [
        'tempat' => ['TempatWisata', 450, 210], 'tokoh' => ['Tokoh', 160, 90], 'kerajaan' => ['Kerajaan', 160, 330],
        'peristiwa' => ['Peristiwa', 450, 50], 'budaya' => ['Budaya', 760, 90], 'kuliner' => ['Kuliner', 760, 330],
        'wilayah' => ['Kecamatan', 450, 380], 'transportasi' => ['ModaTransportasi', 160, 210],
    ];
    $warna = ['tempat' => '#e3a51c', 'tokoh' => '#8e2c36', 'kerajaan' => '#9b6b3d', 'peristiwa' => '#2f6690', 'budaya' => '#6a4c9c', 'kuliner' => '#c9541e', 'wilayah' => '#2e7d4f', 'transportasi' => '#2f8a8a'];
    $sisi = [
        ['tempat', 'tokoh', 'dibangunOleh'], ['tokoh', 'kerajaan', 'memimpin'], ['tokoh', 'peristiwa', 'terlibatDalam'],
        ['tempat', 'budaya', 'merepresentasikan'], ['kuliner', 'budaya', 'khasDari'], ['tempat', 'kuliner', 'menjual'],
        ['tempat', 'wilayah', 'beradaDi'], ['tempat', 'transportasi', 'dilayaniOleh'], ['tempat', 'peristiwa', 'memilikiSejarah'],
    ];
    $contohJson = collect($contoh)->map(fn ($c) => ['query' => $c['query']])->all();
    $pertama = array_key_first($contoh);
@endphp
@section('judul', 'Data terbuka & SPARQL')
@section('foto-kaki', 'peta-medan-1930-kota-lama.jpg')

@section('isi')
<div class="alat">
    <div class="alat__judul">
        <div>
            <h1>Data terbuka MedanVisit</h1>
            <p>{{ array_sum($jumlah) }} entitas dan {{ $relasi }} hubungan, tersusun sebagai graf pengetahuan dengan ontologi sendiri dan kosakata schema.org.</p>
        </div>
    </div>

    <div class="p-grid" style="margin-bottom:24px">
        <section class="p-panel s7" aria-labelledby="judul-ontologi">
            <header><h2 id="judul-ontologi">Ontologi</h2><span class="meta">{{ count($kelas) + 1 }} kelas · {{ count(Katalog::LABEL_RELASI) }} properti</span></header>
            <svg class="ontologi" viewBox="0 0 920 430" role="img" aria-label="Diagram ontologi: TempatWisata dibangun oleh Tokoh, Tokoh memimpin Kerajaan dan terlibat dalam Peristiwa, TempatWisata mencerminkan Budaya, Kuliner khas dari Budaya, TempatWisata berada di Kecamatan dan dilayani ModaTransportasi.">
                @foreach ($sisi as [$a, $b, $p])
                    @php [, $x1, $y1] = $kelas[$a]; [, $x2, $y2] = $kelas[$b]; @endphp
                    <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="#b9c4bf" stroke-width="1.6"/>
                    <text x="{{ ($x1 + $x2) / 2 }}" y="{{ ($y1 + $y2) / 2 - 6 }}" text-anchor="middle" fill="#4a5852" style="font-size:12px;font-weight:500">{{ $p }}</text>
                @endforeach
                <path d="M 505 200 C 600 150, 600 270, 505 222" fill="none" stroke="#9a6a08" stroke-width="2" stroke-dasharray="5 4"/>
                <text x="594" y="214" fill="#4a5852" style="font-size:12px;font-weight:500">dekatDengan</text>
                @foreach ($kelas as $j => [$nama, $x, $y])
                    @php $w = mb_strlen($nama) * 8.6 + 28; @endphp
                    <rect x="{{ $x - $w / 2 }}" y="{{ $y - 17 }}" width="{{ $w }}" height="34" rx="0" fill="{{ $j === 'tempat' ? '#fbf2d9' : '#fff' }}" stroke="{{ $j === 'tempat' ? '#9a6a08' : '#0f3b2e' }}" stroke-width="{{ $j === 'tempat' ? 2.5 : 1.5 }}"/>
                    <text x="{{ $x }}" y="{{ $y + 5 }}" text-anchor="middle" fill="#1b2622">mv:{{ $nama }}</text>
                @endforeach
            </svg>
            <p class="kecil redup" style="margin-top:10px">Setiap hubungan menyimpan sumbernya (dct:source) lewat reifikasi; jarak “dekat dengan” disimpan sebagai mv:Kedekatan dengan mv:jarakMeter.</p>
        </section>
        <section class="p-panel s5" aria-labelledby="judul-unduh">
            <header><h2 id="judul-unduh">Unduh</h2></header>
            <div class="p-baris">
                <div><span class="dua"><b>Ontologi dan data, RDF Turtle</b><span>Kelas OWL, properti, entitas, hubungan beserta sumber</span></span><a class="p-btn kanan" href="{{ route('data.turtle') }}">medanvisit.ttl</a></div>
                <div><span class="dua"><b>Tempat wisata, JSON-LD</b><span>schema.org TouristAttraction dengan alamat, koordinat, dan tiket</span></span><a class="p-btn kanan" href="{{ route('data.jsonld') }}">medanvisit.jsonld</a></div>
                <div><span class="dua"><b>Lisensi data</b><span>Fakta dan hubungan: CC BY 4.0. Foto mengikuti lisensi masing-masing (<a href="{{ route('kredit') }}" style="text-decoration:underline">kredit foto</a>)</span></span></div>
            </div>
            <div class="p-pemisah"></div>
            <h2 style="font-size:16px;font-weight:600">Jumlah per kelas</h2>
            <div class="p-baris" style="margin-top:6px">
                @foreach ($jumlah as $j => $n)
                    <div><span class="kelas" data-kelas="{{ $j }}">{{ Katalog::KELAS[$j] }}</span><span class="kanan">{{ $n }}</span></div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="sparql" data-sparql data-url="{{ route('data.sparql') }}" data-contoh-query="{{ json_encode($contohJson) }}" aria-labelledby="judul-sparql">
        <div class="p-panel">
            <header><h2 id="judul-sparql">Editor SPARQL</h2><span class="meta">Endpoint: belum tersambung (Apache Jena Fuseki)</span></header>
            <form>
                <div class="p-field" style="margin-bottom:12px">
                    <label for="contoh-query">Contoh query</label>
                    <select id="contoh-query" data-contoh>
                        @foreach ($contoh as $id => $c)<option value="{{ $id }}">{{ $c['judul'] }}</option>@endforeach
                    </select>
                </div>
                <label class="sr-only" for="teks-sparql">Query SPARQL</label>
                <textarea id="teks-sparql" spellcheck="false">{{ $contoh[$pertama]['query'] }}</textarea>
                <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
                    <button class="p-btn p-btn--utama" type="submit" data-jalankan>Jalankan</button>
                </div>
            </form>
        </div>
        <div class="p-panel">
            <header>
                <h2>Hasil</h2>
                <div class="p-seg" role="group" aria-label="Bentuk hasil">
                    <button type="button" data-mode-hasil="tabel" aria-pressed="true">Tabel</button>
                    <button type="button" data-mode-hasil="json" aria-pressed="false">JSON</button>
                </div>
            </header>
            <div data-hasil aria-live="polite"><p class="redup">Pilih contoh lalu tekan Jalankan. Contoh query dijalankan pada salinan data lokal; query lain menunggu endpoint Fuseki.</p></div>
        </div>
    </section>
</div>
@endsection
