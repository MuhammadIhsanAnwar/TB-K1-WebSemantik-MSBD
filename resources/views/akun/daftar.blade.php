@extends('layouts.situs')
@section('judul', 'Daftar akun')
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
            <p class="mv-otentik__tajuk-sisi" data-naik style="--i:1">Akun wisata Medan.</p>
            <ul class="mv-otentik__manfaat" data-naik style="--i:2">
                <li>Pesan e-tiket tempat wisata yang tercatat di MedanVisit</li>
            </ul>
            <div class="mv-otentik__galeri" data-naik style="--i:3" aria-label="Cuplikan destinasi MedanVisit">
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
            <span>Sudah punya akun? <a class="mv-tautan" href="{{ route('masuk', ['kembali' => $kembali]) }}">Masuk di sini</a></span>
        </nav>

        <div class="mv-otentik__kotak">
            <h1 data-naik style="--i:2">Buat akun baru</h1>

            <form method="post" action="{{ route('daftar') }}" novalidate style="margin-top:28px">
                @csrf
                <input type="hidden" name="kembali" value="{{ $kembali }}">

                <div class="isian" data-naik style="--i:4">
                    <label for="nama">Nama</label>
                    <input id="nama" type="text" name="nama" value="{{ old('nama') }}" autocomplete="name" maxlength="60" placeholder="Contoh: Rina Nasution" required @error('nama') aria-invalid="true" aria-describedby="galat-nama" @enderror>
                    @error('nama')<p class="isian__galat" id="galat-nama">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:5">
                    <label for="email">Alamat email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" required aria-describedby="bantu-email @error('email') galat-email @enderror" @error('email') aria-invalid="true" @enderror>
                    @error('email')<p class="isian__galat" id="galat-email">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:6">
                    <label for="no_hp">Nomor HP</label>
                    <input id="no_hp" type="tel" name="no_hp" value="{{ old('no_hp') }}" autocomplete="tel" inputmode="numeric" pattern="08[0-9]{8,13}" maxlength="15" placeholder="Contoh: 081234567890" required @error('no_hp') aria-invalid="true" aria-describedby="galat-no-hp" @enderror>
                    @error('no_hp')<p class="isian__galat" id="galat-no-hp">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:7">
                    <label for="tanggal_lahir">Tanggal lahir</label>
                    <input id="tanggal_lahir" class="mv-input-tanggal" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" autocomplete="bday" required @error('tanggal_lahir') aria-invalid="true" @enderror>
                    @error('tanggal_lahir')<p class="isian__galat">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:8">
                    <label for="password">Kata sandi</label>
                    <div class="mv-sandi-bungkus">
                        <input id="password" type="password" name="password" autocomplete="new-password" required aria-describedby="bantu-password @error('password') galat-password @enderror" @error('password') aria-invalid="true" @enderror>
                        <button type="button" class="mv-sandi-toggle" data-toggle-sandi="password" aria-label="Tampilkan password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                    <div class="mv-kekuatan" data-kekuatan-sandi="password" id="bantu-password">
                        <span class="mv-kekuatan__bar" aria-hidden="true"><span class="mv-kekuatan__isi" data-bar-sandi data-status="kosong"></span></span>
                        <span data-teks-sandi aria-live="polite">Minimal 8 karakter, huruf kecil, huruf kapital, angka, dan simbol.</span>
                    </div>
                    @error('password')<p class="isian__galat" id="galat-password">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:9">
                    <label for="password_confirmation">Ulangi kata sandi</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required aria-describedby="kecocokan-password">
                    <p class="isian__bantuan mv-kecocokan-password" id="kecocokan-password" data-kecocokan-password></p>
                </div>

                <div class="isian" data-naik style="--i:10">
                    <label class="centang">
                        <input type="checkbox" name="setuju" value="1" @checked(old('setuju'))>
                        <span>Saya menyetujui <a class="mv-tautan" href="{{ route('ketentuan') }}" style="min-height:0">ketentuan layanan &amp; kebijakan privasi</a>.</span>
                    </label>
                    @error('setuju')<p class="isian__galat">{{ $message }}</p>@enderror
                </div>

                <button class="mv-btn mv-btn--utama mv-btn--lebar" type="submit" style="margin-top:24px; --i:11" data-naik>Daftar</button>
            </form>
            <a class="mv-btn mv-btn--google mv-btn--lebar" href="{{ route('masuk.google', ['kembali' => $kembali]) }}" style="margin-top:12px; --i:11" data-naik>
                <svg class="mv-google-logo" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.35 12.27c0-.72-.06-1.42-.18-2.09H12v3.96h5.24a4.48 4.48 0 0 1-1.94 2.94v2.45h3.14c1.84-1.69 2.91-4.18 2.91-7.26Z"/><path fill="#34A853" d="M12 21.7c2.63 0 4.84-.87 6.45-2.36l-3.14-2.45c-.87.58-1.98.92-3.31.92-2.54 0-4.7-1.72-5.47-4.03H3.29v2.53A9.74 9.74 0 0 0 12 21.7Z"/><path fill="#FBBC05" d="M6.53 13.78A5.85 5.85 0 0 1 6.23 12c0-.62.11-1.23.3-1.78V7.69H3.29A9.74 9.74 0 0 0 2.26 12c0 1.56.37 3.03 1.03 4.31l3.24-2.53Z"/><path fill="#EA4335" d="M12 6.19c1.43 0 2.71.49 3.72 1.46l2.79-2.79C16.83 3.27 14.63 2.3 12 2.3a9.74 9.74 0 0 0-8.71 5.39l3.24 2.53C7.3 7.91 9.46 6.19 12 6.19Z"/></svg>
                Daftar dengan Google
            </a>
            <script>
                const password = document.getElementById('password');
                const confirmation = document.getElementById('password_confirmation');
                const match = document.querySelector('[data-kecocokan-password]');
                const updateMatch = () => {
                    if (! confirmation.value) { match.textContent = ''; return; }
                    const cocok = password.value === confirmation.value;
                    match.textContent = cocok ? 'Password cocok.' : 'Password belum cocok.';
                    match.dataset.status = cocok ? 'cocok' : 'tidak-cocok';
                };
                password.addEventListener('input', updateMatch);
                confirmation.addEventListener('input', updateMatch);
            </script>
        </div>

        <div class="mv-otentik__panel-bawah" data-naik style="--i:12">
            <a class="mv-tautan" href="{{ route('ketentuan') }}">Ketentuan &amp; privasi</a>
            <span>MedanVisit, Kota Medan</span>
        </div>
    </div>
</div>
@endsection
