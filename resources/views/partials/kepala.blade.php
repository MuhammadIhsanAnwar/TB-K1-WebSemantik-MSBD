@php
    $menu = [
        ['jelajah', 'Jelajah', 'jelajah*'],
        ['peta', 'Peta', 'peta'],
        ['jalur', 'Jalur', 'jalur*'],
        ['rencana', 'Rencana', 'rencana'],
        ['tanya', 'Tanya', 'tanya'],
    ];
    $u = session('pengguna');
@endphp
<header class="kepala">
    <a class="kepala__logo" href="{{ route('beranda') }}" aria-label="MedanVisit, ke beranda">
        <img src="{{ asset('images/logo/tanpa-tagline-terang.svg') }}" alt="MedanVisit" width="736" height="256">
    </a>
    <nav class="kepala__nav" aria-label="Utama">
        @foreach ($menu as [$rute, $label, $pola])
            <a href="{{ route($rute) }}" @if (request()->routeIs($pola)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="kepala__aksi">
        @if ($u)
            <a class="kepala__akun" href="{{ $u['peran'] === 'mitra' ? route('mitra.listing') : ($u['peran'] === 'kurator' ? route('admin.ringkasan') : route('akun')) }}">
                <span class="kepala__inisial" aria-hidden="true">{{ mb_substr($u['nama'], 0, 1) }}</span>
                <span>{{ $u['nama'] }}</span>
            </a>
        @else
            <a class="tombol tombol--kecil" href="{{ route('masuk', ['kembali' => request()->getRequestUri()]) }}">Masuk</a>
        @endif
        <button class="kepala__menu" type="button" data-buka-menu aria-haspopup="dialog" aria-controls="lembar-menu">Menu</button>
    </div>
</header>
@include('partials.menu')
