@extends('layouts.situs')
@section('judul', 'Masuk')
@section('tanpa-kepala', '1')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-otentik">
    <div class="mv-otentik__visual">
        <x-foto :berkas="$tempatSorotan[0]['foto'] ?? 'peta-medan-1930-kota-lama.jpg'" :alt="$tempatSorotan[0]['nama'] ?? 'Medan'" :utama="true" />
        <div class="mv-otentik__lapisan" aria-hidden="true"></div>

        <div class="mv-otentik__visual-atas" data-naik style="--i:0">
            <a class="mv-otentik__logo" href="{{ route('beranda') }}" aria-label="MedanVisit, kembali ke beranda">
                <img src="{{ asset('storage/logo_app/svg/MedanVisit_logo_horizontal_gelap.svg') }}" alt="MedanVisit" width="736" height="256">
            </a>
        </div>

        <div class="mv-otentik__visual-bawah">
            <p class="mv-otentik__tajuk-sisi" data-naik style="--i:1">Satu akun wisata Medan.</p>
            <ul class="mv-otentik__manfaat" data-naik style="--i:2">
                <li>Simpan e-tiket ber-QR dan buka kapan saja di loket masuk</li>
                <li>Simpan dan bagikan rencana perjalanan setengah hari</li>
            </ul>
            <div class="mv-otentik__galeri" data-naik style="--i:3" aria-label="Tempat wisata di MedanVisit">
                @foreach ($tempatSorotan as $tempat)
                    @if (!empty($tempat['foto']))
                        <figure>
                            <x-foto :berkas="$tempat['foto']" :alt="$tempat['nama']" :kecil="true" />
                            <figcaption>{{ $tempat['nama'] }}</figcaption>
                        </figure>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="mv-otentik__panel">
        <nav class="mv-otentik__panel-atas" aria-label="Navigasi autentikasi" data-naik style="--i:1">
            <a class="mv-tautan" href="{{ route('beranda') }}">Kembali ke beranda</a>
            <span>Belum punya akun? <a class="mv-tautan" href="{{ route('daftar', ['kembali' => $kembali]) }}">Daftar</a></span>
        </nav>

        <div class="mv-otentik__kotak">
            <h1 data-naik style="--i:2">Masuk ke akun</h1>

            <form method="post" action="{{ route('masuk') }}" novalidate style="margin-top:28px">
                @csrf
                <input type="hidden" name="kembali" value="{{ $kembali }}">

                <div class="isian" data-naik style="--i:4">
                    <label for="email">Alamat email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" required @error('email') aria-invalid="true" aria-describedby="galat-email" @enderror>
                    @error('email')<p class="isian__galat" id="galat-email">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:5">
                    <label for="password">Kata sandi</label>
                    <div class="mv-sandi-bungkus">
                        <input id="password" type="password" name="password" autocomplete="current-password" required aria-describedby="bantu-password @error('password') galat-password @enderror" @error('password') aria-invalid="true" @enderror>
                        <button type="button" class="mv-sandi-toggle" data-toggle-sandi="password" aria-label="Tampilkan password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                    @error('password')<p class="isian__galat" id="galat-password">{{ $message }}</p>@enderror
                </div>
                <p style="margin-top:12px"><a class="mv-tautan" href="{{ route('password.request') }}">Lupa Password?</a></p>

                <button class="mv-btn mv-btn--utama mv-btn--lebar" type="submit" style="margin-top:24px; --i:7" data-naik>Masuk</button>
            </form>
            <a class="mv-btn mv-btn--google mv-btn--lebar" href="{{ route('masuk.google', ['kembali' => $kembali]) }}" style="margin-top:12px">
                <svg class="mv-google-logo" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.35 12.27c0-.72-.06-1.42-.18-2.09H12v3.96h5.24a4.48 4.48 0 0 1-1.94 2.94v2.45h3.14c1.84-1.69 2.91-4.18 2.91-7.26Z"/><path fill="#34A853" d="M12 21.7c2.63 0 4.84-.87 6.45-2.36l-3.14-2.45c-.87.58-1.98.92-3.31.92-2.54 0-4.7-1.72-5.47-4.03H3.29v2.53A9.74 9.74 0 0 0 12 21.7Z"/><path fill="#FBBC05" d="M6.53 13.78A5.85 5.85 0 0 1 6.23 12c0-.62.11-1.23.3-1.78V7.69H3.29A9.74 9.74 0 0 0 2.26 12c0 1.56.37 3.03 1.03 4.31l3.24-2.53Z"/><path fill="#EA4335" d="M12 6.19c1.43 0 2.71.49 3.72 1.46l2.79-2.79C16.83 3.27 14.63 2.3 12 2.3a9.74 9.74 0 0 0-8.71 5.39l3.24 2.53C7.3 7.91 9.46 6.19 12 6.19Z"/></svg>
                Masuk dengan Google
            </a>
        </div>

        <div class="mv-otentik__panel-bawah" data-naik style="--i:8">
            <a class="mv-tautan" href="{{ route('ketentuan') }}">Ketentuan &amp; privasi</a>
            <span>MedanVisit, Kota Medan</span>
        </div>
    </div>
</div>
@endsection
