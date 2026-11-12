@extends('layouts.panel')
@php use App\Support\Katalog; use App\Http\Controllers\SitusController; $draf = $e['jenis'] === 'tempat' ? null : cache('medanvisit.draf_entitas.'.$e['slug']); $v = fn ($k, $d = '') => old($k, $draf[$k] ?? $d); @endphp
@section('judul', 'Ubah '.$e['nama'])

@section('isi')
<div class="p-judul">
    <div><h1>{{ $e['nama'] }}</h1><p class="sub">{{ Katalog::KELAS[$e['jenis']] }}, {{ count($tetangga) }} relasi@if ($draf), draf disimpan {{ \Illuminate\Support\Carbon::parse($draf['waktu'])->locale('id')->diffForHumans() }}@endif</p></div>
    <div class="p-judul__aksi">
        @if ($url = SitusController::urlEntitas($e))<a class="p-btn" href="{{ $url }}">Lihat di situs</a>@endif
        <a class="p-btn" href="{{ route('admin.entitas') }}">Kembali ke daftar</a>
    </div>
</div>
@if ($errors->any())
    <div class="p-pesan p-pesan--galat" role="alert">{{ $errors->first() }}</div>
@endif
<form method="post" action="{{ route('admin.entitas.ubah', $e['slug']) }}" novalidate>
    @csrf
    <section class="p-pengaturan" aria-labelledby="judul-dasar">
        <div><h2 id="judul-dasar">Data dasar</h2><p>Nama dan ringkasan tampil di kartu, hasil cari, dan JSON-LD.</p></div>
        <div class="p-pengaturan__panel">
            <div class="p-field"><label for="nama">Nama</label><input id="nama" name="nama" value="{{ $v('nama', $e['nama']) }}" required @error('nama') aria-invalid="true" @enderror>@error('nama')<span class="galat">{{ $message }}</span>@enderror</div>
            <div class="p-field"><label for="ringkas">Ringkasan</label><textarea id="ringkas" name="ringkas" maxlength="240" style="min-height:90px" @error('ringkas') aria-invalid="true" @enderror>{{ $v('ringkas', $e['ringkas']) }}</textarea><span class="bantu">Maksimal 240 karakter.</span>@error('ringkas')<span class="galat">{{ $message }}</span>@enderror</div>
            @if ($e['jenis'] !== 'tempat')
                <div class="p-field"><label for="status">Status</label><select id="status" name="status"><option value="terbit" @selected($v('status', 'terbit') === 'terbit')>Terbit</option><option value="draf" @selected($v('status') === 'draf')>Draf</option></select></div>
            @endif
        </div>
    </section>
    @if ($e['jenis'] === 'tempat')
        <section class="p-pengaturan" aria-labelledby="judul-lokasi">
            <div><h2 id="judul-lokasi">Lokasi dan kunjungan</h2><p>Koordinat menentukan penanda peta, jarak, dan radius check-in. Saat ini titiknya masih perkiraan.</p></div>
            <div class="p-pengaturan__panel">
                <div class="p-dua">
                    <div class="p-field"><label for="lat">Lintang</label><input id="lat" name="lat" value="{{ $v('lat', $e['lat']) }}" class="num" inputmode="decimal" @error('lat') aria-invalid="true" @enderror>@error('lat')<span class="galat">{{ $message }}</span>@enderror</div>
                    <div class="p-field"><label for="lng">Bujur</label><input id="lng" name="lng" value="{{ $v('lng', $e['lng']) }}" class="num" inputmode="decimal" @error('lng') aria-invalid="true" @enderror>@error('lng')<span class="galat">{{ $message }}</span>@enderror</div>
                </div>
                <div class="p-dua">
                    <div class="p-field"><label for="kategori">Kategori</label><select id="kategori" name="kategori">@foreach (Katalog::KATEGORI as $kode => $nama)<option value="{{ $kode }}" @selected($v('kategori', $e['kategori']) === $kode)>{{ $nama }}</option>@endforeach</select></div>
                    <div class="p-field"><label for="kecamatan">Kecamatan</label><select id="kecamatan" name="kecamatan"><option value="">Belum ditentukan</option>@foreach ($kecamatan as $wilayah)<option value="{{ $wilayah['slug'] }}" @selected($v('kecamatan', $kecamatanTerpilih) === $wilayah['slug'])>{{ $wilayah['nama'] }}</option>@endforeach</select></div>
                </div>
                <div class="p-field"><label for="alamat">Alamat</label><input id="alamat" name="alamat" value="{{ $v('alamat', $e['alamat']) }}" @error('alamat') aria-invalid="true" @enderror>@error('alamat')<span class="galat">{{ $message }}</span>@enderror</div>
                <div class="p-field"><label for="jam">Jam buka</label><input id="jam" name="jam" value="{{ $v('jam', $e['jam']) }}" @error('jam') aria-invalid="true" @enderror>@error('jam')<span class="galat">{{ $message }}</span>@enderror</div>
                <div class="p-field"><label for="foto">Nama file foto (opsional)</label><input id="foto" name="foto" value="{{ $v('foto', $e['foto']) }}" maxlength="160" placeholder="nama-file.jpg"><span class="bantu">File foto harus tersedia di public/images/foto.</span>@error('foto')<span class="galat">{{ $message }}</span>@enderror</div>
                <div class="p-field"><label for="sumber">Sumber data</label><input id="sumber" name="sumber" value="{{ $v('sumber', $e['sumber']) }}" required maxlength="240" placeholder="URL atau judul sumber">@error('sumber')<span class="galat">{{ $message }}</span>@enderror</div>
                @if ($e['tiket'])
                    @foreach ($e['tiket'] as $jenis => $harga)
                        <div class="p-field"><label for="harga-{{ $loop->index }}">Harga tiket {{ mb_strtolower($jenis) }} (Rp)</label><input id="harga-{{ $loop->index }}" name="harga[{{ $jenis }}]" value="{{ old('harga.'.$jenis, $draf['harga'][$jenis] ?? $harga) }}" class="num" inputmode="numeric"></div>
                    @endforeach
                @else
                    <div class="p-field"><label for="harga-reguler">Harga tiket reguler (opsional, rupiah)</label><input id="harga-reguler" name="harga[Reguler]" value="{{ old('harga.Reguler') }}" class="num" inputmode="numeric" min="0"></div>
                @endif
            </div>
        </section>
    @endif
    <section class="p-pengaturan" aria-labelledby="judul-relasi">
        <div><h2 id="judul-relasi">Relasi</h2><p>Relasi diubah di editor relasi, dengan sumber wajib.</p></div>
        <div class="p-pengaturan__panel">
            <div class="p-baris" style="background:#fff;border-radius:8px;padding:0 12px">
                @foreach ($tetangga as $tg)
                    <div><span>{{ Katalog::kalimat($tg['relasi'], $e['slug']) }}</span><span class="kanan" style="color:var(--p-muted);font-size:12px">{{ \Illuminate\Support\Str::limit($tg['relasi']['sumber'], 32) }}</span></div>
                @endforeach
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="p-btn p-btn--utama" type="submit">{{ $e['jenis'] === 'tempat' ? 'Simpan ke Database 3' : 'Simpan sebagai draf' }}</button><a class="p-btn" href="{{ route('admin.relasi') }}">Editor relasi</a></div>
        </div>
    </section>
</form>
@if ($e['jenis'] === 'tempat' && session('pengguna.peran') === 'superadmin')
    <form method="post" action="{{ route('admin.tempat.hapus', $e['slug']) }}" style="margin-top:16px" onsubmit="return confirm('Hapus tempat ini dari katalog SPARQL? Tindakan ini tidak dapat dibatalkan.')">
        @csrf @method('DELETE')<button class="p-btn p-btn--bahaya" type="submit">Hapus tempat wisata</button>
    </form>
@endif
@endsection
