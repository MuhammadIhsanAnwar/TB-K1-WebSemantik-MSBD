@php
    use App\Support\Katalog;
    use App\Http\Controllers\SitusController;
    // foto entitas yang terhubung (1–2 langkah): menunjukkan hal yang sedang dibicarakan, bukan hiasan
    $berfoto = collect($grafMini['simpul'])->keys()->reject(fn ($s) => $s === $pusat)
        ->map(fn ($s) => Katalog::get($s))->filter(fn ($x) => ! empty($x['foto']))->take(4)->values();
    $sumber = collect($tetangga)->pluck('relasi.sumber')->unique()->values();
@endphp
@if (empty($tetangga))
    <p class="kosong-relasi">Belum ada hubungan yang tercatat untuk {{ Katalog::get($pusat)['nama'] }}. Kurator menambahkannya dari sumber yang bisa diperiksa.</p>
@else
<div class="graf-mini"><x-graf-mini :graf="$grafMini" :pusat="$pusat" /></div>
<ul class="relasi-teks">
    @foreach ($tetangga as $tg)
        @php $lawan = Katalog::get($tg['lawan']); $url = SitusController::urlEntitas($lawan); @endphp
        <li data-kelas="{{ $lawan['jenis'] }}">
            <span>{{ Katalog::kalimat($tg['relasi'], $pusat) }} @if ($url)<a href="{{ $url }}">Buka {{ $lawan['nama'] }}</a>@endif</span>
        </li>
    @endforeach
</ul>
<p class="sumber-relasi">Sumber hubungan:
    @foreach ($sumber as $s)
        @if (str_starts_with($s, 'http'))<a href="{{ $s }}" rel="noopener">{{ str_replace('_', ' ', \Illuminate\Support\Str::after($s, '/wiki/')) }} (Wikipedia)</a>@else{{ $s }}@endif{{ $loop->last ? '.' : ',' }}
    @endforeach
</p>
@if ($berfoto->isNotEmpty())
    <div class="strip-foto">
        @foreach ($berfoto as $x)
            <a href="{{ SitusController::urlEntitas($x) }}">
                <figure>
                    <x-foto :berkas="$x['foto']" :alt="$x['foto_keterangan'] ?? $x['nama']" :kecil="true" :segera="true" />
                    <figcaption><b>{{ $x['nama'] }}</b> {{ Katalog::KELAS[$x['jenis']] }}</figcaption>
                </figure>
            </a>
        @endforeach
    </div>
@endif
@endif
