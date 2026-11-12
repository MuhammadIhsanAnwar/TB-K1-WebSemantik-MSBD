@php
    $contohPencarian = array_slice(array_values(array_filter(
        \App\Support\Katalog::entitas(),
        fn ($entitas) => $entitas['jenis'] !== 'wilayah'
    )), 0, 4);
@endphp
<dialog class="mv-dialog-cari" id="dialog-cari" data-ui data-dialog-cari data-saran-url="{{ route('cari.saran') }}" aria-label="Pencarian cepat MedanVisit">
    <div class="mv-dialog-cari__kotak">
        <div class="mv-dialog-cari__atas">
            <span class="mv-dialog-cari__judul">Cari di MedanVisit</span>
            <button type="button" class="mv-dialog-cari__tutup" data-tutup-cari>Tutup</button>
        </div>
        <form action="{{ route('cari') }}" method="get" role="search" data-cari-cepat>
            <div class="mv-dialog-cari__baris">
                <label class="sr-only" for="input-dialog-cari">Ketik nama tempat, tokoh, atau kuliner</label>
                <input id="input-dialog-cari" type="search" name="q" placeholder="Ketik nama tempat, tokoh, atau kuliner" autocomplete="off" required>
                <button class="mv-btn mv-btn--utama mv-btn--kecil" type="submit">Cari</button>
            </div>
            <div class="mv-dialog-cari__saring" role="group" aria-label="Jenis hasil">
                <button type="button" data-jenis-cari="" aria-pressed="true">Semua</button>
                <button type="button" data-jenis-cari="tempat" aria-pressed="false">Tempat</button>
                <button type="button" data-jenis-cari="sejarah" aria-pressed="false">Tokoh &amp; sejarah</button>
                <button type="button" data-jenis-cari="kuliner" aria-pressed="false">Kuliner</button>
            </div>
            <div class="mv-saran-kotak" data-saran-hasil hidden></div>
            <p class="mv-dialog-cari__petunjuk">Panah atas/bawah untuk memilih, Enter untuk membuka, Esc untuk menutup.</p>
        </form>
        <div class="mv-dialog-cari__terakhir" data-cari-terakhir hidden>
            <span>Terakhir dibuka</span>
            <ul></ul>
        </div>
        <div class="mv-dialog-cari__contoh">
            <span>Pencarian populer:</span>
            @foreach ($contohPencarian as $entitas)
                <a href="{{ route('cari', ['q' => $entitas['nama']]) }}">{{ $entitas['nama'] }}</a>
            @endforeach
        </div>
    </div>
</dialog>
