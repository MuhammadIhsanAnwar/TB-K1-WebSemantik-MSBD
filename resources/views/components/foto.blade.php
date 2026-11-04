@props(['berkas', 'alt' => '', 'utama' => false, 'kecil' => false, 'segera' => false])
@php
    // kecil: versi 720 px untuk slot kecil (daftar, strip, rekomendasi), cukup ringan untuk dimuat segera
    $jalur = $kecil ? 'kecil/'.pathinfo($berkas, PATHINFO_FILENAME).'.jpg' : $berkas;
    [$w, $h] = \App\Support\Foto::ukuran($jalur);
@endphp
<img src="{{ \App\Support\Foto::url($jalur) }}" alt="{{ $alt }}" width="{{ $w }}" height="{{ $h }}"
     @if ($utama) fetchpriority="high" @elseif (! $segera) loading="lazy" @endif decoding="async"
     {{ $attributes }}>
