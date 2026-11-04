@php $kredit = \App\Support\Katalog::kredit($fotoKaki); @endphp
<footer class="kaki {{ $ringkas ? 'kaki--ringkas' : '' }}">
    <x-foto :berkas="$fotoKaki" alt="" class="kaki__foto" />
    <div class="kaki__isi">
        <div class="kaki__atas">
            <nav aria-label="Kaki">
                <a href="{{ route('jelajah') }}">Jelajah graf</a>
                <a href="{{ route('peta') }}">Peta wisata</a>
                <a href="{{ route('jalur') }}">Jalur tematik</a>
                <a href="{{ route('rencana') }}">Perencana rute</a>
                <a href="{{ route('tanya') }}">Tanya Medan</a>
                <a href="{{ route('data') }}">Data terbuka &amp; SPARQL</a>
                <a href="{{ route('kredit') }}">Kredit foto</a>
                <a href="{{ route('ketentuan') }}">Ketentuan &amp; privasi</a>
            </nav>
            <div class="kaki__kontak">
                <p>Panduan wisata Kota Medan: istana, rumah ibadah, kuliner, dan tokohnya, beserta hubungan di antara mereka.</p>
                <p><a href="mailto:administrator@medanvisit.neoverse.my.id">administrator@medanvisit.neoverse.my.id</a></p>
            </div>
        </div>
        <div class="kaki__nama" aria-hidden="true">
            <svg viewBox="0 0 1000 190" preserveAspectRatio="xMidYMax meet"><text x="0" y="168" textLength="1000" lengthAdjust="spacingAndGlyphs" font-size="200">MedanVisit</text></svg>
        </div>
        <div class="kaki__legal">
            <span>© {{ date('Y') }} MedanVisit. Harga, jam, dan koordinat bertanda “contoh” belum diverifikasi ke pengelola.</span>
            @if ($kredit)<span>Foto latar: {{ $kredit }}. <a href="{{ route('kredit') }}">Semua kredit foto</a></span>@endif
        </div>
    </div>
</footer>
