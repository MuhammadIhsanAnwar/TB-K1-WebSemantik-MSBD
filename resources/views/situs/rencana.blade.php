@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $pref ??= null;
    $dibagikan ??= null;
    $minat = $pref['minat'] ?? ['sejarah', 'religi', 'kuliner'];
    $durasi = (int) ($pref['durasi'] ?? 4);
    $moda = $pref['moda'] ?? ['jalan', 'becak'];
    $mulai = $pref['mulai'] ?? 'istana-maimun';
    $rute = $hasil ? collect($hasil['singgah'])->map(fn ($s) => ['nama' => $s['tempat']['nama'], 'lat' => $s['tempat']['lat'], 'lng' => $s['tempat']['lng'], 'url' => route('tempat', $s['tempat']['slug']), 'harga' => Katalog::rupiah(Katalog::hargaTermurah($s['tempat'])), 'tiket' => ! empty($s['tempat']['tiket']) ? route('tiket.pilih', $s['tempat']['slug']) : null])->values() : null;
    $judulRute = $dibagikan['judul'] ?? (($durasi <= 4 ? 'Setengah hari' : 'Sehari').' dari '.Katalog::get($mulai)['nama']);
    $bertiket = $hasil ? collect($hasil['singgah'])->pluck('tempat')->filter(fn ($t) => ! empty($t['tiket'])) : collect();
@endphp
@section('judul', $dibagikan ? $judulRute : 'Perencana rute')
@section('foto-kaki', 'becak-medan.jpg')

