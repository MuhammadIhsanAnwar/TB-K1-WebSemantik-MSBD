@extends('layouts.situs')
@php use App\Support\Katalog; @endphp
@section('judul', 'Tanya Medan')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-tanya-ruang" data-tanya
    data-url="{{ route('tanya.jawab') }}"
    data-awal="{{ $awal }}"
    data-maskot-menyapa="{{ asset('images/maskot/mascot-menyapa.png') }}"
    data-maskot-mendengarkan="{{ asset('images/maskot/mascot-mendengarkan.png') }}"
    data-maskot-berpikir="{{ asset('images/maskot/mascot-berpikir.png') }}"
    data-maskot-membantu="{{ asset('images/maskot/mascot-membantu.png') }}"
    data-maskot-bingung="{{ asset('images/maskot/mascot-bingung.png') }}"
    data-maskot-sukses="{{ asset('images/maskot/mascot-sukses.png') }}"
>
    <aside class="mv-tanya-samping" aria-label="Topik dan cakupan asisten">
        <div class="mv-tanya-maskot-kartu">
            <div class="mv-tanya-maskot-bingkai">
                <img src="{{ asset('images/maskot/mascot-menyapa.png') }}" alt="Maskot Asisten MedanVisit" class="mv-tanya-maskot-gambar" data-tanya-maskot>
            </div>
            <div class="mv-tanya-maskot-info">
                <span class="mv-tanya-maskot-lencana" data-tanya-lencana>Siap Membantu</span>
                <b class="mv-tanya-maskot-nama">Asisten MedanVisit</b>
                <p class="mv-tanya-maskot-deskripsi" data-tanya-status>Tanyakan rute wisata, tiket, dan kuliner khas Kota Medan!</p>
            </div>
        </div>

        <div>
            <h1>Tanya Medan</h1>
            <p class="redup" style="margin-top:8px">Cari tahu jam kunjungan, tiket masuk, kuliner khas, dan rute jalan kaki berdasarkan katalog terkurasi MedanVisit.</p>
        </div>

        <div>
            <b style="display:block;color:var(--mv-hijau);margin-bottom:8px">Pertanyaan populer</b>
            <div class="mv-tanya-topik" data-saran-samping>
                @if ($contohTiket)
                    <button type="button">Berapa harga tiket {{ $contohTiket['nama'] }}?</button>
                @endif
                @if ($contohKuliner)
                    <button type="button">Info kuliner {{ $contohKuliner['nama'] }}</button>
                @endif
                @if ($contohTempat)
                    <button type="button">Rute setengah hari dari {{ $contohTempat[0]['nama'] }}</button>
                    <button type="button">Tempat wisata dekat {{ $contohTempat[0]['nama'] }}</button>
                @endif
            </div>
        </div>

        <div style="padding-top:12px;display:grid;gap:8px;color:var(--mv-redup)">
            <span>Ingin melihat letaknya langsung?</span>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <a class="mv-btn mv-btn--garis mv-btn--kecil" href="{{ route('peta') }}">Peta wisata</a>
                <a class="mv-btn mv-btn--garis mv-btn--kecil" href="{{ route('rencana') }}">Susun rute</a>
            </div>
        </div>
    </aside>

    <div class="mv-tanya-utama">
        <div class="tanya__log" data-log aria-live="polite">
            <div class="gelembung gelembung--asisten">
                <p>Halo! Aku siap membantu mencari tempat bersejarah, rumah ibadah, kuliner khas, rute jalan kaki, dan info harga tiket di Kota Medan. Apa yang ingin kamu rencanakan hari ini?</p>
            </div>
            @unless ($awal)
                <div data-saran style="display:grid;gap:18px">
                    <div class="tanya__saran">
                        @if ($contohKuliner)
                            <button type="button">Info kuliner {{ $contohKuliner['nama'] }}</button>
                        @endif
                        @if ($contohTempat)
                            <button type="button">Rute setengah hari dari {{ $contohTempat[0]['nama'] }}</button>
                        @endif
                        @if ($contohTiket)
                            <button type="button">Berapa harga tiket {{ $contohTiket['nama'] }}?</button>
                        @endif
                        @if ($contohTempat)
                            <button type="button">Tempat wisata dekat {{ $contohTempat[0]['nama'] }}</button>
                        @endif
                    </div>
                    <div class="mv-tanya-awal-grid">
                        @foreach ($contohTempat as $ct)
                            <a class="mv-tanya-awal-kartu" href="{{ route('tempat', $ct['slug']) }}">
                                @if (! empty($ct['foto']))
                                    <x-foto :berkas="$ct['foto']" :alt="$ct['nama']" :kecil="true" />
                                @endif
                                <div>
                                    <b>{{ $ct['nama'] }}</b>
                                    <span>{{ Katalog::KATEGORI[$ct['kategori']] }}, {{ $ct['kecamatan'] }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endunless
        </div>

        <div class="tanya__form">
            <form>
                <label class="sr-only" for="pesan-tanya">Tulis pertanyaanmu soal wisata Medan</label>
                <textarea id="pesan-tanya" name="pesan" rows="1" maxlength="300" placeholder="Tulis pertanyaan soal Medan, lalu tekan Enter…" required></textarea>
                <button class="mv-btn mv-btn--utama" type="submit">Kirim</button>
            </form>
            <p class="tanya__catatan" style="margin-top:8px">Jawaban disusun dari katalog MedanVisit menggunakan pencocokan kata kunci dan jaringan cerita. Periksa kembali jam buka serta harga bertanda contoh sebelum berangkat.</p>
        </div>
    </div>
</div>
@endsection
