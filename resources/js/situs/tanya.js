// Asisten Tanya Medan: percakapan ke POST /tanya; jawaban disusun server dari katalog.
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

const akar = document.querySelector('[data-tanya]');
const log = akar.querySelector('[data-log]');
const form = akar.querySelector('form');
const kotak = form.querySelector('textarea');
const kirim = form.querySelector('button[type="submit"]');
const token = document.querySelector('meta[name="csrf-token"]')?.content;

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
    const sub = [t.jenis, t.harga, t.jarak != null ? `${Number(t.jarak).toLocaleString('id')} m` : null].filter(Boolean).join(' · ');
    const aksi = [t.url ? `<a href="${esc(t.url)}">Lihat</a>` : '', t.tiket ? `<a href="${esc(t.tiket)}">Pesan tiket</a>` : ''].join('');
    return `<div><a class="kartu-mini" href="${esc(t.url ?? '#')}">${foto}<span><b>${esc(t.nama)}</b><span>${esc(sub)}</span></span></a>${aksi ? `<div class="aksi-mini">${aksi}</div>` : ''}</div>`;
}

async function tanya(pesan) {
    tambah(`<p>${esc(pesan)}</p>`, 'gelembung--aku');
    akar.querySelector('[data-saran]')?.remove();
    const ketik = document.createElement('div');
    ketik.className = 'mengetik';
    ketik.setAttribute('aria-label', 'Asisten sedang mengetik');
    ketik.innerHTML = '<span></span><span></span><span></span>';
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
        const isi = (j.teks ?? []).map((p) => `<p>${esc(p)}</p>`).join('')
            + (j.tempat ?? []).map(kartu).join('')
            + (j.tautan ? `<div class="aksi-mini"><a href="${esc(j.tautan.url)}">${esc(j.tautan.label)}</a></div>` : '')
            + '<p class="sumber">Berdasarkan data MedanVisit</p>';
        tambah(isi, 'gelembung--asisten');
    } catch (e) {
        ketik.remove();
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
akar.querySelectorAll('[data-saran] button').forEach((b) => b.addEventListener('click', () => tanya(b.textContent.trim())));
if (akar.dataset.awal) tanya(akar.dataset.awal);
