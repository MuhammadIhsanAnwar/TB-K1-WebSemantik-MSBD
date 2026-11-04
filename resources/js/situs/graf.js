// Jelajah graf dengan Cytoscape. Simpul berwarna kelas, jalur Benang Merah disorot emas,
// simpul lain diredupkan, sisi jarak (dekatDengan) putus-putus. Graf dimuat bertahap: kedalaman 1 dulu.
import cytoscape from 'cytoscape';

const WARNA = { tempat: '#e3a51c', tokoh: '#8e2c36', kerajaan: '#9b6b3d', peristiwa: '#2f6690', budaya: '#6a4c9c', kuliner: '#c9541e', wilayah: '#2e7d4f', transportasi: '#2f8a8a' };
const LABEL = { tempat: 'Tempat', tokoh: 'Tokoh', kerajaan: 'Kerajaan', peristiwa: 'Peristiwa', budaya: 'Budaya', kuliner: 'Kuliner', wilayah: 'Wilayah', transportasi: 'Transportasi' };
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

const akar = document.querySelector('[data-graf]');
const wadah = akar.querySelector('#graf');
const panel = document.querySelector('[data-panel-simpul]');
const jalur = JSON.parse(akar.dataset.jalur || '[]');
const status = document.querySelector('[data-status-graf]');

