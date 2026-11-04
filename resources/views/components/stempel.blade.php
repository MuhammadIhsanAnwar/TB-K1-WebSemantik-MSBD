@props(['tempat', 'tanggal' => null, 'id' => null, 'besar' => false])
@php
    $warna = ['sejarah' => '#0f3b2e', 'religi' => '#6a4c9c', 'kuliner' => '#b24a18', 'museum' => '#2f6690', 'ruang-publik' => '#2e7d4f'][$tempat['kategori'] ?? 'sejarah'] ?? '#0f3b2e';
    $uid = $id ?? 'st-'.$tempat['slug'].'-'.substr(md5((string) $tanggal), 0, 5);
    $nama = mb_strtoupper(\Illuminate\Support\Str::limit($tempat['nama'], 26, ''));
@endphp
<svg {{ $attributes->merge(['class' => $besar ? 'stempel' : 'stempel stempel--kecil']) }} viewBox="0 0 200 200" role="img" aria-label="Stempel {{ $tempat['nama'] }}{{ $tanggal ? ', '.$tanggal : '' }}">
    <defs><path id="{{ $uid }}" d="M 100,100 m -72,0 a 72,72 0 1,1 144,0 a 72,72 0 1,1 -144,0"/></defs>
    <circle cx="100" cy="100" r="94" fill="none" stroke="{{ $warna }}" stroke-width="5"/>
    <circle cx="100" cy="100" r="84" fill="none" stroke="{{ $warna }}" stroke-width="1.5"/>
    <text fill="{{ $warna }}"><textPath href="#{{ $uid }}" startOffset="25%" text-anchor="middle">{{ $nama }}</textPath></text>
    <g transform="translate(70 52) scale(0.3)" fill="{{ $warna }}">
        <path d="M95.41,182.41 L48.99,124.4 A64,64 0 1 1 151.01,124.4 L104.59,182.41 A6,6 0 0 1 95.41,182.41 Z M100,40 A44,44 0 1 0 100.01,40 Z"/>
        <path d="M100,52 L112,92 L100,86 L88,92 Z M78,66 L90,100 L78,96 L68,102 Z M122,66 L132,102 L122,96 L110,100 Z"/>
    </g>
    @if ($tanggal)
        <text x="100" y="146" text-anchor="middle" fill="{{ $warna }}" style="font-size:17px">{{ $tanggal }}</text>
    @endif
</svg>
