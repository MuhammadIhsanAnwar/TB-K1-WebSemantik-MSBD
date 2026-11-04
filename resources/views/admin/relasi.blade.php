@extends('layouts.panel')
@php use App\Support\Katalog; @endphp
@section('judul', 'Editor relasi')

@section('isi')
<div class="p-judul">
    <div><h1>Editor relasi</h1><p class="sub">Relasi menjadi sisi graf dan triple RDF. Relasi tanpa sumber tidak tayang ke publik.</p></div>
</div>
<div class="p-grid">
    <section class="p-panel s8" aria-labelledby="judul-daftar">
        <header>
            <h2 id="judul-daftar">Relasi tayang</h2>
            <form action="{{ route('admin.relasi') }}" method="get" style="display:flex;gap:8px;align-items:center">
                <label class="sr-only" for="predikat">Saring predikat</label>
                <select id="predikat" name="predikat" class="p-btn" onchange="this.form.submit()">
                    <option value="">Semua predikat</option>
                    @foreach (Katalog::LABEL_RELASI as $p => $l)@continue($p === 'beradaDi')<option value="{{ $p }}" @selected($predikat === $p)>{{ $l }}</option>@endforeach
                </select>
                <noscript><button class="p-btn" type="submit">Saring</button></noscript>
            </form>
        </header>
        <table class="p-tabel jadi-daftar">
            <thead><tr><th>Subjek</th><th>Predikat</th><th>Objek</th><th>Sumber</th></tr></thead>
            <tbody>
                @foreach ($relasi as $r)
                    <tr>
                        <td><b>{{ Katalog::get($r[0])['nama'] }}</b></td>
                        <td class="sekunder"><span class="p-pill">{{ Katalog::LABEL_RELASI[$r[1]] }}{{ isset($r['jarak_m']) ? ' ('.$r['jarak_m'].' m)' : '' }}</span></td>
                        <td class="sekunder">{{ Katalog::get($r[2])['nama'] }}</td>
                        <td class="sekunder" style="color:var(--p-muted)">@if (str_contains($r['sumber'], 'perlu verifikasi'))<span class="p-pill warn">Survei, perlu verifikasi</span>@else{{ \Illuminate\Support\Str::of($r['sumber'])->after('wiki/')->replace('_', ' ')->limit(28) }}@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
    <section class="p-panel s4" aria-labelledby="judul-tambah">
        <header><h2 id="judul-tambah">Usulkan relasi</h2></header>
        <form class="p-form" method="post" action="{{ route('admin.relasi') }}" novalidate>
            @csrf
            <div class="p-field"><label for="subjek">Subjek</label><select id="subjek" name="subjek">@foreach ($pilihan as $e)<option value="{{ $e['slug'] }}" @selected(old('subjek') === $e['slug'])>{{ $e['nama'] }}</option>@endforeach</select></div>
            <div class="p-field"><label for="predikat-baru">Predikat</label><select id="predikat-baru" name="predikat">@foreach (Katalog::LABEL_RELASI as $p => $l)<option value="{{ $p }}" @selected(old('predikat') === $p)>{{ $l }}</option>@endforeach</select></div>
            <div class="p-field"><label for="objek">Objek</label><select id="objek" name="objek" @error('objek') aria-invalid="true" aria-describedby="g-objek" @enderror>@foreach ($pilihan as $e)<option value="{{ $e['slug'] }}" @selected(old('objek') === $e['slug'])>{{ $e['nama'] }}</option>@endforeach</select>@error('objek')<span class="galat" id="g-objek">{{ $message }}</span>@enderror</div>
            <div class="p-field"><label for="sumber">Sumber</label><input id="sumber" name="sumber" value="{{ old('sumber') }}" placeholder="https://… atau judul buku, halaman" aria-describedby="b-sumber @error('sumber') g-sumber @enderror" @error('sumber') aria-invalid="true" @enderror><span class="bantu" id="b-sumber">Wajib. Tautan atau judul sumber yang bisa dicek.</span>@error('sumber')<span class="galat" id="g-sumber">{{ $message }}</span>@enderror</div>
            <div><button class="p-btn p-btn--utama" type="submit">Simpan usulan</button></div>
        </form>
        @if ($usulan)
            <div class="p-pemisah"></div>
            <h3 style="font-size:14px;font-weight:600;margin-bottom:6px">Usulan menunggu ({{ count($usulan) }})</h3>
            <div class="p-baris">
                @foreach (array_reverse($usulan) as $us)
                    <div><span class="dua"><b>{{ Katalog::get($us['subjek'])['nama'] }} {{ Katalog::LABEL_RELASI[$us['predikat']] }} {{ Katalog::get($us['objek'])['nama'] }}</b><span>{{ \Illuminate\Support\Str::limit($us['sumber'], 48) }}</span></span></div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
