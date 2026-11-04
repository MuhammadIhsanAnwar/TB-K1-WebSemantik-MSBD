<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Jelajahi Medan lewat ceritanya') · MedanVisit</title>
    <meta name="description" content="@yield('deskripsi', 'MedanVisit menghubungkan istana, rumah ibadah, kuliner, dan tokoh Kota Medan. Cari tempat, pesan tiket masuk, dan kumpulkan stempel di Paspor Digital.')">
    <meta name="theme-color" content="#0f3b2e">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <script>document.documentElement.classList.add('js')</script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('kepala')
</head>
<body class="situs @yield('kelas-body')">
    <a class="lewati" href="#isi">Lewati ke isi</a>
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
    @stack('skrip')
</body>
</html>
