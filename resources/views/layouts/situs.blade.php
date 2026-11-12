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
    </script>
    @if (request()->routeIs('beranda'))
        <script>
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) window.location.reload();
            });
        </script>
    @endif
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
        @php
            $fotoKaki = trim($__env->yieldContent('foto-kaki'));
            if (! $fotoKaki) {
                $fotoKaki = collect(\App\Support\Katalog::tempat())
                    ->first(fn ($tempat) => ! empty($tempat['foto']))['foto'] ?? 'peta-medan-1930-kota-lama.jpg';
            }
        @endphp
        @include('partials.kaki', ['ringkas' => ! request()->routeIs('beranda'), 'fotoKaki' => $fotoKaki])
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
