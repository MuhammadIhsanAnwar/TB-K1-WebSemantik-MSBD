@php
    $u = session('pengguna');
    $menuPonsel = [
        [route('beranda'), 'Beranda', ['beranda']],
        [route('peta'), 'Tempat & Peta', ['peta', 'tempat', 'entitas']],
        [route('jalur'), 'Jalur tematik', ['jalur*']],
        [route('rencana'), 'Perencana rute', ['rencana', 'rencana.bagikan']],
        [route('jelajah'), 'Cerita Medan (Graf)', ['jelajah*']],
        [route('tanya'), 'Tanya asisten', ['tanya', 'cari']],
    ];
@endphp
<dialog class="lembar-menu" id="lembar-menu" data-ui aria-label="Menu navigasi">
    <div class="lembar-menu__isi">
        <div class="lembar-menu__atas">
            <img src="{{ asset('storage/logo_app/svg/MedanVisit_logo_tanpa-tagline_gelap.svg') }}" alt="MedanVisit" width="736" height="256" style="width:144px;height:auto">
            <button type="button" data-tutup-menu>Tutup</button>
        </div>

        <form class="mv-cari-menu" action="{{ route('cari') }}" method="get" role="search">
            <label class="sr-only" for="input-cari-menu">Cari di MedanVisit</label>
            <input id="input-cari-menu" type="search" name="q" placeholder="Cari tempat, tokoh, kuliner…" autocomplete="off" required>
            <button type="submit">Cari</button>
        </form>

        <nav aria-label="Menu utama ponsel">
            @foreach ($menuPonsel as [$href, $label, $pola])
                <a href="{{ $href }}" @if (request()->routeIs(...$pola)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        @if ($u && $u['peran'] === 'wisatawan')
            <nav class="lembar-menu__sub" aria-label="Menu akun">
                <a href="{{ route('tiket.saya') }}">Tiket saya</a>
                <a href="{{ route('rencana.saya') }}">Rencana tersimpan</a>
                <a href="{{ route('akun') }}">Akun saya</a>
            </nav>
        @endif

        <div class="lembar-menu__bawah">
            @if ($u)
                <form method="post" action="{{ route('keluar') }}">
                    @csrf
                    <button class="mv-btn mv-btn--krem mv-btn--lebar" type="submit">Keluar ({{ $u['nama'] }})</button>
                </form>
            @else
                <a class="mv-btn mv-btn--utama mv-btn--lebar" href="{{ route('masuk', ['kembali' => request()->getRequestUri()]) }}">Masuk atau daftar</a>
            @endif
        </div>
    </div>
</dialog>
