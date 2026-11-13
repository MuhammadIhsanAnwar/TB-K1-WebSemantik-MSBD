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
