@extends('layouts.situs')
@section('judul', 'Pengaturan akun')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul">
        <div>
            <h1>Pengaturan akun</h1>
            <p class="mv-meta-garis"><span>{{ $user['email'] }}</span></p>
        </div>
        <a class="p-btn" href="{{ route('akun') }}">Kembali ke akun saya</a>
    </div>

    @if (session('pesan'))<div role="status" class="mv-pesan mv-pesan--sukses">{{ session('pesan') }}</div>@endif

    <form method="post" action="{{ route('akun.pengaturan') }}" enctype="multipart/form-data" class="p-panel" style="max-width:720px">
        @csrf
        <div class="isian">
            <label for="foto_profil">Foto profil</label>
            @if (!empty($user['foto_profil']))
                <img src="{{ asset('storage/'.$user['foto_profil']) }}" alt="Foto profil saat ini" width="96" height="96" style="display:block;object-fit:cover;border-radius:50%;margin-bottom:12px">
            @endif
            <input id="foto_profil" type="file" name="foto_profil" accept="image/*" @error('foto_profil') aria-invalid="true" @enderror>
            <p class="isian__bantuan">JPG, PNG, GIF, atau WEBP; maksimal 2 MB.</p>
            @error('foto_profil')<p class="isian__galat">{{ $message }}</p>@enderror
        </div>
        <div class="isian">
            <label for="nama">Nama</label>
            <input id="nama" type="text" name="nama" value="{{ old('nama', $user['name']) }}" maxlength="60" required>
            @error('nama')<p class="isian__galat">{{ $message }}</p>@enderror
        </div>
        <div class="isian">
            <label for="tanggal_lahir">Tanggal lahir</label>
            <input id="tanggal_lahir" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $user['tanggal_lahir'] ?? '') }}" required>
            @error('tanggal_lahir')<p class="isian__galat">{{ $message }}</p>@enderror
        </div>
        <div class="isian">
            <label for="no_hp">Nomor HP</label>
            <input id="no_hp" type="tel" name="no_hp" value="{{ old('no_hp', $user['no_hp'] ?? '') }}" inputmode="numeric" pattern="08[0-9]{8,13}" maxlength="15" required>
            <p class="isian__bantuan">Hanya angka dan harus diawali 08.</p>
            @error('no_hp')<p class="isian__galat">{{ $message }}</p>@enderror
        </div>
        <fieldset style="border:0;padding:0;margin:24px 0 0">
            <legend>Password (opsional)</legend>
            <div class="isian">
                <label for="password">Password baru</label>
                <input id="password" type="password" name="password" autocomplete="new-password">
                <p class="isian__bantuan">Kosongkan jika tidak ingin mengubah password.</p>
                @error('password')<p class="isian__galat">{{ $message }}</p>@enderror
            </div>
            <div class="isian">
                <label for="password_confirmation">Ulangi password baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
            </div>
        </fieldset>
        <button class="mv-btn mv-btn--utama" type="submit" style="margin-top:24px">Simpan perubahan</button>
    </form>
</div>
@endsection