(async () => {
status.textContent = 'Memuat graf…';
const res = await fetch(akar.dataset.sumber, { headers: { Accept: 'application/json' } }).catch(() => null);
if (!res || !res.ok) {
    status.textContent = 'Graf tidak bisa dimuat. Muat ulang halaman untuk mencoba lagi.';
    return;
}
const data = await res.json();
status.textContent = '';

const cy = cytoscape({
    container: wadah,
    elements: [...data.nodes, ...data.edges],
    minZoom: 0.35,
    maxZoom: 2.5,
    wheelSensitivity: 0.25,
    style: [
        { selector: 'node', style: {
            'background-color': (n) => WARNA[n.data('jenis')], label: 'data(label)', 'font-family': 'Plus Jakarta Sans, system-ui, sans-serif',
            'font-size': 12, 'font-weight': 600, color: '#1b2622', 'text-valign': 'bottom', 'text-margin-y': 6, 'text-wrap': 'wrap', 'text-max-width': 110,
            width: (n) => (n.data('jenis') === 'tempat' ? 26 : 20), height: (n) => (n.data('jenis') === 'tempat' ? 26 : 20),
            'border-width': 3, 'border-color': '#f7f8f5', 'text-background-color': '#f7f8f5', 'text-background-opacity': 0.85, 'text-background-padding': 2,
        } },
        { selector: 'edge', style: {
            width: 1.6, 'line-color': '#b9c4bf', 'curve-style': 'bezier', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#b9c4bf', 'arrow-scale': 0.8,
            label: 'data(label)', 'font-size': 9, color: '#4a5852', 'text-rotation': 'autorotate', 'text-background-color': '#f7f8f5', 'text-background-opacity': 1, 'text-background-padding': 1,
        } },
        { selector: 'edge[predikat = "dekatDengan"]', style: { 'line-style': 'dashed', 'line-color': '#e3a51c', 'target-arrow-shape': 'none', width: 2 } },
        { selector: '.redup', style: { opacity: 0.16 } },
        { selector: 'edge.di-jalur', style: { width: 5, 'line-color': '#e3a51c', 'target-arrow-color': '#e3a51c', opacity: 1, 'z-index': 9, color: '#1b2622', 'font-size': 11, 'font-weight': 700 } },
        { selector: 'node.di-jalur', style: { 'border-color': '#e3a51c', 'border-width': 5, opacity: 1, 'z-index': 10, 'font-size': 13 } },
        { selector: 'node:selected', style: { 'border-color': '#0f3b2e', 'border-width': 5 } },
        { selector: 'node.tersembunyi, edge.tersembunyi', style: { display: 'none' } },
    ],
    layout: { name: 'cose', animate: false, nodeRepulsion: 9000, idealEdgeLength: 90, randomize: false, padding: 30 },
});

// ---- saring jenis simpul dan kedalaman ----
const jenisAktif = new Set([...document.querySelectorAll('[data-saring-jenis]')].filter((c) => c.checked).map((c) => c.value));
let kedalaman = Number(document.querySelector('[data-kedalaman][aria-pressed="true"]')?.dataset.kedalaman ?? 2);
let pusat = jalur[0] ?? 'istana-maimun';

function terapkan() {
    const dalam = new Set([pusat]);
    let tepi = new Set([pusat]);
    for (let d = 0; d < kedalaman; d++) {
        const baru = new Set();
        tepi.forEach((id) => cy.getElementById(id).neighborhood('node').forEach((n) => {
            if (!dalam.has(n.id()) && jenisAktif.has(n.data('jenis'))) baru.add(n.id());
        }));
        baru.forEach((id) => dalam.add(id));
        tepi = baru;
    }
    jalur.forEach((id) => dalam.add(id));
    cy.batch(() => {
        cy.nodes().forEach((n) => n.toggleClass('tersembunyi', !dalam.has(n.id())));
        cy.edges().forEach((e) => e.toggleClass('tersembunyi', !(dalam.has(e.source().id()) && dalam.has(e.target().id()))));
    });
    cy.layout({ name: 'cose', animate: false, nodeRepulsion: 9000, idealEdgeLength: 95, padding: 30, eles: cy.elements(':visible') }).run();
    sorotJalur();
    cy.fit(cy.elements(':visible'), 40);
    document.querySelector('[data-jumlah-terlihat]').textContent = `${cy.nodes(':visible').length} simpul terlihat`;
}

function sorotJalur() {
    cy.elements().removeClass('redup di-jalur');
    if (jalur.length < 2) return;
    const set = new Set(jalur);
    cy.edges().forEach((e) => {
        const i = jalur.indexOf(e.source().id());
        const j = jalur.indexOf(e.target().id());
        if (i > -1 && j > -1 && Math.abs(i - j) === 1) e.addClass('di-jalur');
    });
    cy.nodes().forEach((n) => (set.has(n.id()) ? n.addClass('di-jalur') : n.addClass('redup')));
    cy.edges().not('.di-jalur').addClass('redup');
}

function tampilkanPanel(n) {
    const d = n.data();
    const tetangga = n.connectedEdges().map((e) => {
        const lawan = e.source().id() === n.id() ? e.target() : e.source();
        return `<li><a href="#" data-lompat="${esc(lawan.id())}">${esc(lawan.data('label'))}</a> <span class="redup">${esc(e.data('label'))}</span></li>`;
    }).join('');
    panel.querySelector('[data-isi-simpul]').innerHTML = `
        <span class="kelas" data-kelas="${esc(d.jenis)}">${esc(LABEL[d.jenis])}</span>
        <h2>${esc(d.label)}</h2>
        <p style="margin-top:8px">${esc(d.ringkas)}</p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px">
            ${d.url ? `<a class="p-btn p-btn--utama" href="${esc(d.url)}">Buka halaman</a>` : ''}
            <button class="p-btn" type="button" data-kembangkan="${esc(d.id)}">Pusatkan di sini</button>
        </div>
        <h3 style="font:700 1rem var(--font-text);margin-top:24px;color:var(--ink)">Terhubung langsung (${n.connectedEdges().length})</h3>
        <ul class="relasi-teks" style="margin-top:10px">${tetangga}</ul>`;
}

cy.on('tap', 'node', (e) => tampilkanPanel(e.target));
panel.addEventListener('click', (e) => {
    const lompat = e.target.closest('[data-lompat]');
    if (lompat) {
        e.preventDefault();
        const n = cy.getElementById(lompat.dataset.lompat);
        if (n.hasClass('tersembunyi')) { pusat = n.id(); terapkan(); }
        cy.$(':selected').unselect();
        n.select();
        cy.animate({ center: { eles: n }, duration: 300 });
        tampilkanPanel(n);
    }
    const kembang = e.target.closest('[data-kembangkan]');
    if (kembang) {
        pusat = kembang.dataset.kembangkan;
        terapkan();
    }
});

document.querySelectorAll('[data-saring-jenis]').forEach((c) => c.addEventListener('change', () => {
    c.checked ? jenisAktif.add(c.value) : jenisAktif.delete(c.value);
    terapkan();
}));
document.querySelectorAll('[data-kedalaman]').forEach((b) => b.addEventListener('click', () => {
    kedalaman = Number(b.dataset.kedalaman);
    document.querySelectorAll('[data-kedalaman]').forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
    terapkan();
}));
document.querySelector('[data-zoom="masuk"]').addEventListener('click', () => cy.zoom({ level: cy.zoom() * 1.25, renderedPosition: { x: wadah.clientWidth / 2, y: wadah.clientHeight / 2 } }));
document.querySelector('[data-zoom="keluar"]').addEventListener('click', () => cy.zoom({ level: cy.zoom() / 1.25, renderedPosition: { x: wadah.clientWidth / 2, y: wadah.clientHeight / 2 } }));
document.querySelector('[data-zoom="pusat"]').addEventListener('click', () => cy.fit(cy.elements(':visible'), 40));

// tampilan daftar teks (alternatif untuk pembaca layar dan papan ketik)
const tombolDaftar = document.querySelector('[data-tampilan-daftar]');
const daftarAlt = document.querySelector('[data-daftar-alt]');
tombolDaftar.addEventListener('click', () => {
    const buka = daftarAlt.hidden;
    daftarAlt.hidden = !buka;
    tombolDaftar.setAttribute('aria-pressed', buka ? 'true' : 'false');
    tombolDaftar.textContent = buka ? 'Tampilan graf' : 'Tampilan daftar';
});

// cari simpul
const cari = document.querySelector('[data-cari-simpul]');
cari?.addEventListener('change', () => {
    const q = cari.value.trim().toLowerCase();
    const n = cy.nodes().filter((x) => x.data('label').toLowerCase().includes(q))[0];
    if (!n) return;
    pusat = n.id();
    terapkan();
    n.select();
    tampilkanPanel(n);
});

terapkan();
const awal = cy.getElementById(jalur[jalur.length - 1] ?? pusat);
if (awal.nonempty()) tampilkanPanel(awal);
})();
