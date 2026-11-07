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
        const kurang = b.querySelector('[data-kurang]');
        const tambah = b.querySelector('[data-tambah]');
        const min = Number(input.min) || 0;
        const maks = Number(input.max) || 10;
        // tombol mati di batas, supaya jelas jumlahnya tidak bisa turun/naik lagi
        const aturBatas = () => {
            const n = Number(input.value) || 0;
            kurang.disabled = n <= min;
            tambah.disabled = n >= maks;
        };
        const ubah = (arah) => {
            const lama = Number(input.value) || 0;
            const baru = Math.min(maks, Math.max(min, lama + arah));
            if (baru === lama) return false;
            input.value = baru;
            input.dataset.arah = arah > 0 ? 'naik' : 'turun';
            input.classList.remove('berganti');
            void input.offsetWidth; // ulang animasi angka
            input.classList.add('berganti');
            aturBatas();
            hitung();
            return true;
        };
        // tahan tombol: setelah 400 ms jumlah berubah terus, makin lama makin cepat
        const tahan = (tombol, arah) => {
            let jeda = null;
            let selang = 180;
            const henti = () => { clearTimeout(jeda); selang = 180; };
            const ulang = () => {
                if (!ubah(arah)) return henti();
                selang = Math.max(70, selang * 0.85);
                jeda = setTimeout(ulang, selang);
            };
            tombol.addEventListener('pointerdown', (e) => {
                if (e.button !== 0) return;
                ubah(arah);
                jeda = setTimeout(ulang, 400);
            });
            ['pointerup', 'pointerleave', 'pointercancel'].forEach((ev) => tombol.addEventListener(ev, henti));
            // papan ketik: Enter/Spasi memicu click tanpa pointerdown
            tombol.addEventListener('click', (e) => { if (e.detail === 0) ubah(arah); });
        };
        tahan(kurang, -1);
        tahan(tambah, 1);
        input.addEventListener('input', () => { aturBatas(); hitung(); });
        aturBatas();
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

