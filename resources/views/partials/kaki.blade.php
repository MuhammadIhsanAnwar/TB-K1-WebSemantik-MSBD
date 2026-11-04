@php $kredit = \App\Support\Katalog::kredit($fotoKaki); @endphp
<footer class="mv-kaki {{ $ringkas ? 'mv-kaki--ringkas' : '' }} tidak-dicetak">
    <x-foto :berkas="$fotoKaki" alt="" class="mv-kaki__foto" />
    <div class="mv-kaki__lapisan" aria-hidden="true"></div>
    <div class="mv-kaki__isi">
        <div class="mv-kaki__atas">
            <nav class="mv-kaki__tautan" aria-label="Navigasi kaki halaman">
                <a href="{{ route('peta') }}">Tempat &amp; peta</a>
                <a href="{{ route('jalur') }}">Jalur tematik</a>
                <a href="{{ route('rencana') }}">Perencana rute</a>
                <a href="{{ route('jelajah') }}">Cerita Medan (Graf)</a>
                <a href="{{ route('tanya') }}">Tanya asisten</a>
                <a href="{{ route('data') }}">Data terbuka &amp; SPARQL</a>
                <a href="{{ route('kredit') }}">Kredit foto</a>
                <a href="{{ route('ketentuan') }}">Ketentuan &amp; privasi</a>
            </nav>
            <div class="mv-kaki__catatan">
                <p>Jelajahi Medan, bawa pulang ceritanya. Panduan wisata berbasis jaringan cerita yang menautkan tempat, tokoh, budaya, dan kuliner Kota Medan.</p>
                <p><a href="mailto:administrator@medanvisit.neoverse.my.id">administrator@medanvisit.neoverse.my.id</a></p>
                <p class="mv-kaki__jujur">Keterangan bertanda “contoh” pada harga, jam buka, dan koordinat masih dalam tahap verifikasi pengelola.</p>
            </div>
        </div>

        <div class="mv-kaki__merek" aria-hidden="true">
            <svg viewBox="0 0 1000 185" preserveAspectRatio="xMidYMax meet">
                <text x="0" y="162" textLength="1000" lengthAdjust="spacingAndGlyphs" font-size="192">MedanVisit</text>
            </svg>
        </div>

        <div class="mv-kaki__legal">
            <span class="mv-meta-garis">
                <span>© {{ date('Y') }} MedanVisit</span>
                <span>Peta © kontributor OpenStreetMap</span>
                <span>Data tertaut ke Wikidata</span>
            </span>
            @if ($kredit)
                <span class="mv-meta-garis">
                    <span>Foto latar: {{ $kredit }}</span>
                    <a href="{{ route('kredit') }}">Daftar lengkap kredit</a>
                </span>
            @endif
        </div>
    </div>
</footer>
