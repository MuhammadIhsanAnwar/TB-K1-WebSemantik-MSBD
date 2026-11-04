@php
    $menu ??= [['jelajah', 'Jelajah', 'jelajah*'], ['peta', 'Peta', 'peta'], ['jalur', 'Jalur', 'jalur*'], ['rencana', 'Rencana', 'rencana'], ['tanya', 'Tanya', 'tanya']];
    $u = session('pengguna');
@endphp
<dialog class="lembar-menu" id="lembar-menu" aria-label="Menu">
    <div class="lembar-menu__isi">
        <div class="lembar-menu__atas">
            <img src="{{ asset('images/logo/tanpa-tagline-gelap.svg') }}" alt="MedanVisit" width="736" height="256" style="width:148px;height:auto">
            <button type="button" data-tutup-menu>Tutup</button>
        </div>
        <nav aria-label="Utama, ponsel">
            <a href="{{ route('beranda') }}" @if (request()->routeIs('beranda')) aria-current="page" @endif>Beranda</a>
            @foreach ($menu as [$rute, $label, $pola])
                <a href="{{ route($rute) }}" @if (request()->routeIs($pola)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <a href="{{ route('data') }}" @if (request()->routeIs('data')) aria-current="page" @endif>Data terbuka</a>
        </nav>
        <div class="lembar-menu__bawah">
            @if ($u)
                <a class="tombol tombol--terang tombol--lebar" href="{{ route('akun') }}">Akun {{ $u['nama'] }}</a>
            @else
                <a class="tombol tombol--lebar" href="{{ route('masuk', ['kembali' => request()->getRequestUri()]) }}">Masuk</a>
            @endif
        </div>
    </div>
</dialog>
