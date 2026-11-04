<dialog class="mv-dialog-cari" id="dialog-cari" data-ui data-dialog-cari data-saran-url="{{ route('cari.saran') }}" aria-label="Pencarian cepat MedanVisit">
    <div class="mv-dialog-cari__kotak">
        <div class="mv-dialog-cari__atas">
            <span class="mv-dialog-cari__judul">Cari di MedanVisit</span>
            <button type="button" class="mv-dialog-cari__tutup" data-tutup-cari>Tutup</button>
        </div>
        <form action="{{ route('cari') }}" method="get" role="search" data-cari-cepat>
            <div class="mv-dialog-cari__baris">
                <label class="sr-only" for="input-dialog-cari">Ketik nama tempat, tokoh, atau kuliner</label>
                <input id="input-dialog-cari" type="search" name="q" placeholder="Ketik tempat, tokoh, kuliner (coba: istana maimon)" autocomplete="off" required>
                <button class="mv-btn mv-btn--utama mv-btn--kecil" type="submit">Cari</button>
            </div>
            <div class="mv-saran-kotak" data-saran-hasil role="region" aria-live="polite" hidden></div>
        </form>
        <div class="mv-dialog-cari__contoh">
            <span>Pencarian populer:</span>
            <a href="{{ route('cari', ['q' => 'istana maimon']) }}">istana maimon</a>
            <a href="{{ route('cari', ['q' => 'tjong a fie']) }}">Tjong A Fie</a>
            <a href="{{ route('cari', ['q' => 'bubur pedas']) }}">Bubur pedas</a>
            <a href="{{ route('cari', ['q' => 'rumah ibadah']) }}">Rumah ibadah</a>
        </div>
    </div>
</dialog>

