@extends('layouts.panel')
@section('judul', 'Listing usaha')

@section('isi')
<div class="p-judul">
    <div><h1>Listing usaha</h1><p class="sub">Data ini tampil di halaman voucher dan di kartu penukaran wisatawan.</p></div>
</div>
@if ($usaha['nama'] === '')
    <div class="p-pesan p-pesan--info">Lengkapi listing dulu. Voucher baru bisa dibuat setelah nama dan alamat usaha terisi.</div>
@endif
<form method="post" action="{{ route('mitra.listing') }}" novalidate>
    @csrf
    <section class="p-pengaturan" aria-labelledby="judul-profil">
        <div><h2 id="judul-profil">Profil usaha</h2><p>Nama, jenis, dan alamat yang dilihat wisatawan.</p></div>
        <div class="p-pengaturan__panel">
            <div class="p-field">
                <label for="nama">Nama usaha</label>
                <input id="nama" name="nama" value="{{ old('nama', $usaha['nama']) }}" maxlength="80" required @error('nama') aria-invalid="true" aria-describedby="g-nama" @enderror>
                @error('nama')<span class="galat" id="g-nama">{{ $message }}</span>@enderror
            </div>
            <div class="p-field">
                <label for="jenis">Jenis</label>
                <select id="jenis" name="jenis">
                    @foreach (['kuliner' => 'Kuliner', 'oleh-oleh' => 'Oleh-oleh', 'kerajinan' => 'Kerajinan', 'penginapan' => 'Penginapan'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('jenis', $usaha['jenis']) === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="p-field">
                <label for="alamat">Alamat</label>
                <input id="alamat" name="alamat" value="{{ old('alamat', $usaha['alamat']) }}" maxlength="160" required @error('alamat') aria-invalid="true" aria-describedby="g-alamat" @enderror>
                @error('alamat')<span class="galat" id="g-alamat">{{ $message }}</span>@enderror
            </div>
            <div class="p-field">
                <label for="jam">Jam buka</label>
                <input id="jam" name="jam" value="{{ old('jam', $usaha['jam']) }}" maxlength="80" placeholder="Setiap hari, 08.00–21.00" required aria-describedby="b-jam @error('jam') g-jam @enderror" @error('jam') aria-invalid="true" @enderror>
                <span class="bantu" id="b-jam">Tulis hari dan jam, misalnya “Senin–Sabtu, 07.00–20.00”.</span>
                @error('jam')<span class="galat" id="g-jam">{{ $message }}</span>@enderror
            </div>
            <div class="p-field">
                <label for="deskripsi">Deskripsi singkat</label>
                <textarea id="deskripsi" name="deskripsi" maxlength="400">{{ old('deskripsi', $usaha['deskripsi']) }}</textarea>
            </div>
        </div>
    </section>
    <section class="p-pengaturan" aria-labelledby="judul-menu">
        <div><h2 id="judul-menu">Menu dan harga</h2><p>Kosongkan nama untuk menghapus baris.</p></div>
        <div class="p-pengaturan__panel">
            @php $menu = array_pad(old('menu', $usaha['menu']), max(3, count($usaha['menu']) + 1), ['nama' => '', 'harga' => '']); @endphp
            @foreach ($menu as $i => $m)
                <div class="p-dua">
                    <div class="p-field"><label for="menu-{{ $i }}-nama">Nama menu {{ $i + 1 }}</label><input id="menu-{{ $i }}-nama" name="menu[{{ $i }}][nama]" value="{{ $m['nama'] }}" maxlength="60"></div>
                    <div class="p-field"><label for="menu-{{ $i }}-harga">Harga (Rp)</label><input id="menu-{{ $i }}-harga" name="menu[{{ $i }}][harga]" value="{{ $m['harga'] }}" inputmode="numeric" class="num"></div>
                </div>
            @endforeach
            @error('menu.*.harga')<span class="galat" style="color:var(--p-negative)">Harga harus berupa angka tanpa titik.</span>@enderror
            <div><button class="p-btn p-btn--utama" type="submit">Simpan listing</button></div>
        </div>
    </section>
</form>
@endsection
