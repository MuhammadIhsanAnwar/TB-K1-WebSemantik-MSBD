@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $jalurSimpul = $langkah ? array_merge([$dari], array_column($langkah, 'ke')) : [];
    $namaDari = Katalog::get($dari)['nama'];
    $namaKe = Katalog::get($ke)['nama'];
@endphp
@section('judul', 'Benang merah')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-graf" data-ui data-graf data-sumber="{{ route('jelajah.graf') }}" data-jalur="{{ json_encode($jalurSimpul) }}">
    <form class="mv-graf__cari" action="{{ route('jelajah') }}" method="get">
        <div class="mv-graf__judul">
            <h1>Benang merah</h1>
            <p>Pilih dua hal di Medan, lalu lihat rantai hubungan terpendek di antara keduanya.</p>
        </div>
        <div class="mv-graf__pasangan">
            <label for="dari">Dari</label>
            <select id="dari" name="dari">
                @foreach ($pilihan as $p)<option value="{{ $p['slug'] }}" @selected($p['slug'] === $dari)>{{ $p['nama'] }}</option>@endforeach
            </select>
            <label for="ke">ke</label>
            <select id="ke" name="ke">
                @foreach ($pilihan as $p)<option value="{{ $p['slug'] }}" @selected($p['slug'] === $ke)>{{ $p['nama'] }}</option>@endforeach
            </select>
            <button class="mv-btn mv-btn--kecil" type="submit">Cari hubungan</button>
        </div>
    </form>

    <aside class="mv-graf__saring" aria-label="Atur graf">
        <div class="mv-graf__kelompok">
            <label class="mv-graf__label" for="cari-simpul">Cari simpul</label>
            <input id="cari-simpul" type="search" list="daftar-simpul" placeholder="Ketik nama simpul" data-cari-simpul>
            <datalist id="daftar-simpul">@foreach ($pilihan as $p)<option value="{{ $p['nama'] }}"></option>@endforeach</datalist>
        </div>
        <fieldset class="mv-graf__kelompok">
            <legend class="mv-graf__label">Jenis yang tampil</legend>
            @foreach (Katalog::KELAS as $j => $label)
                @continue(! isset($jumlah[$j]))
                <label class="mv-graf__jenis"><input type="checkbox" value="{{ $j }}" data-saring-jenis @checked($j !== 'wilayah')><span class="mv-graf__titik" data-kelas="{{ $j }}"></span>{{ $label }}<span class="n">{{ $jumlah[$j] }}</span></label>
            @endforeach
            <p class="mv-graf__catatan" data-jumlah-terlihat role="status"></p>
        </fieldset>
        <div class="mv-graf__kelompok">
            <span class="mv-graf__label" id="label-kedalaman">Kedalaman</span>
            <div class="mv-graf__seg" role="group" aria-labelledby="label-kedalaman">
                @foreach ([1, 2, 3] as $d)
                    <button type="button" data-kedalaman="{{ $d }}" aria-pressed="{{ $d === 2 ? 'true' : 'false' }}" aria-label="{{ $d }} langkah">{{ $d }}</button>
                @endforeach
            </div>
            <p class="mv-graf__catatan">Langkah dari simpul pusat.</p>
        </div>
    </aside>

    <div class="mv-graf__kanvas">
        <div id="graf" role="img" aria-label="Graf hubungan antartempat, tokoh, budaya, dan kuliner Medan. Gunakan tampilan daftar untuk versi teks."></div>
        <p class="mv-graf__status" data-status-graf role="status"></p>
        <p class="mv-graf__petunjuk">Seret untuk menggeser, gulir untuk memperbesar, klik simpul untuk melihat hubungannya.</p>
        <div class="mv-graf__daftar" data-daftar-alt hidden>
            <h2>Semua hubungan</h2>
            <ul class="relasi-teks">
                @foreach (Katalog::relasi() as $r)
                    @continue($r[1] === 'beradaDi')
                    <li data-kelas="{{ Katalog::get($r[0])['jenis'] }}">{{ Katalog::kalimat($r, $r[0]) }}</li>
                @endforeach
            </ul>
        </div>
        <div class="mv-graf__kontrol">
            <button class="mv-graf__alih" type="button" data-tampilan-daftar aria-pressed="false">Tampilan daftar</button>
            <button type="button" data-zoom="masuk" aria-label="Perbesar">+</button>
            <button type="button" data-zoom="keluar" aria-label="Perkecil">−</button>
            <button type="button" data-zoom="pusat">Pusatkan</button>
        </div>
    </div>

    <aside class="mv-graf__panel" data-panel-simpul aria-live="polite">
        <section class="mv-graf__hasil" aria-labelledby="judul-rantai">
            @if ($langkah)
                <h2 id="judul-rantai">{{ count($langkah) }} langkah dari {{ $namaDari }} ke {{ $namaKe }}</h2>
                <ol class="mv-rantai">
                    @foreach ($jalurSimpul as $i => $slug)
                        @php $e = Katalog::get($slug); @endphp
                        <li>
                            <button type="button" data-lompat="{{ $slug }}"><span class="mv-graf__titik" data-kelas="{{ $e['jenis'] }}"></span>{{ $e['nama'] }}</button>
                            @isset($langkah[$i])<span class="mv-rantai__kata">{{ Katalog::labelRelasi($langkah[$i]['relasi'], $slug) }}</span>@endisset
                        </li>
                    @endforeach
                </ol>
                <p class="mv-graf__catatan">Klik nama untuk menyorotnya di graf. Sumber tiap hubungan tercantum di halaman masing-masing.</p>
            @else
                <h2 id="judul-rantai">Belum ada rantai dari {{ $namaDari }} ke {{ $namaKe }}</h2>
                <p>Keduanya belum terhubung di graf. Coba pasangan lain, atau usulkan hubungannya ke kurator.</p>
            @endif
        </section>
        <section class="mv-graf__simpul" data-isi-simpul>
            <p class="mv-graf__catatan">Klik simpul di graf untuk melihat penjelasan dan hubungannya di sini.</p>
        </section>
    </aside>
</div>
@endsection
