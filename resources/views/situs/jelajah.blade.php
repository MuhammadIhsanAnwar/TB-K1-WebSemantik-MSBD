@extends('layouts.situs')
@php
    use App\Support\Katalog;
    use App\Http\Controllers\SitusController;
    $jalurSimpul = $langkah ? array_merge([$dari], array_column($langkah, 'ke')) : [];
@endphp
@section('judul', 'Jelajah graf')
@section('tanpa-kaki', '1')

@section('isi')
<div class="jelajah" data-graf data-sumber="{{ route('jelajah.graf') }}" data-jalur="{{ json_encode($jalurSimpul) }}">
    <form class="jelajah__benang" action="{{ route('jelajah') }}" method="get">
        <h1>Benang merah</h1>
        <label class="sr-only" for="dari">Dari</label>
        <select id="dari" name="dari">
            @foreach ($pilihan as $p)<option value="{{ $p['slug'] }}" @selected($p['slug'] === $dari)>{{ $p['nama'] }}</option>@endforeach
        </select>
        <span>ke</span>
        <label class="sr-only" for="ke">Ke</label>
        <select id="ke" name="ke">
            @foreach ($pilihan as $p)<option value="{{ $p['slug'] }}" @selected($p['slug'] === $ke)>{{ $p['nama'] }}</option>@endforeach
        </select>
        <button class="tombol tombol--kecil" type="submit">Cari hubungan</button>
        <div class="cari-simpul">
            <label class="sr-only" for="cari-simpul">Cari simpul</label>
            <input id="cari-simpul" type="search" list="daftar-simpul" placeholder="Cari simpul…" data-cari-simpul>
            <datalist id="daftar-simpul">@foreach ($pilihan as $p)<option value="{{ $p['nama'] }}"></option>@endforeach</datalist>
        </div>
    </form>

    <aside class="jelajah__saring" aria-label="Saring graf">
        <h2>Jenis simpul</h2>
        @foreach (Katalog::KELAS as $j => $label)
            @continue(! isset($jumlah[$j]))
            <label><input type="checkbox" value="{{ $j }}" data-saring-jenis @checked($j !== 'wilayah')><span class="kelas" data-kelas="{{ $j }}">{{ $label }}</span><span class="n">{{ $jumlah[$j] }}</span></label>
        @endforeach
        <h2>Kedalaman</h2>
        <div class="p-seg" role="group" aria-label="Kedalaman dari simpul pusat">
            @foreach ([1, 2, 3] as $d)
                <button type="button" data-kedalaman="{{ $d }}" aria-pressed="{{ $d === 2 ? 'true' : 'false' }}">{{ $d }} langkah</button>
            @endforeach
        </div>
        <h2>Tampilan</h2>
        <button class="p-btn" type="button" data-tampilan-daftar aria-pressed="false">Tampilan daftar</button>
        <p class="kecil redup" style="margin-top:12px" data-jumlah-terlihat role="status"></p>
    </aside>

    <div class="jelajah__kanvas">
        <div id="graf" role="img" aria-label="Graf hubungan antartempat, tokoh, budaya, dan kuliner Medan. Gunakan tampilan daftar untuk versi teks."></div>
        <p class="peta-diam" data-status-graf role="status"></p>
        <div class="jelajah__kontrol">
            <button type="button" data-zoom="masuk" aria-label="Perbesar">+</button>
            <button type="button" data-zoom="keluar" aria-label="Perkecil">−</button>
            <button type="button" data-zoom="pusat">Pusatkan</button>
        </div>
        <div class="jelajah__daftar-alt" data-daftar-alt hidden>
            <h2 style="font:600 1.5rem var(--font-display);color:var(--green)">Semua hubungan</h2>
            <ul class="relasi-teks">
                @foreach (Katalog::relasi() as $r)
                    @continue($r[1] === 'beradaDi')
                    <li data-kelas="{{ Katalog::get($r[0])['jenis'] }}">{{ Katalog::kalimat($r, $r[0]) }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <aside class="jelajah__panel" data-panel-simpul aria-live="polite">
        <div data-isi-simpul></div>
        @if ($langkah)
            <div class="narasi">
                <h3>Ceritanya dalam {{ count($langkah) }} langkah</h3>
                <ol>
                    @foreach ($langkah as $l)<li>{{ $l['kalimat'] }}</li>@endforeach
                </ol>
                <p>Sumber tiap hubungan tercantum di halaman entitasnya.</p>
            </div>
        @else
            <div class="narasi">
                <h3>Belum ditemukan hubungan</h3>
                <p style="color:var(--ink);font-size:1rem">{{ Katalog::get($dari)['nama'] }} dan {{ Katalog::get($ke)['nama'] }} belum terhubung di graf. Coba pasangan lain, atau usulkan hubungannya ke kurator.</p>
            </div>
        @endif
    </aside>
</div>
@endsection
