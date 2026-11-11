@php
    $item = [['akun', 'Akun saya'], ['tiket.saya', 'Tiket saya'], ['rencana.saya', 'Rencana perjalanan'], ['ulasan.saya', 'Ulasan saya']];
@endphp
<nav class="alat-subnav" aria-label="Akun">
    @foreach ($item as [$r, $l])
        <a href="{{ route($r) }}" @if (request()->routeIs($r)) aria-current="page" @endif>{{ $l }}</a>
    @endforeach
</nav>
