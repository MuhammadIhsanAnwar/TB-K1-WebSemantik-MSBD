// Tiket: penghitung jumlah dengan total yang ikut berubah, hitung mundur batas bayar, QR e-tiket, simpan PDF.
import QRCode from 'qrcode';

const rp = (n) => `Rp${Number(n).toLocaleString('id')}`;

// ---- pilih tiket ----
const pilih = document.querySelector('[data-tiket="pilih"]');
if (pilih) {
    const baris = [...pilih.querySelectorAll('[data-harga]')];
    const total = pilih.querySelector('[data-total]');
    const rincian = pilih.querySelector('[data-rincian]');
    const lanjut = pilih.querySelector('[data-lanjut]');
    const hitung = () => {
        let t = 0;
        const teks = [];
        baris.forEach((b) => {
            const n = Number(b.querySelector('input').value) || 0;
            t += n * Number(b.dataset.harga);
            if (n) teks.push(`${n} ${b.dataset.jenis}`);
        });
        total.textContent = rp(t);
        rincian.textContent = teks.length ? teks.join(', ') : 'Belum ada tiket dipilih';
        lanjut.disabled = t === 0;
    };
    baris.forEach((b) => {
        const input = b.querySelector('input');
        b.querySelector('[data-kurang]').addEventListener('click', () => { input.value = Math.max(0, (Number(input.value) || 0) - 1); hitung(); });
        b.querySelector('[data-tambah]').addEventListener('click', () => { input.value = Math.min(10, (Number(input.value) || 0) + 1); hitung(); });
        input.addEventListener('input', hitung);
    });
    hitung();
}

// ---- hitung mundur batas bayar ----
const mundur = document.querySelector('[data-batas]');
if (mundur) {
    const batas = new Date(mundur.dataset.batas).getTime();
    const tik = () => {
        const sisa = Math.max(0, Math.floor((batas - Date.now()) / 1000));
        mundur.textContent = `${String(Math.floor(sisa / 60)).padStart(2, '0')}:${String(sisa % 60).padStart(2, '0')}`;
        if (sisa === 0) {
            clearInterval(jam);
            location.reload();
        }
    };
    const jam = setInterval(tik, 1000);
    tik();
}

// ---- QR e-tiket dan QRIS ----
document.querySelectorAll('[data-qr]').forEach(async (el) => {
    try {
        el.innerHTML = await QRCode.toString(el.dataset.qr, { type: 'svg', errorCorrectionLevel: 'M', margin: 1, color: { dark: '#0f3b2e', light: '#ffffff' } });
        el.querySelector('svg')?.setAttribute('role', 'img');
        el.querySelector('svg')?.setAttribute('aria-label', el.dataset.qrLabel || 'Kode QR');
    } catch {
        el.textContent = 'Kode QR tidak bisa dibuat. Tunjukkan kode pemesanan di bawahnya.';
    }
});

