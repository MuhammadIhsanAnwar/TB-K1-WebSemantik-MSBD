// Asisten Tanya Medan: percakapan ke POST /tanya; jawaban disusun server dari katalog.
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

const akar = document.querySelector('[data-tanya]');
const log = akar.querySelector('[data-log]');
const form = akar.querySelector('form');
const kotak = form.querySelector('textarea');
const kirim = form.querySelector('button[type="submit"]');
const token = document.querySelector('meta[name="csrf-token"]')?.content;

// Maskot interaktif halaman tanya
const maskot = {
    menyapa: akar.dataset.maskotMenyapa || '/images/maskot/mascot-menyapa.png',
    mendengarkan: akar.dataset.maskotMendengarkan || '/images/maskot/mascot-mendengarkan.png',
    berpikir: akar.dataset.maskotBerpikir || '/images/maskot/mascot-berpikir.png',
    membantu: akar.dataset.maskotMembantu || '/images/maskot/mascot-membantu.png',
    bingung: akar.dataset.maskotBingung || '/images/maskot/mascot-bingung.png',
    sukses: akar.dataset.maskotSukses || '/images/maskot/mascot-sukses.png',
};

const maskotImg = akar.querySelector('[data-tanya-maskot]');
const lencanaEl = akar.querySelector('[data-tanya-lencana]');
const statusEl = akar.querySelector('[data-tanya-status]');
let timerEkspresi = null;

function aturEkspresi(tipe, lencanaTeks = '', statusTeks = '') {
    const url = maskot[tipe];
    if (!url) return;

    if (maskotImg && maskotImg.dataset.ekspresi !== tipe) {
        maskotImg.dataset.ekspresi = tipe;
        maskotImg.classList.add('mv-maskot--ganti');
        setTimeout(() => {
            maskotImg.src = url;
            maskotImg.classList.remove('mv-maskot--ganti');
        }, 120);
    }
    if (lencanaEl && lencanaTeks) lencanaEl.textContent = lencanaTeks;
    if (statusEl && statusTeks) statusEl.textContent = statusTeks;
}

function gulir() {
    akar.querySelector('.tanya__form')?.scrollIntoView({ block: 'end', behavior: 'smooth' });
}

function tambah(html, kelas) {
    const el = document.createElement('div');
    el.className = `gelembung ${kelas}`;
    el.innerHTML = html;
    log.append(el);
    gulir();
    return el;
}

function kartu(t) {
    const foto = t.foto ? `<img src="${esc(t.foto)}" alt="" width="72" height="56" loading="lazy">` : '<span class="tanpa-foto" aria-hidden="true"></span>';
    const sub = [t.jenis, t.harga, t.jarak != null ? `${Number(t.jarak).toLocaleString('id')} m` : null].filter(Boolean).join(', ');
    const aksi = [t.url ? `<a href="${esc(t.url)}">Lihat tempat</a>` : '', t.tiket ? `<a href="${esc(t.tiket)}">Pesan tiket</a>` : ''].join('');
    return `<div><a class="kartu-mini" href="${esc(t.url ?? '#')}">${foto}<span><b>${esc(t.nama)}</b><span>${esc(sub)}</span></span></a>${aksi ? `<div class="aksi-mini">${aksi}</div>` : ''}</div>`;
}

// Deteksi saat pengguna mengetik pertanyaan -> ekspresi mendengarkan
kotak?.addEventListener('input', () => {
    const v = kotak.value.trim();
    clearTimeout(timerEkspresi);
    if (v.length > 0) {
        aturEkspresi('mendengarkan', 'Mendengarkan', 'Sedang menyimak pertanyaanmu…');
        timerEkspresi = setTimeout(() => {
            aturEkspresi('menyapa', 'Siap Membantu', 'Tanyakan rute wisata, tiket, dan kuliner khas Kota Medan!');
        }, 4500);
    } else {
        aturEkspresi('menyapa', 'Siap Membantu', 'Tanyakan rute wisata, tiket, dan kuliner khas Kota Medan!');
    }
});

async function tanya(pesan) {
    tambah(`<p>${esc(pesan)}</p>`, 'gelembung--aku');
    akar.querySelector('[data-saran]')?.remove();

    clearTimeout(timerEkspresi);
    aturEkspresi('berpikir', 'Sedang Berpikir', 'Menelusuri katalog semantik & menyusun jawaban…');

    const ketik = document.createElement('div');
    ketik.className = 'mengetik';
    ketik.setAttribute('aria-label', 'Asisten sedang mengetik');
    ketik.innerHTML = `<img src="${esc(maskot.berpikir)}" alt="" width="26" height="26" class="mv-mini-maskot"><span></span><span></span><span></span>`;
    log.append(ketik);
    kirim.disabled = true;

    try {
        const res = await fetch(akar.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ pesan }),
        });
        if (res.status === 429) throw new Error('batas');
        if (!res.ok) throw new Error('galat');
        const j = await res.json();
        ketik.remove();

        const adaTempat = Array.isArray(j.tempat) && j.tempat.length > 0;
        const isi = (j.teks ?? []).map((p) => `<p>${esc(p)}</p>`).join('')
            + (j.tempat ?? []).map(kartu).join('')
            + (j.tautan ? `<div class="aksi-mini"><a href="${esc(j.tautan.url)}">${esc(j.tautan.label)}</a></div>` : '')
            + '<p class="sumber">Berdasarkan katalog MedanVisit</p>';
        tambah(isi, 'gelembung--asisten');

        const teksKosong = (!j.teks || j.teks.length === 0) && !adaTempat;
        if (teksKosong) {
            aturEkspresi('bingung', 'Data Belum Ada', 'Belum menemukan data yang sesuai di katalog.');
        } else if (adaTempat) {
            aturEkspresi('membantu', 'Rekomendasi Siap', 'Berikut rekomendasi tempat wisata yang kami temukan.');
        } else {
            aturEkspresi('sukses', 'Jawaban Siap', 'Semoga jawaban ini membantu wisatamu di Medan!');
        }

        clearTimeout(timerEkspresi);
        timerEkspresi = setTimeout(() => {
            aturEkspresi('menyapa', 'Siap Membantu', 'Ada destinasi atau rute lain yang ingin kamu cari?');
        }, 7000);

    } catch (e) {
        ketik.remove();
        aturEkspresi('bingung', 'Terjadi Kendala', 'Maaf, jawaban belum bisa dimuat. Coba lagi.');
        const pesanGalat = e.message === 'batas' ? 'Kamu terlalu cepat bertanya. Coba lagi sebentar ya.' : 'Jawaban tidak bisa dimuat. Periksa sambunganmu lalu coba lagi.';
        const el = tambah(`<p>${pesanGalat}</p><div class="aksi-mini"><a href="#" data-ulang>Coba lagi</a></div>`, 'gelembung--asisten');
        el.querySelector('[data-ulang]').addEventListener('click', (ev) => {
            ev.preventDefault();
            el.remove();
            log.lastElementChild?.remove();
            tanya(pesan);
        });
    } finally {
        kirim.disabled = false;
        kotak.focus();
    }
}

form.addEventListener('submit', (e) => {
    e.preventDefault();
    const pesan = kotak.value.trim();
    if (!pesan) return;
    kotak.value = '';
    tanya(pesan);
});
kotak.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        form.requestSubmit();
    }
});
akar.querySelectorAll('[data-saran] button, [data-saran-samping] button').forEach((b) => {
    b.addEventListener('click', () => tanya(b.textContent.trim()));
});
if (akar.dataset.awal) tanya(akar.dataset.awal);
