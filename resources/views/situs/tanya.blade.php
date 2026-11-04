@extends('layouts.situs')
@section('judul', 'Tanya Medan')
@section('tanpa-kaki', '1')

@section('isi')
<div class="tanya" data-tanya data-url="{{ route('tanya.jawab') }}" data-awal="{{ $awal }}">
    <div class="alat__judul">
        <div>
            <h1>Tanya Medan</h1>
            <p>Tanya soal tempat, kuliner, rute, dan tiket di Kota Medan.</p>
        </div>
    </div>
    <div class="tanya__log" data-log aria-live="polite">
        <div class="gelembung gelembung--asisten">
            <p>Halo! Aku bisa bantu cari tempat, kuliner, rute, dan info tiket di Medan. Mau mulai dari mana?</p>
        </div>
        @unless ($awal)
            <div class="tanya__saran" data-saran>
                <button type="button">Kuliner khas Melayu Deli</button>
                <button type="button">Rute setengah hari di Medan lama</button>
                <button type="button">Rumah ibadah yang bisa dikunjungi</button>
                <button type="button">Berapa harga tiket Istana Maimun?</button>
                <button type="button">Makan dekat Tjong A Fie Mansion</button>
            </div>
        @endunless
    </div>
    <div class="tanya__form">
        <form>
            <label class="sr-only" for="pesan-tanya">Pertanyaanmu</label>
            <textarea id="pesan-tanya" name="pesan" rows="1" maxlength="300" placeholder="Tulis pertanyaan, lalu tekan Enter" required></textarea>
            <button class="tombol" type="submit">Kirim</button>
        </form>
        <p class="tanya__catatan">Asisten versi uji: jawaban disusun dari data MedanVisit dengan aturan kata kunci, belum memakai model bahasa. Cek kembali jam buka dan harga sebelum berangkat.</p>
    </div>
</div>
@endsection
