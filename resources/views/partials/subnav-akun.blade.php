@php
    $item = [['akun', 'Ringkasan'], ['paspor', 'Paspor'], ['tiket.saya', 'Tiket saya'], ['voucher', 'Voucher'], ['rencana.saya', 'Rencana'], ['ulasan.saya', 'Ulasan'], ['peringkat', 'Peringkat']];
@endphp
<nav class="alat-subnav" aria-label="Akun">
    @foreach ($item as [$r, $l])
        <a href="{{ route($r) }}" @if (request()->routeIs($r)) aria-current="page" @endif>{{ $l }}</a>
    @endforeach
</nav>
