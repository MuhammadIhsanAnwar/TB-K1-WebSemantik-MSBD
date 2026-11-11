@extends('layouts.situs')
@section('judul', 'Daftar akun')
@section('tanpa-kepala', '1')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-otentik">
    <div class="mv-otentik__visual">
        <x-foto berkas="istana-maimun-1900.jpg" alt="" :utama="true" />
        <div class="mv-otentik__lapisan" aria-hidden="true">            <a class="mv-btn mv-btn--lebar" href="{{ route('masuk.google', ['kembali' => $kembali]) }}" style="margin-top:12px">Daftar dengan Google</a>
        </div>

        <div class="mv-otentik__visual-atas" data-naik style="--i:0">
            <a class="mv-otentik__logo" href="{{ route('beranda') }}" aria-label="MedanVisit, kembali ke beranda">
                <img src="{{ asset('storage/logo_app/svg/MedanVisit_logo_horizontal_gelap.svg') }}" alt="MedanVisit" width="736" height="256">
            </a>
        </div>

        <div class="mv-otentik__visual-bawah">
            <p class="mv-otentik__tajuk-sisi" data-naik style="--i:1">Akun wisata Medan.</p>
            <ul class="mv-otentik__manfaat" data-naik style="--i:2">
                <li>Pesan e-tiket Istana Maimun, Tjong A Fie Mansion, dan museum</li>
            </ul>
            <div class="mv-otentik__galeri" data-naik style="--i:3" aria-label="Cuplikan destinasi MedanVisit">
                <figure>
                    <x-foto berkas="tjong-a-fie-mansion.jpg" alt="Tjong A Fie Mansion" :kecil="true" />
                    <figcaption>Kesawan</figcaption>
                </figure>
                <figure>
                    <x-foto berkas="graha-maria-annai-velangkanni.jpg" alt="Graha Maria Annai Velangkanni" :kecil="true" />
                    <figcaption>Tanjung Selamat</figcaption>
                </figure>
                <figure>
                    <x-foto berkas="soto-medan.jpg" alt="Kuliner Soto Medan" :kecil="true" />
                    <figcaption>Kuliner Medan</figcaption>
                </figure>
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
            <p class="mv-otentik__pengantar" data-naik style="--i:3">Buat akun untuk menyimpan tiket masuk dan rencana kunjunganmu di Medan.</p>

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
                    <p class="isian__bantuan" id="bantu-email">Salinan e-tiket dikirim ke alamat ini.</p>
                    @error('email')<p class="isian__galat" id="galat-email">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:6">
                    <label for="tanggal_lahir">Tanggal lahir</label>
                    <input id="tanggal_lahir" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" autocomplete="bday" required @error('tanggal_lahir') aria-invalid="true" @enderror>
                    @error('tanggal_lahir')<p class="isian__galat">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:7">
                    <label for="password">Kata sandi</label>
                    <div class="mv-sandi-bungkus">
                        <input id="password" type="password" name="password" autocomplete="new-password" required aria-describedby="bantu-password @error('password') galat-password @enderror" @error('password') aria-invalid="true" @enderror>
                        <button type="button" class="mv-sandi-toggle" data-toggle-sandi="password" aria-pressed="false">Tampilkan</button>
                    </div>
                    <div class="mv-kekuatan" data-kekuatan-sandi="password" id="bantu-password">
                        <span class="mv-kekuatan__bar" aria-hidden="true"><span class="mv-kekuatan__isi" data-bar-sandi></span></span>
                        <span data-teks-sandi>Minimal 8 karakter, huruf kecil, huruf kapital, angka, dan simbol.</span>
                    </div>
                    @error('password')<p class="isian__galat" id="galat-password">{{ $message }}</p>@enderror
                </div>

                <div class="isian" data-naik style="--i:8">
                    <label for="password_confirmation">Ulangi kata sandi</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required aria-describedby="kecocokan-password">
                    <p class="isian__bantuan mv-kecocokan-password" id="kecocokan-password" data-kecocokan-password></p>
                </div>

                <div class="isian" data-naik style="--i:9">
                    <label class="centang">
                        <input type="checkbox" name="setuju" value="1" @checked(old('setuju'))>
                        <span>Saya menyetujui <a class="mv-tautan" href="{{ route('ketentuan') }}" style="min-height:0">ketentuan layanan &amp; kebijakan privasi</a>.</span>
                    </label>
                    @error('setuju')<p class="isian__galat">{{ $message }}</p>@enderror
                </div>

                <button class="mv-btn mv-btn--utama mv-btn--lebar" type="submit" style="margin-top:24px" data-naik style="--i:10">Daftar</button>
            </form>
            <a class="mv-btn mv-btn--lebar" href="{{ route('masuk.google', ['kembali' => $kembali]) }}" style="margin-top:12px">Daftar dengan Google</a>
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

        <div class="mv-otentik__panel-bawah" data-naik style="--i:9">
            <a class="mv-tautan" href="{{ route('ketentuan') }}">Ketentuan &amp; privasi</a>
            <span>MedanVisit, Kota Medan</span>
        </div>
    </div>
</div>
@endsection
