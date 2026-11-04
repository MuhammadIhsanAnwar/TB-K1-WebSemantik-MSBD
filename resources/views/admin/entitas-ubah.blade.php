@extends('layouts.panel')
@php use App\Support\Katalog; use App\Http\Controllers\SitusController; $draf = cache('medanvisit.draf_entitas.'.$e['slug']); $v = fn ($k, $d = '') => old($k, $draf[$k] ?? $d); @endphp
@section('judul', 'Ubah '.$e['nama'])

@section('isi')
<div class="p-judul">
    <div><h1>{{ $e['nama'] }}</h1><p class="sub">{{ Katalog::KELAS[$e['jenis']] }}, {{ count($tetangga) }} relasi, @if ($draf) draf disimpan {{ \Illuminate\Support\Carbon::parse($draf['waktu'])->locale('id')->diffForHumans() }} @else belum ada perubahan @endif</p></div>
    <div class="p-judul__aksi">
        @if ($url = SitusController::urlEntitas($e))<a class="p-btn" href="{{ $url }}">Lihat di situs</a>@endif
        <a class="p-btn" href="{{ route('admin.entitas') }}">Kembali ke daftar</a>
    </div>
</div>
<form method="post" action="{{ route('admin.entitas.ubah', $e['slug']) }}" novalidate>
    @csrf
    <section class="p-pengaturan" aria-labelledby="judul-dasar">
        <div><h2 id="judul-dasar">Data dasar</h2><p>Nama dan ringkasan tampil di kartu, hasil cari, dan JSON-LD.</p></div>
        <div class="p-pengaturan__panel">
            <div class="p-field"><label for="nama">Nama</label><input id="nama" name="nama" value="{{ $v('nama', $e['nama']) }}" required @error('nama') aria-invalid="true" @enderror>@error('nama')<span class="galat">{{ $message }}</span>@enderror</div>
            <div class="p-field"><label for="ringkas">Ringkasan</label><textarea id="ringkas" name="ringkas" maxlength="240" style="min-height:90px" @error('ringkas') aria-invalid="true" @enderror>{{ $v('ringkas', $e['ringkas']) }}</textarea><span class="bantu">Maksimal 240 karakter.</span>@error('ringkas')<span class="galat">{{ $message }}</span>@enderror</div>
            <div class="p-field"><label for="status">Status</label><select id="status" name="status"><option value="terbit" @selected($v('status', 'terbit') === 'terbit')>Terbit</option><option value="draf" @selected($v('status') === 'draf')>Draf</option></select></div>
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
                <div class="p-field"><label for="alamat">Alamat</label><input id="alamat" name="alamat" value="{{ $v('alamat', $e['alamat']) }}" @error('alamat') aria-invalid="true" @enderror>@error('alamat')<span class="galat">{{ $message }}</span>@enderror</div>
                <div class="p-field"><label for="jam">Jam buka</label><input id="jam" name="jam" value="{{ $v('jam', $e['jam']) }}" @error('jam') aria-invalid="true" @enderror>@error('jam')<span class="galat">{{ $message }}</span>@enderror</div>
                @if ($e['tiket'])
                    @foreach ($e['tiket'] as $jenis => $harga)
                        <div class="p-field"><label for="harga-{{ $loop->index }}">Harga tiket {{ mb_strtolower($jenis) }} (Rp)</label><input id="harga-{{ $loop->index }}" name="harga[{{ $jenis }}]" value="{{ old('harga.'.$jenis, $draf['harga'][$jenis] ?? $harga) }}" class="num" inputmode="numeric"></div>
                    @endforeach
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
            <div style="display:flex;gap:8px"><button class="p-btn p-btn--utama" type="submit">Simpan sebagai draf</button><a class="p-btn" href="{{ route('admin.relasi') }}">Editor relasi</a></div>
        </div>
    </section>
</form>
@endsection
