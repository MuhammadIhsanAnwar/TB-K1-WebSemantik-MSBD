@extends('layouts.panel')
@php $namaBahasa = ['id' => 'Indonesia', 'en' => 'English', 'zh' => '中文', 'ms' => 'Melayu']; @endphp
@section('judul', 'Cerita dan audio')

@section('isi')
<div class="p-judul">
    <div><h1>Cerita dan audio</h1><p class="sub">{{ $t['nama'] }} · {{ collect($status)->filter()->count() }} dari 4 bahasa berisi</p></div>
    <form class="p-judul__aksi" action="{{ route('admin.cerita') }}" method="get">
        <label class="sr-only" for="tempat">Pilih tempat</label>
        <select id="tempat" name="tempat" class="p-btn" onchange="this.form.submit()">@foreach ($tempat as $x)<option value="{{ $x['slug'] }}" @selected($x['slug'] === $t['slug'])>{{ $x['nama'] }}</option>@endforeach</select>
        <input type="hidden" name="bahasa" value="{{ $bahasa }}">
        <noscript><button class="p-btn" type="submit">Buka</button></noscript>
    </form>
</div>
<nav class="p-tabs" aria-label="Bahasa">
    @foreach ($namaBahasa as $k => $l)
        <a href="{{ route('admin.cerita', ['tempat' => $t['slug'], 'bahasa' => $k]) }}" @if ($bahasa === $k) aria-current="true" @endif>{{ $l }} @if (! $status[$k])<span class="n" style="color:var(--p-muted)">kosong</span>@endif</a>
    @endforeach
</nav>
<div class="p-grid">
    <form class="p-panel s7" method="post" action="{{ route('admin.cerita') }}" novalidate aria-labelledby="judul-editor">
        @csrf
        <input type="hidden" name="tempat" value="{{ $t['slug'] }}"><input type="hidden" name="bahasa" value="{{ $bahasa }}">
        <header><h2 id="judul-editor">Cerita, {{ $namaBahasa[$bahasa] }}</h2>@if ($draf)<span class="meta">Draf disimpan {{ \Illuminate\Support\Carbon::parse($draf['waktu'])->locale('id')->diffForHumans() }}</span>@endif</header>
        <div class="p-form" style="max-width:none">
            <div class="p-field"><label for="judul">Judul</label><input id="judul" name="judul" value="{{ old('judul', $draf['judul'] ?? ($bahasa === 'id' ? $t['nama'] : '')) }}" @error('judul') aria-invalid="true" @enderror>@error('judul')<span class="galat">{{ $message }}</span>@enderror</div>
            <div class="p-field"><label for="isi">Isi cerita</label><textarea id="isi" name="isi" style="min-height:260px" @error('isi') aria-invalid="true" aria-describedby="g-isi" @enderror>{{ old('isi', $draf['isi'] ?? ($bahasa === 'id' ? implode("\n\n", $t['cerita']) : '')) }}</textarea>@error('isi')<span class="galat" id="g-isi">{{ $message }}</span>@enderror</div>
            <div><button class="p-btn p-btn--utama" type="submit">Simpan draf</button></div>
        </div>
    </form>
    <section class="p-panel s5" aria-labelledby="judul-audio">
        <header><h2 id="judul-audio">Audio guide</h2></header>
        <div class="p-kosong">Belum ada audio untuk {{ $t['nama'] }}. Unggah audio menunggu penyimpanan berkas (storage) tersambung.</div>
        <div class="p-pemisah"></div>
        <h2>Lorong Waktu</h2>
        @if (! empty($t['foto_lama']))
            <p style="margin-top:8px">Foto dulu: {{ $t['tahun_foto_lama'] }}. Foto kini: {{ $t['foto'] }}.</p>
            <p class="meta" style="margin-top:4px">{{ \App\Support\Katalog::kredit($t['foto_lama']) }}</p>
        @else
            <div class="p-kosong" style="margin-top:8px">Belum ada foto arsip. Cari di koleksi digital KITLV atau Nationaal Archief, cek lisensinya, lalu tambahkan.</div>
        @endif
    </section>
</div>
@endsection
