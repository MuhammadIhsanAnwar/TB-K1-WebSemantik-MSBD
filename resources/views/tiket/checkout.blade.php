@extends('layouts.situs')
@php use App\Support\Katalog; $tgl = \Illuminate\Support\Carbon::parse($pilihan['tanggal'])->locale('id'); @endphp
@section('judul', 'Checkout')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul"><div><h1>Data pemesan dan pembayaran</h1><p>{{ $t['nama'] }} · {{ $tgl->translatedFormat('l, j F Y') }}</p></div><a class="p-btn" href="{{ route('tiket.pilih', $t['slug']) }}">Ubah tiket</a></div>
    <ol class="langkah-beli" aria-label="Langkah pembelian">
        <li data-n="1" class="selesai">Pilih tiket</li><li data-n="2" aria-current="step">Data pemesan</li><li data-n="3">Bayar</li><li data-n="4">E-tiket</li>
    </ol>
    <form class="tiket-alur" method="post" action="{{ route('checkout') }}" novalidate>
        @csrf
        <div>
            <section class="p-panel" aria-labelledby="judul-pemesan">
                <header><h2 id="judul-pemesan">Data pemesan</h2></header>
                <div class="p-form" style="max-width:none">
                    <div class="p-field">
                        <label for="nama">Nama lengkap</label>
                        <input id="nama" name="nama" value="{{ old('nama', $u['nama']) }}" autocomplete="name" required @error('nama') aria-invalid="true" aria-describedby="g-nama" @enderror>
                        @error('nama')<span class="galat" id="g-nama">{{ $message }}</span>@enderror
                    </div>
                    <div class="p-dua">
                        <div class="p-field">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $u['email']) }}" autocomplete="email" required aria-describedby="b-email @error('email') g-email @enderror" @error('email') aria-invalid="true" @enderror>
                            <span class="bantu" id="b-email">E-tiket dikirim ke sini.</span>
                            @error('email')<span class="galat" id="g-email">{{ $message }}</span>@enderror
                        </div>
                        <div class="p-field">
                            <label for="hp">Nomor HP</label>
                            <input id="hp" type="tel" name="hp" value="{{ old('hp') }}" autocomplete="tel" inputmode="tel" required aria-describedby="b-hp @error('hp') g-hp @enderror" @error('hp') aria-invalid="true" @enderror>
                            <span class="bantu" id="b-hp">Diawali 08 atau +62.</span>
                            @error('hp')<span class="galat" id="g-hp">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            </section>
            <section class="p-panel" aria-labelledby="judul-metode">
                <header><h2 id="judul-metode">Cara bayar</h2></header>
                <fieldset class="metode" style="border:0;padding:0;margin:0">
                    <legend class="sr-only">Cara bayar</legend>
                    @foreach ($metode as $k => $label)
                        <label><input type="radio" name="metode" value="{{ $k }}" @checked(old('metode', 'qris') === $k)>{{ $label }}</label>
                    @endforeach
                </fieldset>
                @error('metode')<p class="p-pesan p-pesan--galat" role="alert" style="margin-top:12px">{{ $message }}</p>@enderror
                <p class="p-pesan p-pesan--info" style="margin:16px 0 0">Prototipe: payment gateway (Midtrans) belum tersambung. Setelah pesanan dibuat, kamu bisa menyimulasikan pembayaran berhasil.</p>
            </section>
        </div>
        <aside class="p-panel ringkasan-pesanan" aria-labelledby="judul-ringkasan">
            <header><h2 id="judul-ringkasan">Ringkasan pesanan</h2></header>
            <p><b>{{ $t['nama'] }}</b></p>
            <p class="redup">{{ $tgl->translatedFormat('l, j F Y') }}</p>
            <div class="p-baris" style="margin-top:8px">
                @foreach ($pilihan['item'] as $i)
                    <div><span>{{ $i['jumlah'] }} × {{ $i['jenis'] }}</span><span class="kanan">{{ Katalog::rupiah($i['subtotal']) }}</span></div>
                @endforeach
                <div><span>Biaya layanan</span><span class="kanan">Rp0</span></div>
            </div>
            <div class="total"><span>Total</span><b>{{ Katalog::rupiah($total) }}</b></div>
            <label class="centang" style="margin-top:16px;font-size:14px"><input type="checkbox" name="setuju" value="1" @checked(old('setuju'))><span>Saya setuju dengan <a href="{{ route('ketentuan') }}" style="text-decoration:underline">ketentuan pembelian</a>.</span></label>
            @error('setuju')<p class="galat" style="color:var(--p-negative);font-weight:600">{{ $message }}</p>@enderror
            <button class="p-btn p-btn--utama p-btn--besar" type="submit" style="width:100%;margin-top:16px">Bayar {{ Katalog::rupiah($total) }}</button>
        </aside>
    </form>
</div>
@endsection
