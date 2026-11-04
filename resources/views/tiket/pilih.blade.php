@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $jumlahLama = old('jumlah', []);
@endphp
@section('judul', 'Tiket '.$t['nama'])
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-tiket-ruang">
    <nav class="mv-remah" aria-label="Jejak halaman">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('tempat', $t['slug']) }}">{{ $t['nama'] }}</a>
        <span aria-hidden="true">›</span>
        <span>Pesan tiket</span>
    </nav>

    <div class="alat__judul" style="margin-top:4px">
        <div>
            <h1>Tiket {{ $t['nama'] }}</h1>
            <p class="mv-meta-garis">
                <span>Buka {{ $t['jam'] }} @if (! empty($t['jam_contoh']))<span class="contoh">contoh</span>@endif</span>
                <span>{{ $t['alamat'] }}</span>
            </p>
        </div>
    </div>

    <ol class="mv-langkah-tiket" aria-label="Tahap pemesanan tiket">
        <li aria-current="step"><span class="mv-langkah-tiket__no">1</span><span>Pilih tiket</span></li>
        <li><span class="mv-langkah-tiket__no">2</span><span>Data pemesan</span></li>
        <li><span class="mv-langkah-tiket__no">3</span><span>Pembayaran</span></li>
        <li><span class="mv-langkah-tiket__no">4</span><span>E-tiket terbit</span></li>
    </ol>

    <form class="tiket-alur" method="post" action="{{ route('tiket.pilih', $t['slug']) }}" data-tiket="pilih" novalidate>
        @csrf
        <div>
            <section class="p-panel" aria-labelledby="judul-tanggal">
                <header><h2 id="judul-tanggal">Tanggal kunjungan</h2></header>
                <div class="p-field">
                    <label for="tanggal">Pilih tanggal kedatangan</label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', $lama['tanggal'] ?? now('Asia/Jakarta')->toDateString()) }}" min="{{ now('Asia/Jakarta')->toDateString() }}" max="{{ now('Asia/Jakarta')->addDays(60)->toDateString() }}" required aria-describedby="bantu-tanggal @error('tanggal') galat-tanggal @enderror" @error('tanggal') aria-invalid="true" @enderror>
                    <span class="bantu" id="bantu-tanggal">Dapat dipesan hingga 60 hari ke depan. E-tiket berlaku satu kali masuk pada tanggal ini.</span>
                    @error('tanggal')<span class="galat" id="galat-tanggal">{{ $message }}</span>@enderror
                </div>
            </section>

            <section class="p-panel" aria-labelledby="judul-jumlah">
                <header>
                    <h2 id="judul-jumlah">Jumlah pengunjung</h2>
                    @if (! empty($t['harga_contoh']))<span class="meta">Harga contoh prototipe</span>@endif
                </header>
                @foreach ($t['tiket'] as $jenis => $harga)
                    @php $n = $jumlahLama[$loop->index] ?? (collect($lama['item'] ?? [])->firstWhere('jenis', $jenis)['jumlah'] ?? ($loop->first ? 1 : 0)); @endphp
                    <div class="penghitung" data-harga="{{ $harga }}" data-jenis="{{ mb_strtolower($jenis) }}">
                        <span>
                            <b>{{ $jenis }}</b>
                            <span class="redup nums">{{ Katalog::rupiah($harga) }} per orang</span>
                        </span>
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
                <header><h2 id="judul-ketentuan">Ketentuan kunjungan</h2></header>
                <div class="p-baris">
                    <div>Berlaku satu kali masuk pada tanggal yang dipilih.</div>
                    <div>Tiket yang telah terbit tidak dapat diuangkan kembali.</div>
                    <div>Tunjukkan kode QR e-tiket di loket masuk. <a class="p-tautan-kartu" href="{{ route('ketentuan') }}">Baca ketentuan lengkap</a></div>
                </div>
            </section>
        </div>

        <aside class="p-panel ringkasan-pesanan" aria-labelledby="judul-ringkasan">
            <header><h2 id="judul-ringkasan">Ringkasan pesanan</h2></header>
            @if ($t['foto'])
                <x-foto :berkas="$t['foto']" :alt="$t['nama']" :kecil="true" style="width:100%;aspect-ratio:16/9;object-fit:cover;margin-bottom:12px" />
            @endif
            <p><b>{{ $t['nama'] }}</b></p>
            <p class="redup" data-rincian aria-live="polite">Belum ada tiket dipilih</p>
            <div class="total"><span>Total bayar</span><b class="nums" data-total aria-live="polite">Rp0</b></div>
            <button class="mv-btn mv-btn--utama mv-btn--lebar" type="submit" data-lanjut style="margin-top:16px">Lanjut ke data pemesan</button>
        </aside>
    </form>
</div>
@endsection
