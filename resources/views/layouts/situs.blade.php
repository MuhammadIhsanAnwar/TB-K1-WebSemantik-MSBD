<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Jelajahi Medan lewat ceritanya') | MedanVisit</title>
    <meta name="description" content="@yield('deskripsi', 'MedanVisit menghubungkan istana, rumah ibadah, kuliner, dan tokoh Kota Medan. Cari tempat dan pesan tiket masuk.')">
    <meta name="theme-color" content="#0f3b2e">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <script>
        document.documentElement.classList.add('js');
        try {
            if (localStorage.getItem('mv-beranda-siap') === '1') {
                document.documentElement.classList.add('mv-pemuatan-selesai');
            }
        } catch { /* localStorage tidak tersedia, loader tetap berjalan */ }
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
<body class="situs @yield('kelas-body') @hasSection('tanpa-kaki') situs--alat @endif">
    @if (request()->routeIs('beranda'))
        @include('partials.pemuatan-beranda')
    @endif
    <a class="lewati" href="#isi">Lewati ke isi utama</a>
    @hasSection('tanpa-kepala')
    @else
        @include('partials.kepala')
    @endif
    @include('partials.pesan')
    <main id="isi">
        @yield('isi')
    </main>
    @hasSection('tanpa-kaki')
    @else
        @include('partials.kaki', ['ringkas' => ! request()->routeIs('beranda'), 'fotoKaki' => trim($__env->yieldContent('foto-kaki')) ?: 'kesawan-arsip.jpg'])
    @endif
    @hasSection('tanpa-kepala')
    @else
        @include('partials.dialog-cari')
        {{-- tidak dipasang di halaman Tanya (sudah percakapan) dan alur tiket (jangan menutupi tombol bayar) --}}
        @unless (request()->routeIs('tanya', 'tiket.*', 'checkout', 'pesanan*'))
            @include('partials.asisten-melayang')
        @endunless
    @endif
    @include('partials.lightbox-foto')
    @stack('skrip')
</body>
</html>
