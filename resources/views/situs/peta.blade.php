@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $json = collect($tempat)->map(fn ($t) => [
        'slug' => $t['slug'], 'nama' => $t['nama'], 'lat' => $t['lat'], 'lng' => $t['lng'], 'kategori' => $t['kategori'],
        'url' => route('tempat', $t['slug']), 'harga' => Katalog::rupiah(Katalog::hargaTermurah($t)),
        'tiket' => ! empty($t['tiket']) ? route('tiket.pilih', $t['slug']) : null, 'buka' => $t['jam_buka'],
    ])->values();
    $perKategori = collect($tempat)->countBy('kategori');
@endphp
@section('judul', 'Peta wisata')
@section('tanpa-kaki', '1')

@section('isi')
<div class="peta-alat">
    <div class="peta-alat__samping">
        <div class="peta-alat__atas">
            <h1>Peta wisata</h1>
            <div class="peta-alat__saring" role="group" aria-label="Jenis tempat">
                @foreach (Katalog::KATEGORI as $k => $label)
                    @continue(! $perKategori->get($k))
                    <button type="button" class="kelas" data-kelas="{{ ['sejarah' => 'tempat', 'religi' => 'budaya', 'kuliner' => 'kuliner', 'museum' => 'peristiwa', 'ruang-publik' => 'wilayah'][$k] }}" data-saring-kategori="{{ $k }}" aria-pressed="true">{{ $label }} <span class="nums">{{ $perKategori->get($k) }}</span></button>
                @endforeach
            </div>
            <div class="peta-alat__saring" role="group" aria-label="Saring lain">
                <button type="button" data-saring="buka" aria-pressed="false">Buka sekarang</button>
                <button type="button" data-saring="tiket" aria-pressed="false">Bisa pesan tiket</button>
                <button type="button" data-lokasi-saya aria-pressed="false">Dekat saya</button>
            </div>
            <p class="kecil redup" data-pesan-lokasi role="status" aria-live="polite"></p>
        </div>
        <p class="peta-alat__hitung" data-peta-hitung role="status">{{ count($tempat) }} dari {{ count($tempat) }} tempat</p>
        <div class="peta-alat__daftar" data-peta-daftar>
            @foreach ($tempat as $t)
                <a class="peta-butir" href="{{ route('tempat', $t['slug']) }}" data-slug="{{ $t['slug'] }}">
                    @if ($t['foto'])<x-foto :berkas="$t['foto']" :alt="''" :kecil="true" />@else<span class="tanpa-foto" aria-hidden="true"></span>@endif
                    <span>
                        <b>{{ $t['nama'] }}</b>
                        <span>{{ Katalog::KATEGORI[$t['kategori']] }} · <x-harga :tempat="$t" /></span>
                        <span>{{ $t['kecamatan'] }} · {{ $t['jam'] }}</span>
                        <span data-jarak></span>
                    </span>
                </a>
            @endforeach
            <div class="kosong" data-peta-kosong hidden>
                <p>Tidak ada tempat yang cocok dengan saringan ini.</p>
                <button class="tombol tombol--kecil" type="button" data-hapus-saring>Hapus saringan</button>
            </div>
        </div>
    </div>
    <div class="peta-alat__peta" data-peta data-peta-mode="alat" data-tempat="{{ json_encode($json) }}" tabindex="0" aria-label="Peta interaktif tempat wisata Medan">
        <p class="peta-diam">Peta dimuat… Bila tidak muncul, daftar di samping tetap bisa dipakai.</p>
    </div>
</div>
@endsection
