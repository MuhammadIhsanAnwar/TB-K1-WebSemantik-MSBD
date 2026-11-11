@php
    $u = session('pengguna');
    $ikon = [
        'ringkasan' => '<path d="M3 13h7V3H3zM14 21h7V11h-7zM3 21h7v-4H3zM14 7h7V3h-7z"/>',
        'entitas' => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'relasi' => '<circle cx="6" cy="6" r="2.5"/><circle cx="18" cy="8" r="2.5"/><circle cx="10" cy="18" r="2.5"/><path d="M8.3 7l7.4.6M7 8.3l2.2 7.4M16.4 10l-4.6 6.2"/>',
        'cerita' => '<path d="M5 4h10l4 4v12H5z"/><path d="M9 12h6M9 16h6M9 8h3"/>',
        'ulasan' => '<path d="M4 5h16v11H9l-5 4z"/>',
        'mitra' => '<path d="M4 10l2-6h12l2 6M4 10v10h16V10M4 10h16M9 20v-5h6v5"/>',
        'tiket' => '<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v12" stroke-dasharray="2 2"/>',
        'sinkron' => '<path d="M20 8A8 8 0 0 0 5 6.5M4 16a8 8 0 0 0 15 1.5"/><path d="M20 3v5h-5M4 21v-5h5"/>',
        'listing' => '<path d="M4 6h16M4 12h16M4 18h10"/>',
        'voucher' => '<path d="M3 7h18v4a2 2 0 0 0 0 4v4H3v-4a2 2 0 0 0 0-4z"/><path d="M9 7v12" stroke-dasharray="2 2"/>',
        'tukar' => '<path d="M4 7h13l-3-3M20 17H7l3 3"/>',
        'statistik' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'situs' => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
    ];
    $svg = fn ($k) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$ikon[$k].'</svg>';
    $antre = count(\App\Support\Penyimpanan::ulasan(null, 'menunggu'));
    $menuKurator = [
        ['admin.ringkasan', 'Ringkasan', 'ringkasan', null], ['admin.entitas', 'Entitas', 'entitas', null], ['admin.relasi', 'Editor relasi', 'relasi', null],
        ['admin.cerita', 'Cerita dan audio', 'cerita', null], ['admin.ulasan', 'Ulasan menunggu', 'ulasan', $antre ?: null],
        ['admin.tiket', 'Tiket dan transaksi', 'tiket', null], ['admin.sinkronisasi', 'Sinkronisasi', 'sinkron', null],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') | MedanVisit {{ $u['peran'] === 'admin' ? 'Admin' : 'Superadmin' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>document.documentElement.classList.add('js')</script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="panel-app" data-panel-app>
    <a class="lewati" href="#isi">Lewati ke isi</a>
    <div class="ps">
        <nav class="ps__samping" aria-label="Menu {{ $u['peran'] }}">
            <div class="ps__merek">
                <img src="{{ asset('storage/logo_app/svg/MedanVisit_logo_tanpa-tagline_terang.svg') }}" alt="MedanVisit" width="736" height="256">
                <button class="ps__menu" type="button" data-tutup-samping style="margin-left:auto">Tutup</button>
            </div>
            <div class="ps__lingkup">
                <span>Ruang {{ $u['peran'] === 'admin' ? 'admin' : 'superadmin' }}</span>
                <b>Kota Medan, 21 kecamatan</b>
            </div>
            <div class="ps__nav">
                @foreach ($menuKurator as $m)
                    <a href="{{ route($m[0]) }}" @if (request()->routeIs($m[0].'*')) aria-current="page" @endif>{!! $svg($m[2]) !!}<span>{{ $m[1] }}</span>@if (! empty($m[3]))<span class="hitung">{{ $m[3] }}</span>@endif</a>
                @endforeach
            </div>
            <div class="ps__bawah">
                <a class="ps__nav" href="{{ route('beranda') }}" style="display:flex;align-items:center;gap:10px;height:32px;padding:0 8px;color:var(--p-muted)">{!! $svg('situs') !!}<span>Lihat situs publik</span></a>
                <div class="ps__pengguna">
                    <span class="ps__inisial" aria-hidden="true">{{ mb_substr($u['nama'], 0, 1) }}</span>
                    <span><b>{{ $u['nama'] }}</b><br><span style="font-size:12px;color:var(--p-muted)">{{ $u['email'] }}</span></span>
                </div>
                <form method="post" action="{{ route('keluar') }}">@csrf<button class="p-btn" type="submit" style="width:100%">Keluar</button></form>
            </div>
        </nav>
        <header class="ps__atas">
            <button class="ps__menu" type="button" data-buka-samping aria-expanded="false">Menu</button>
            @if (in_array($u['peran'], ['admin', 'superadmin'], true))
                <form action="{{ route('admin.entitas') }}" method="get" role="search">
                    <label class="sr-only" for="cari-panel">Cari entitas</label>
                    <input id="cari-panel" type="search" name="q" placeholder="Cari tempat, tokoh, kuliner…" value="{{ request('q') }}">
                </form>
            @else
                <span></span>
            @endif
            <span class="stamp" style="font-size:12px;color:var(--p-muted)">{{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y') }}</span>
        </header>
        <main class="ps__lembar" id="isi">
            @if (session('pesan'))<div class="p-pesan" role="status">{{ session('pesan') }}</div>@endif
            @if (session('galat'))<div class="p-pesan p-pesan--galat" role="alert">{{ session('galat') }}</div>@endif
            @yield('isi')
        </main>
    </div>
</body>
</html>
