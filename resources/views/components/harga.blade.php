@props(['tempat', 'lengkap' => false])
@php $min = \App\Support\Katalog::hargaTermurah($tempat); @endphp
@if ($min === null)
    <span {{ $attributes->merge(['class' => 'harga']) }}>Gratis</span>
@else
    <span {{ $attributes->merge(['class' => 'harga']) }}>{{ $lengkap ? 'Mulai ' : '' }}{{ \App\Support\Katalog::rupiah($min) }}@if (! empty($tempat['harga_contoh'])) <span class="contoh">contoh</span>@endif</span>
@endif
