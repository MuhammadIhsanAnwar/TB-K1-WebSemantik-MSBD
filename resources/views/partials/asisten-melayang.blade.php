@php
    $contohTiket = array_values(\App\Support\Katalog::bertiket())[0] ?? null;
    $contohTempat = array_values(\App\Support\Katalog::tempat())[0] ?? null;
    $contohKuliner = array_values(\App\Support\Katalog::tempat('kuliner'))[0] ?? null;
@endphp
<div class="mv-asisten-widget tidak-dicetak" data-ui data-asisten-widget
    data-url="{{ route('tanya.jawab') }}"
    data-maskot-menyapa="{{ asset('images/maskot/mascot-menyapa.png') }}"
    data-maskot-mendengarkan="{{ asset('images/maskot/mascot-mendengarkan.png') }}"
    data-maskot-berpikir="{{ asset('images/maskot/mascot-berpikir.png') }}"
    data-maskot-membantu="{{ asset('images/maskot/mascot-membantu.png') }}"
    data-maskot-bingung="{{ asset('images/maskot/mascot-bingung.png') }}"
    data-maskot-sukses="{{ asset('images/maskot/mascot-sukses.png') }}"
>
    <button class="mv-asisten-widget__pemicu" type="button" data-buka-asisten aria-expanded="false" aria-controls="panel-asisten-melayang">
        <span class="mv-asisten-widget__pemicu-avatar" aria-hidden="true">
            <img src="{{ asset('images/maskot/mascot-menyapa.png') }}" alt="" width="36" height="36" data-pemicu-maskot>
        </span>
        <span>Tanya asisten</span>
    </button>

    <div class="mv-asisten-panel" id="panel-asisten-melayang" role="dialog" aria-label="Asisten wisata MedanVisit" hidden>
        <header class="mv-asisten-panel__kepala">
            <div class="mv-asisten-panel__identitas">
                <span class="mv-asisten-panel__avatar" aria-hidden="true">
                    <img src="{{ asset('images/maskot/mascot-menyapa.png') }}" alt="Maskot Asisten MedanVisit" width="46" height="46" data-panel-maskot>
                </span>
                <div>
                    <b>Asisten MedanVisit</b>
                    <span class="mv-asisten-panel__status" data-panel-status>Siap membantu wisatamu</span>
                </div>
            </div>
            <div class="mv-asisten-panel__kontrol">
                <a href="{{ route('tanya') }}" class="mv-asisten-panel__ikon" aria-label="Buka di halaman penuh" title="Halaman penuh">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                </a>
                <button type="button" class="mv-asisten-panel__ikon" data-tutup-asisten aria-label="Tutup panel asisten">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
        </header>

        <div class="mv-asisten-panel__log" data-widget-log aria-live="polite">
            <div class="mv-gelembung mv-gelembung--asisten">
                <p>Halo! Aku bisa bantu cari tempat wisata, kuliner khas, rute jalan kaki, dan info harga tiket di Medan. Mau mulai dari mana?</p>
            </div>
            <div class="mv-asisten-panel__saran" data-widget-saran>
                @if ($contohTiket)
                    <button type="button">Berapa harga tiket {{ $contohTiket['nama'] }}?</button>
                @endif
                @if ($contohKuliner)
                    <button type="button">Info kuliner {{ $contohKuliner['nama'] }}</button>
                @endif
                @if ($contohTempat)
                    <button type="button">Rute setengah hari dari {{ $contohTempat['nama'] }}</button>
                    <button type="button">Tempat wisata dekat {{ $contohTempat['nama'] }}</button>
                @endif
            </div>
        </div>

        <form class="mv-asisten-panel__form" data-widget-form>
            <label class="sr-only" for="pesan-widget-asisten">Tulis pertanyaan soal wisata Medan</label>
            <textarea id="pesan-widget-asisten" name="pesan" rows="1" maxlength="300" placeholder="Tulis pertanyaan soal Medan…" required></textarea>
            <button type="submit" aria-label="Kirim pertanyaan">Kirim</button>
        </form>
        <p class="mv-asisten-panel__catatan">Berdasarkan katalog MedanVisit. Cek kembali jam buka dan harga bertanda contoh sebelum berangkat.</p>
    </div>
</div>
