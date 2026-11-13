// MedanVisit: titik masuk skrip. Modul berat (peta, graf, QR) hanya dimuat di halaman yang memakainya.
import './situs/dasar.js';

const ada = (sel) => document.querySelector(sel);

if (ada('[data-peta-koordinat]')) {
    import('./admin/peta-koordinat.js')
        .catch((error) => {
            console.error(
                'Gagal memuat modul peta admin:',
                error
            );
        });
}
if (ada('[data-panggung]')) import('./situs/panggung.js');
if (ada('[data-benang]')) import('./situs/benang.js');
if (ada('[data-jalur-pin]')) import('./situs/jalur-pin.js');
if (ada('[data-peta]')) import('./situs/peta.js');
if (ada('[data-dekat]')) import('./situs/dekat.js');
if (ada('[data-graf]')) import('./situs/graf.js');
if (ada('[data-tanya]')) import('./situs/tanya.js');
if (ada('[data-checkin]')) import('./situs/checkin.js');
if (ada('[data-lorong]')) import('./situs/lorong.js');
if (ada('[data-tiket]')) import('./situs/tiket.js');
if (ada('[data-sparql]')) import('./situs/sparql.js');
if (ada('[data-panel-app]')) import('./panel/panel.js');
