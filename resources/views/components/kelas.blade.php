@props(['jenis', 'label' => null])
<span {{ $attributes->merge(['class' => 'kelas', 'data-kelas' => $jenis]) }}>{{ $label ?? \App\Support\Katalog::KELAS[$jenis] }}</span>
