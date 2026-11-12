@extends('layouts.panel')
@section('judul', 'Kelola akun')

@section('isi')
<div class="p-judul">
    <div><h1>Kelola akun</h1><p class="sub">Akun admin dan wisatawan di Database 1. Akun Super Admin tidak dikelola dari halaman ini.</p></div>
</div>
@if ($errors->any())
    <div class="p-pesan p-pesan--galat" role="alert">{{ $errors->first() }}</div>
@endif

<section class="p-panel" aria-labelledby="judul-akun-baru">
    <header><h2 id="judul-akun-baru">Buat akun</h2><span class="meta">{{ $jumlah['admin'] ?? 0 }} admin · {{ $jumlah['wisatawan'] ?? 0 }} wisatawan</span></header>
    <form class="p-form" method="post" action="{{ route('admin.akun.buat') }}">
        @csrf
        <div class="p-dua">
            <div class="p-field"><label for="nama-baru">Nama</label><input id="nama-baru" name="nama" value="{{ old('nama') }}" required maxlength="60" autocomplete="name">@error('nama')<span class="galat">{{ $message }}</span>@enderror</div>
            <div class="p-field"><label for="email-baru">Email</label><input id="email-baru" type="email" name="email" value="{{ old('email') }}" required maxlength="120" autocomplete="email">@error('email')<span class="galat">{{ $message }}</span>@enderror</div>
        </div>
        <div class="p-dua">
            <div class="p-field"><label for="peran-baru">Peran</label><select id="peran-baru" name="peran"><option value="wisatawan" @selected(old('peran') === 'wisatawan')>Wisatawan</option><option value="admin" @selected(old('peran') === 'admin')>Admin</option></select></div>
            <div class="p-field"><label for="password-baru">Kata sandi sementara</label><input id="password-baru" type="password" name="password" required autocomplete="new-password"><span class="bantu">Minimal 8 karakter: huruf kecil, kapital, angka, dan simbol.</span>@error('password')<span class="galat">{{ $message }}</span>@enderror</div>
        </div>
        <div class="p-field"><label for="password-confirm">Ulangi kata sandi</label><input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <div><button class="p-btn p-btn--utama" type="submit">Buat akun</button></div>
    </form>
</section>

<form class="p-alat" action="{{ route('admin.akun') }}" method="get" role="search" style="margin-top:24px">
    <label class="sr-only" for="cari-akun">Cari akun</label><input id="cari-akun" type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau email…">
    <label class="sr-only" for="filter-peran">Filter peran</label><select id="filter-peran" name="peran"><option value="">Semua peran</option><option value="admin" @selected($peran === 'admin')>Admin</option><option value="wisatawan" @selected($peran === 'wisatawan')>Wisatawan</option></select>
    <span class="isi"></span><span class="hitung">{{ $akun->count() }} akun</span>
</form>

<div class="p-grid">
    @forelse ($akun as $user)
        <section class="p-panel s12" aria-label="Akun {{ $user['email'] }}">
            <header><h2>{{ $user['name'] }}</h2><span class="meta">{{ $user['peran'] }} · {{ $user['email'] }}</span></header>
            <form class="p-form" method="post" action="{{ route('admin.akun.ubah', $user['id']) }}" style="max-width:none">
                @csrf @method('PATCH')
                <div class="p-dua">
                    <div class="p-field"><label for="nama-{{ $user['id'] }}">Nama</label><input id="nama-{{ $user['id'] }}" name="nama" value="{{ $user['name'] }}" required maxlength="60"></div>
                    <div class="p-field"><label for="email-{{ $user['id'] }}">Email</label><input id="email-{{ $user['id'] }}" type="email" name="email" value="{{ $user['email'] }}" required maxlength="120"></div>
                </div>
                <div class="p-dua">
                    <div class="p-field"><label for="peran-{{ $user['id'] }}">Peran</label><select id="peran-{{ $user['id'] }}" name="peran"><option value="wisatawan" @selected($user['peran'] === 'wisatawan')>Wisatawan</option><option value="admin" @selected($user['peran'] === 'admin')>Admin</option></select></div>
                    <div class="p-field"><label for="password-{{ $user['id'] }}">Kata sandi baru (opsional)</label><input id="password-{{ $user['id'] }}" type="password" name="password" autocomplete="new-password"><span class="bantu">Kosongkan jika tidak ingin mengganti kata sandi.</span></div>
                </div>
                <div class="p-field"><label for="password-confirm-{{ $user['id'] }}">Ulangi kata sandi baru</label><input id="password-confirm-{{ $user['id'] }}" type="password" name="password_confirmation" autocomplete="new-password"></div>
                <div class="p-judul__aksi"><button class="p-btn p-btn--utama" type="submit">Simpan perubahan</button><span class="meta">{{ $user['email_verified_at'] ? 'Email terverifikasi' : 'Email belum terverifikasi' }}</span></div>
            </form>
            <form method="post" action="{{ route('admin.akun.hapus', $user['id']) }}" style="margin-top:12px" onsubmit="return confirm('Hapus akun ini? Tindakan ini tidak dapat dibatalkan.')">
                @csrf @method('DELETE')<button class="p-btn p-btn--bahaya" type="submit">Hapus akun</button>
            </form>
        </section>
    @empty
        <div class="p-kosong s12">Tidak ada akun yang cocok dengan filter ini.</div>
    @endforelse
</div>
@endsection
