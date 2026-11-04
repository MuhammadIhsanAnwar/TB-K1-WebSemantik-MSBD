@extends('layouts.situs')
@php use App\Support\Katalog; $jumlahLama = old('jumlah', []); @endphp
@section('judul', 'Tiket '.$t['nama'])
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul"><div><h1>Tiket {{ $t['nama'] }}</h1><p>{{ $t['jam'] }} @if (! empty($t['jam_contoh']))<span class="contoh">contoh</span>@endif · {{ $t['alamat'] }}</p></div></div>
    <ol class="langkah-beli" aria-label="Langkah pembelian">
        <li data-n="1" aria-current="step">Pilih tiket</li><li data-n="2">Data pemesan</li><li data-n="3">Bayar</li><li data-n="4">E-tiket</li>
    </ol>
    <form class="tiket-alur" method="post" action="{{ route('tiket.pilih', $t['slug']) }}" data-tiket="pilih" novalidate>
        @csrf
        <div>
            <section class="p-panel" aria-labelledby="judul-tanggal">
                <header><h2 id="judul-tanggal">Tanggal kunjungan</h2></header>
                <div class="p-field">
                    <label for="tanggal">Tanggal</label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', $lama['tanggal'] ?? now('Asia/Jakarta')->toDateString()) }}" min="{{ now('Asia/Jakarta')->toDateString() }}" max="{{ now('Asia/Jakarta')->addDays(60)->toDateString() }}" required aria-describedby="bantu-tanggal @error('tanggal') galat-tanggal @enderror" @error('tanggal') aria-invalid="true" @enderror>
                    <span class="bantu" id="bantu-tanggal">Bisa dipesan sampai 60 hari ke depan. Tiket berlaku hanya pada tanggal ini.</span>
                    @error('tanggal')<span class="galat" id="galat-tanggal">{{ $message }}</span>@enderror
                </div>
            </section>
            <section class="p-panel" aria-labelledby="judul-jumlah">
                <header><h2 id="judul-jumlah">Jumlah tiket</h2>@if (! empty($t['harga_contoh']))<span class="meta">Harga contoh, belum dicek ke pengelola</span>@endif</header>
                @foreach ($t['tiket'] as $jenis => $harga)
                    @php $n = $jumlahLama[$loop->index] ?? (collect($lama['item'] ?? [])->firstWhere('jenis', $jenis)['jumlah'] ?? ($loop->first ? 1 : 0)); @endphp
                    <div class="penghitung" data-harga="{{ $harga }}" data-jenis="{{ mb_strtolower($jenis) }}">
                        <span><b>{{ $jenis }}</b><span class="redup">{{ Katalog::rupiah($harga) }} per orang</span></span>
                        <span class="penghitung__kontrol">
                            <button type="button" data-kurang aria-label="Kurangi tiket {{ $jenis }}">−</button>
                            <label class="sr-only" for="jumlah-{{ $loop->index }}">Jumlah tiket {{ $jenis }}</label>
                            <input id="jumlah-{{ $loop->index }}" type="number" name="jumlah[{{ $loop->index }}]" value="{{ $n }}" min="0" max="10" inputmode="numeric">
                            <button type="button" data-tambah aria-label="Tambah tiket {{ $jenis }}">+</button>
                        </span>
                    </div>
                @endforeach
                @error('jumlah')<p class="p-pesan p-pesan--galat" role="alert" style="margin-top:12px">{{ $message }}</p>@enderror
            </section>
            <section class="p-panel" aria-labelledby="judul-ketentuan">
                <header><h2 id="judul-ketentuan">Ketentuan singkat</h2></header>
                <div class="p-baris">
                    <div>Berlaku satu kali masuk pada tanggal yang dipilih.</div>
                    <div>Tiket yang sudah dibayar tidak dapat dikembalikan.</div>
                    <div>Tunjukkan QR e-tiket di loket. <a class="p-tautan-kartu" href="{{ route('ketentuan') }}">Ketentuan lengkap</a></div>
                </div>
            </section>
        </div>
        <aside class="p-panel ringkasan-pesanan" aria-labelledby="judul-ringkasan">
            <header><h2 id="judul-ringkasan">Ringkasan</h2></header>
            @if ($t['foto'])<x-foto :berkas="$t['foto']" :alt="$t['nama']" style="width:100%;aspect-ratio:16/9;object-fit:cover;margin-bottom:12px" />@endif
            <p><b>{{ $t['nama'] }}</b></p>
            <p class="redup" data-rincian aria-live="polite">Belum ada tiket dipilih</p>
            <div class="total"><span>Total</span><b data-total aria-live="polite">Rp0</b></div>
            <button class="p-btn p-btn--utama p-btn--besar" type="submit" data-lanjut style="width:100%;margin-top:16px">Lanjut ke data pemesan</button>
        </aside>
    </form>
</div>
@endsection
