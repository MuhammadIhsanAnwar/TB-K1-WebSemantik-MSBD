@php
    $item = [['akun', 'Ringkasan'], ['tiket.saya', 'Tiket saya'], ['rencana.saya', 'Rencana'], ['ulasan.saya', 'Ulasan']];
@endphp
<nav class="alat-subnav" aria-label="Akun">
    @foreach ($item as [$r, $l])
        <a href="{{ route($r) }}" @if (request()->routeIs($r)) aria-current="page" @endif>{{ $l }}</a>
    @endforeach
</nav>
