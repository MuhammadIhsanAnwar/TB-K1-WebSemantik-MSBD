// Editor SPARQL: pilih contoh, jalankan, tampilkan hasil sebagai tabel atau JSON.
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

const akar = document.querySelector('[data-sparql]');
const pilih = akar.querySelector('[data-contoh]');
const teks = akar.querySelector('textarea');
const hasil = akar.querySelector('[data-hasil]');
const tombol = akar.querySelector('[data-jalankan]');
const contoh = JSON.parse(akar.dataset.contohQuery);
const token = document.querySelector('meta[name="csrf-token"]')?.content;
let terakhir = null;
let mode = 'tabel';

pilih.addEventListener('change', () => (teks.value = contoh[pilih.value].query));

function tampil() {
    if (!terakhir) return;
    if (!terakhir.ok) {
        hasil.innerHTML = `<div class="p-pesan p-pesan--info" role="status">${esc(terakhir.pesan)}</div>`;
        return;
    }
    if (mode === 'json') {
        hasil.innerHTML = `<pre style="margin:0;padding:16px;background:#fff;overflow:auto;font:13px/1.5 ui-monospace,Consolas,monospace">${esc(JSON.stringify({ head: { vars: terakhir.kolom }, results: { bindings: terakhir.baris.map((b) => Object.fromEntries(terakhir.kolom.map((k, i) => [k, { type: typeof b[i] === 'number' ? 'typed-literal' : 'literal', value: String(b[i]) }]))) } }, null, 2))}</pre>`;
        return;
    }
    const kepala = terakhir.kolom.map((k) => `<th class="${terakhir.baris.some((b) => typeof b[terakhir.kolom.indexOf(k)] === 'number') ? 'n' : ''}">${esc(k)}</th>`).join('');
    const isi = terakhir.baris.length
        ? terakhir.baris.map((b) => `<tr>${b.map((v) => `<td class="${typeof v === 'number' ? 'n' : ''}">${esc(typeof v === 'number' ? v.toLocaleString('id') : v)}</td>`).join('')}</tr>`).join('')
        : `<tr><td colspan="${terakhir.kolom.length}">0 baris.</td></tr>`;
    hasil.innerHTML = `<p class="kecil redup" style="margin-bottom:8px">${terakhir.baris.length} baris · ${esc(terakhir.catatan)}</p><div class="p-tabel-bungkus"><table class="p-tabel"><thead><tr>${kepala}</tr></thead><tbody>${isi}</tbody></table></div>`;
}

akar.querySelectorAll('[data-mode-hasil]').forEach((b) => b.addEventListener('click', () => {
    mode = b.dataset.modeHasil;
    akar.querySelectorAll('[data-mode-hasil]').forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
    tampil();
}));

akar.querySelector('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    tombol.disabled = true;
    hasil.innerHTML = '<p class="redup">Menjalankan query…</p>';
    try {
        const res = await fetch(akar.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ query: teks.value }),
        });
        if (res.status === 422) {
            terakhir = { ok: false, pesan: 'Query masih kosong.' };
        } else {
            terakhir = await res.json();
        }
    } catch {
        terakhir = { ok: false, pesan: 'Query tidak bisa dikirim. Periksa sambunganmu lalu coba lagi.' };
    }
    tombol.disabled = false;
    tampil();
});