@section('isi')
<div class="rencana">
    <form class="rencana__form" action="{{ route('rencana') }}" method="get" novalidate>
        <h1 style="font-size:var(--s-head);margin-bottom:12px">Perencana rute</h1>
        <p style="margin-bottom:32px">Pilih minat, lama waktu, dan cara berpindah. Rute disusun dari tempat terdekat ke tempat berikutnya, dengan jarak dan ongkos becak dihitung dari letaknya.</p>
        @if ($errors->any())
            <div class="pesan pesan--galat" role="alert" style="margin-bottom:24px">{{ $errors->first() }}</div>
        @endif
        <fieldset>
            <legend>Minat</legend>
            <div class="pilih-banyak">
                @foreach (Katalog::KATEGORI as $k => $label)
                    <label><input type="checkbox" name="minat[]" value="{{ $k }}" @checked(in_array($k, $minat, true))>{{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
        <fieldset>
            <legend>Lama waktu</legend>
            <div class="pilih-banyak">
                @foreach ([2 => '2 jam', 4 => '4 jam', 6 => '6 jam', 8 => 'Seharian'] as $d => $label)
                    <label><input type="radio" name="durasi" value="{{ $d }}" @checked($durasi === $d)>{{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
        <fieldset>
            <legend>Cara berpindah</legend>
            <div class="pilih-banyak">
                <label><input type="checkbox" name="moda[]" value="jalan" @checked(in_array('jalan', $moda, true))>Jalan kaki</label>
                <label><input type="checkbox" name="moda[]" value="becak" @checked(in_array('becak', $moda, true))>Becak motor</label>
            </div>
        </fieldset>
        <div class="isian" style="margin-bottom:28px">
            <label for="mulai">Mulai dari</label>
            <select id="mulai" name="mulai">
                @foreach ($tempat as $t)
                    <option value="{{ $t['slug'] }}" @selected($t['slug'] === $mulai)>{{ $t['nama'] }}</option>
                @endforeach
            </select>
        </div>
        <button class="tombol tombol--lebar" type="submit">{{ $hasil ? 'Susun ulang rute' : 'Susun rute' }}</button>
    </form>

    <section class="rencana__hasil" aria-labelledby="judul-hasil">
        @if ($hasil)
            <h2 id="judul-hasil" style="font-size:2.25rem">{{ $judulRute }}</h2>
            <dl class="mv-jalur-spek nums" style="margin:14px 0 24px;max-width:560px">
                <div><dd>{{ count($hasil['singgah']) }} tempat</dd><dt>{{ collect($minat)->map(fn ($m) => Katalog::KATEGORI[$m])->implode(', ') }}</dt></div>
                <div><dd>{{ $durasi }} jam</dd><dt>Durasi rencana</dt></div>
                <div><dd>{{ collect($moda)->map(fn ($m) => $m === 'jalan' ? 'Jalan kaki' : 'Becak')->implode(' & ') }}</dd><dt>Cara pindah</dt></div>
            </dl>
            <div class="slip" aria-label="Urutan singgah">
                @foreach ($hasil['singgah'] as $s)
                    @if ($s['pindah'])
                        <div class="slip__baris redup"><span>{{ $s['pindah']['moda'] }} {{ number_format($s['pindah']['meter'], 0, ',', '.') }} m</span><span class="titik-titik"></span><span class="kanan">{{ $s['pindah']['menit'] }} mnt{{ $s['pindah']['biaya'] ? ' ('.Katalog::rupiah($s['pindah']['biaya']).')' : '' }}</span></div>
                    @endif
                    <div class="slip__baris"><span>{{ Katalog::jamTeks($s['mulai']) }} <a href="{{ route('tempat', $s['tempat']['slug']) }}">{{ $s['tempat']['nama'] }}</a></span><span class="titik-titik"></span><span class="kanan">{{ $s['tempat']['singgah'] }} mnt</span></div>
                @endforeach
                <div class="slip__akhir"><span>Selesai {{ Katalog::jamTeks($hasil['selesai']) }}, {{ count($hasil['singgah']) }} tempat</span><span>{{ Katalog::rupiah($hasil['biaya']) }}</span></div>
                <p class="kecil redup" style="margin-top:12px">Perkiraan tiket masuk dan becak per orang. Harga tiket dan tarif becak masih contoh.</p>
            </div>
            @if (count($hasil['singgah']) === 1)
                <p class="pesan pesan--galat" style="margin-top:20px">Tidak ada tempat lain yang cocok dalam waktu ini. Tambah minat, perpanjang waktu, atau izinkan becak motor.</p>
            @endif
            <div class="rencana__peta" data-peta data-peta-mode="rute" data-rute="{{ json_encode($rute) }}" tabindex="0" aria-label="Peta rute"><p class="peta-diam">Peta rute dimuat di sini.</p></div>
            <div class="rencana__aksi">
                @if ($dibagikan)
                    @php $tautan = route('rencana.bagikan', $dibagikan['kode']); @endphp
                    <button class="tombol tombol--hijau" type="button" data-salin="{{ $tautan }}">Salin tautan rencana</button>
                    <a class="tombol tombol--garis" href="https://wa.me/?text={{ urlencode($judulRute.': '.$tautan) }}" rel="noopener">Bagikan ke WhatsApp</a>
                @elseif (session('pengguna'))
                    <form method="post" action="{{ route('rencana.simpan') }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
                        @csrf
                        @foreach ($minat as $m)<input type="hidden" name="minat[]" value="{{ $m }}">@endforeach
                        @foreach ($moda as $m)<input type="hidden" name="moda[]" value="{{ $m }}">@endforeach
                        <input type="hidden" name="durasi" value="{{ $durasi }}"><input type="hidden" name="mulai" value="{{ $mulai }}">
                        <div class="isian" style="min-width:260px"><label for="judul-rencana">Nama rencana</label><input id="judul-rencana" type="text" name="judul" value="{{ $judulRute }}" maxlength="80" required></div>
                        <button class="tombol tombol--hijau" type="submit">Simpan rencana</button>
                    </form>
                @else
                    <a class="tombol tombol--hijau" href="{{ route('masuk', ['kembali' => request()->getRequestUri()]) }}">Masuk untuk menyimpan</a>
                @endif
                @foreach ($bertiket as $t)
                    <a class="tombol" href="{{ route('tiket.pilih', $t['slug']) }}">Tiket {{ $t['nama'] }}</a>
                @endforeach
            </div>
        @else
            <h2 id="judul-hasil" style="font-size:2.25rem">Rutemu muncul di sini</h2>
            <p style="margin-top:12px">Contoh: mulai dari Istana Maimun pukul 08.30, empat jam, jalan kaki dan becak motor, lima tempat bersejarah, religi, dan kuliner.</p>
            <x-foto berkas="becak-medan.jpg" alt="Becak motor Medan" style="margin-top:28px;width:100%;aspect-ratio:16/10;object-fit:cover" />
        @endif
    </section>
</div>
@endsection
