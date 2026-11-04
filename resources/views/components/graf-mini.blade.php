@props(['graf', 'pusat', 'lebar' => 800, 'tinggi' => 500, 'maks2' => 7])
@php
    use App\Support\Katalog;
    use App\Http\Controllers\SitusController;
    $warna = ['tempat' => '#e3a51c', 'tokoh' => '#8e2c36', 'kerajaan' => '#9b6b3d', 'peristiwa' => '#2f6690', 'budaya' => '#6a4c9c', 'kuliner' => '#c9541e', 'wilayah' => '#2e7d4f', 'transportasi' => '#2f8a8a'];
    $cx = $lebar / 2; $cy = $tinggi / 2;
    $l1 = array_keys(array_filter($graf['simpul'], fn ($d) => $d === 1));
    $l2 = array_slice(array_keys(array_filter($graf['simpul'], fn ($d) => $d === 2)), 0, $maks2);
    $pos = [$pusat => [$cx, $cy]];
    foreach ($l1 as $i => $s) {
        $a = -M_PI / 2 + 2 * M_PI * $i / max(1, count($l1));
        $pos[$s] = [$cx + cos($a) * $lebar * 0.26, $cy + sin($a) * $tinggi * 0.3];
    }
    foreach ($l2 as $s) {
        $induk = null;
        foreach ($graf['sisi'] as $r) {
            foreach ([[$r[0], $r[2]], [$r[2], $r[0]]] as [$x, $y]) {
                if ($x === $s && isset($pos[$y]) && $y !== $pusat) { $induk = $y; }
            }
        }
        $ang = $induk ? atan2($pos[$induk][1] - $cy, $pos[$induk][0] - $cx) : 0;
        $sudah = count(array_filter($pos, fn ($p, $k) => isset($graf['simpul'][$k]) && $graf['simpul'][$k] === 2, ARRAY_FILTER_USE_BOTH));
        $ang += ($sudah % 2 ? 1 : -1) * 0.32 * (1 + intdiv($sudah, 2) % 2);
        $pos[$s] = [$cx + cos($ang) * $lebar * 0.42, $cy + sin($ang) * $tinggi * 0.42];
    }
    $sisi = array_filter($graf['sisi'], fn ($r) => isset($pos[$r[0]], $pos[$r[2]]));
@endphp
<svg viewBox="0 0 {{ $lebar }} {{ $tinggi }}" role="img" aria-label="Graf hubungan {{ Katalog::get($pusat)['nama'] }}: {{ count($pos) - 1 }} simpul terhubung" {{ $attributes }}>
    @foreach ($sisi as $r)
        <line class="sisi {{ $r[1] === 'dekatDengan' ? 'sisi--jarak' : '' }}" x1="{{ round($pos[$r[0]][0]) }}" y1="{{ round($pos[$r[0]][1]) }}" x2="{{ round($pos[$r[2]][0]) }}" y2="{{ round($pos[$r[2]][1]) }}"/>
    @endforeach
    @foreach ($pos as $slug => [$x, $y])
        @php $e = Katalog::get($slug); @endphp
        @php $r = $slug === $pusat ? 20 : ($graf['simpul'][$slug] === 1 ? 13 : 9); @endphp
        @php $url = $slug === $pusat ? null : SitusController::urlEntitas($e); @endphp
        @if ($url)<a href="{{ $url }}">@endif
            <title>{{ $e['nama'] }} ({{ Katalog::KELAS[$e['jenis']] }})</title>
            <circle cx="{{ round($x) }}" cy="{{ round($y) }}" r="{{ $r }}" fill="{{ $warna[$e['jenis']] }}" stroke="#f7f8f5" stroke-width="3"/>
            <text x="{{ round($x) }}" y="{{ round($y + $r + 17) }}" text-anchor="middle" @if ($slug === $pusat) style="font-size:16px;font-weight:700" @endif>{{ \Illuminate\Support\Str::limit($e['nama'], 24) }}</text>
        @if ($url)</a>@endif
    @endforeach
</svg>
