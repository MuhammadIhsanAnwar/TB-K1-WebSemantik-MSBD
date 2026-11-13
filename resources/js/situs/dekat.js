// Beranda: sarankan tiga tempat wisata terdekat dari lokasi pengunjung.
// Izin lokasi langsung diminta saat beranda dibuka. Kalau permintaan itu ditutup tanpa jawaban, beranda
// tidak bertanya otomatis lagi selama 7 hari dan menampilkan tombol. Kalau izin ditolak, bagian ini disembunyikan.
import { bukaPada, jarakMeter, waktuWib } from './jadwal.js';

const blok = document.querySelector('[data-dekat]');
const tempat = JSON.parse(blok.dataset.dekat);
const status = blok.querySelector('[data-dekat-status]');
const daftar = blok.querySelector('[data-dekat-daftar]');
const tombol = blok.querySelector('[data-dekat-minta]');
const tautanPeta = blok.querySelector('[data-dekat-peta]');
const KUNCI = 'mv-lokasi-ditunda';
const TUNDA = 7 * 24 * 60 * 60 * 1000;

const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const fmtJarak = (m) => (m < 1000 ? `${m.toLocaleString('id')} m` : `${(m / 1000).toLocaleString('id', { maximumFractionDigits: 1 })} km`);

function ditunda() {
    try {
        return Date.now() - Number(localStorage.getItem(KUNCI) || 0) < TUNDA;
    } catch {
        return false;
    }
}

function tawarkanTombol(teks) {
    blok.hidden = false;
    status.textContent = teks;
    tombol.hidden = false;
}

function tampilkan(pos) {
    const lokasi = { lat: pos.coords.latitude, lng: pos.coords.longitude };
    const kini = waktuWib();
    const terdekat = tempat
        .map((t) => ({ ...t, jarak: jarakMeter(lokasi, t) }))
        .sort((a, b) => a.jarak - b.jarak)
        .slice(0, 3);
    status.textContent = terdekat[0].jarak > 30000
        ? 'Kamu sedang di luar Medan. Ini tempat yang paling dekat dari posisimu.'
        : 'Tiga tempat terdekat dari lokasimu sekarang.';
    daftar.innerHTML = terdekat.map((t) => {
        const jam = t.buka
            ? `<span class="mv-lencana-jam ${bukaPada(t.buka, kini) ? 'mv-lencana-jam--buka">Buka' : 'mv-lencana-jam--tutup">Tutup'}</span>`
            : '';
        return `<li class="mv-dekat__butir"><a href="${esc(t.url)}">${esc(t.nama)}</a><span class="mv-dekat__meta">${esc(t.kategori)}, ${fmtJarak(t.jarak)}</span>${jam}</li>`;
    }).join('');
    daftar.hidden = false;
    tautanPeta.hidden = false;
    tombol.hidden = true;
    blok.removeAttribute('aria-busy');
}

function minta() {
    blok.hidden = false;
    tombol.hidden = true;
    blok.setAttribute('aria-busy', 'true');
    status.textContent = 'Mencari tempat terdekat dari lokasimu…';
    navigator.geolocation.getCurrentPosition(tampilkan, (galat) => {
        blok.removeAttribute('aria-busy');
        if (galat.code !== galat.PERMISSION_DENIED) {
            tawarkanTombol('Lokasimu belum terbaca.');
            return;
        }
        // permintaan ditutup tanpa jawaban (izin masih "prompt") atau benar-benar ditolak
        const cek = navigator.permissions?.query ? navigator.permissions.query({ name: 'geolocation' }) : Promise.resolve({ state: 'denied' });
        cek.then((izin) => {
            if (izin.state === 'denied') {
                blok.hidden = true;
                return;
            }
            try { localStorage.setItem(KUNCI, String(Date.now())); } catch { /* penyimpanan tidak tersedia */ }
            tawarkanTombol('Izinkan lokasi untuk melihat tempat wisata terdekat.');
        }).catch(() => { blok.hidden = true; });
    }, { enableHighAccuracy: false, timeout: 15000, maximumAge: 300000 });
}

function mulai(keadaan) {
