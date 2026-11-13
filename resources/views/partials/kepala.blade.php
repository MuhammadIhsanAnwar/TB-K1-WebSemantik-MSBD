@php
    use App\Support\Katalog;
    use App\Support\Penyimpanan;
    $u = session('pengguna');
    $jalurFotoProfil = $u['foto_profil'] ?? null;
    $berkasFotoProfil = $jalurFotoProfil ? public_path('storage/'.$jalurFotoProfil) : null;
    $urlFotoProfil = $berkasFotoProfil && is_file($berkasFotoProfil)
        ? asset('storage/'.$jalurFotoProfil).'?v='.filemtime($berkasFotoProfil)
        : null;
    $adalahBeranda = request()->routeIs('beranda');
    $menu = [
        [route('peta'), 'Tempat & Peta', ['peta', 'tempat', 'entitas']],
        [route('jalur'), 'Jalur & Rute', ['jalur*', 'rencana', 'rencana.bagikan']],
        [route('jelajah'), 'Cerita Medan', ['jelajah*', 'data']],
        [route('tanya'), 'Tanya', ['tanya', 'cari']],
        [$u && $u['peran'] === 'wisatawan' ? route('tiket.saya') : route('beranda').'#tiket', 'Tiket', ['tiket.*', 'checkout', 'pesanan']],
    ];
    $tiketAktif = ($u && $u['peran'] === 'wisatawan')
        ? collect(Penyimpanan::pesanan((int) ($u['id'] ?? 0), aman: true))->filter(fn ($p) => in_array($p['status'], ['menunggu', 'lunas'], true) && empty($p['dipakai']) && $p['tanggal'] >= now('Asia/Jakarta')->toDateString())->count()
        : 0;
@endphp
<header class="mv-kepala {{ $adalahBeranda ? 'mv-kepala--hero' : '' }}" data-kepala>
    <div class="mv-kepala__dalam">
        <a class="mv-kepala__logo" href="{{ route('beranda') }}" aria-label="MedanVisit, ke beranda">
            <img src="{{ asset('storage/logo_app/svg/MedanVisit_logo_tanpa-tagline_gelap.svg') }}" alt="MedanVisit" width="736" height="256">
        </a>

        <nav class="mv-kepala__nav" aria-label="Navigasi utama">
            @foreach ($menu as [$href, $label, $pola])
                <a href="{{ $href }}" @if (request()->routeIs(...$pola)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="mv-kepala__aksi">
            <a class="mv-kepala__cepat-ponsel" href="{{ route('peta') }}" @if (request()->routeIs('peta')) aria-current="page" @endif>Peta</a>
            <a class="mv-kepala__cepat-ponsel" href="{{ route('tanya') }}" @if (request()->routeIs('tanya')) aria-current="page" @endif>Tanya</a>

            <button class="mv-kepala__cari" type="button" data-buka-cari aria-haspopup="dialog" aria-controls="dialog-cari" aria-label="Cari tempat, tokoh, atau kuliner">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <span class="mv-kepala__cari-teks">Cari</span>
            </button>

            @if ($u)
                @if ($u['peran'] === 'admin')
                    @if ($urlFotoProfil)
                        <img class="mv-kepala__foto" src="{{ $urlFotoProfil }}" alt="">
                    @endif
                    <a class="mv-btn mv-btn--krem mv-btn--kecil" href="{{ route('admin.ringkasan') }}">Ruang Admin</a>
                @elseif (in_array($u['peran'], ['superadmin'], true))
                    @if ($urlFotoProfil)
                        <img class="mv-kepala__foto" src="{{ $urlFotoProfil }}" alt="">
                    @endif
                    <a class="mv-btn mv-btn--krem mv-btn--kecil" href="{{ route('admin.ringkasan') }}">Super Admin</a>
                @else
                    <details class="mv-akun-menu" data-ui data-akun-menu>
                        <summary aria-label="Menu akun {{ $u['nama'] }}">
                            @if ($urlFotoProfil)
                                <img class="mv-kepala__foto" src="{{ $urlFotoProfil }}" alt="">
                            @else
                                <span class="mv-akun-menu__inisial" aria-hidden="true">{{ mb_substr($u['nama'], 0, 1) }}</span>
                            @endif
                            <span class="mv-akun-menu__nama">{{ \Illuminate\Support\Str::words($u['nama'], 1, '') }}</span>
                            @if ($tiketAktif > 0)
                                <span class="mv-akun-menu__lencana nums" title="{{ $tiketAktif }} tiket aktif">{{ $tiketAktif }}</span>
                            @endif
                        </summary>
                        <div class="mv-akun-menu__panel">
                            <div class="mv-akun-menu__info">
                                <b>{{ $u['nama'] }}</b>
                            </div>
                            <nav aria-label="Menu akun">
                                <a href="{{ route('tiket.saya') }}"><span>Tiket saya</span>@if ($tiketAktif > 0)<span class="nums">{{ $tiketAktif }}</span>@endif</a>
                                <a href="{{ route('rencana.saya') }}"><span>Rencana perjalanan</span></a>
                                <a href="{{ route('ulasan.saya') }}"><span>Ulasan saya</span></a>
                                <a href="{{ route('akun') }}"><span>Akun saya</span></a>
                            </nav>
                            <form method="post" action="{{ route('keluar') }}">
                                @csrf
                                <button type="submit" class="mv-akun-menu__keluar">Keluar</button>
                            </form>
                        </div>
                    </details>
                @endif
            @else
                <a class="mv-btn mv-btn--utama mv-btn--kecil mv-kepala__masuk" href="{{ route('masuk', ['kembali' => request()->getRequestUri()]) }}">Masuk</a>
            @endif

            <button class="mv-kepala__tombol-menu" type="button" data-buka-menu aria-haspopup="dialog" aria-controls="lembar-menu" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h11"/></svg>
                <span>Menu</span>
            </button>
        </div>
    </div>
</header>
@include('partials.menu')
