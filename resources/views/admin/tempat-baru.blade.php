@extends('layouts.panel')
@section('judul', 'Tambah tempat wisata')

@section('isi')
<div class="p-judul">
    <div><h1>Tambah tempat wisata</h1><p class="sub">Data akan langsung ditulis ke Database 3 melalui SPARQL.</p></div>
    <a class="p-btn" href="{{ route('admin.entitas', ['jenis' => 'tempat']) }}">Kembali ke daftar</a>
</div>
@if ($errors->any())
    <div class="p-pesan p-pesan--galat" role="alert">{{ $errors->first() }}</div>
@endif
<form method="post" action="{{ route('admin.tempat.simpan') }}" class="p-panel p-form" style="max-width:760px">
    @csrf
    <div class="p-field"><label for="nama">Nama tempat</label><input id="nama" name="nama" value="{{ old('nama') }}" required maxlength="120">@error('nama')<span class="galat">{{ $message }}</span>@enderror</div>
    <div class="p-field"><label for="ringkas">Deskripsi singkat</label><textarea id="ringkas" name="ringkas" maxlength="240" required>{{ old('ringkas') }}</textarea>@error('ringkas')<span class="galat">{{ $message }}</span>@enderror</div>
    <div class="p-dua">
        <div class="p-field"><label for="kategori">Kategori</label><select id="kategori" name="kategori" required>@foreach (\App\Support\Katalog::KATEGORI as $kode => $nama)<option value="{{ $kode }}" @selected(old('kategori') === $kode)>{{ $nama }}</option>@endforeach</select></div>
        <div class="p-field"><label for="kecamatan">Kecamatan</label><select id="kecamatan" name="kecamatan"><option value="">Belum ditentukan</option>@foreach ($kecamatan as $wilayah)<option value="{{ $wilayah['slug'] }}" @selected(old('kecamatan') === $wilayah['slug'])>{{ $wilayah['nama'] }}</option>@endforeach</select>@error('kecamatan')<span class="galat">{{ $message }}</span>@enderror</div>
    </div>
    <div class="p-dua">
        <div class="p-field"><label for="lat">Lintang</label><input id="lat" name="lat" type="number" step="any" min="3.4" max="3.8" value="{{ old('lat') }}" required>@error('lat')<span class="galat">{{ $message }}</span>@enderror</div>
        <div class="p-field"><label for="lng">Bujur</label><input id="lng" name="lng" type="number" step="any" min="98.5" max="98.8" value="{{ old('lng') }}" required>@error('lng')<span class="galat">{{ $message }}</span>@enderror</div>
    </div>
    <div class="p-field"><label for="alamat">Alamat</label><input id="alamat" name="alamat" value="{{ old('alamat') }}" required maxlength="160">@error('alamat')<span class="galat">{{ $message }}</span>@enderror</div>
    <div class="p-dua">
        <div class="p-field"><label for="jam">Keterangan jam buka</label><input id="jam" name="jam" value="{{ old('jam') }}" required maxlength="80" placeholder="Setiap hari, 08.00–17.00">@error('jam')<span class="galat">{{ $message }}</span>@enderror</div>
        <div class="p-field"><label for="foto">Nama file foto (opsional)</label><input id="foto" name="foto" value="{{ old('foto') }}" maxlength="160" placeholder="nama-file.jpg"><span class="bantu">File harus sudah tersedia di public/images/foto.</span>@error('foto')<span class="galat">{{ $message }}</span>@enderror</div>
    </div>
    <div class="p-field"><label for="sumber">Sumber data</label><input id="sumber" name="sumber" value="{{ old('sumber') }}" required maxlength="240" placeholder="URL atau judul sumber">@error('sumber')<span class="galat">{{ $message }}</span>@enderror</div>
    <div class="p-field"><label for="harga">Harga tiket reguler (opsional, rupiah)</label><input id="harga" type="number" min="0" step="1" name="harga" value="{{ old('harga') }}">@error('harga')<span class="galat">{{ $message }}</span>@enderror</div>
    <div><button class="p-btn p-btn--utama" type="submit">Buat tempat wisata</button></div>
</form>
@endsection
