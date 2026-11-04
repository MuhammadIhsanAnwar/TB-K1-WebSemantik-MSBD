// Benang merah dengan Cytoscape. Rantai hasil pencarian dikunci di satu garis mendatar dan disorot emas,
// simpul lain disusun di sekitarnya lalu diredupkan. Label sisi hanya tampil di rantai dan di sekitar simpul terpilih.
import cytoscape from 'cytoscape';

const WARNA = { tempat: '#e3a51c', tokoh: '#8e2c36', kerajaan: '#9b6b3d', peristiwa: '#2f6690', budaya: '#6a4c9c', kuliner: '#c9541e', wilayah: '#2e7d4f', transportasi: '#2f8a8a' };
const LABEL = { tempat: 'Tempat', tokoh: 'Tokoh', kerajaan: 'Kerajaan', peristiwa: 'Peristiwa', budaya: 'Budaya', kuliner: 'Kuliner', wilayah: 'Wilayah', transportasi: 'Transportasi' };
const LATAR = '#f4f6f2';
const ZOOM_PAS = 1.15;
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

const akar = document.querySelector('[data-graf]');
const wadah = akar.querySelector('#graf');
const panel = document.querySelector('[data-panel-simpul]');
const isiSimpul = panel.querySelector('[data-isi-simpul]');
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
    minZoom: 0.3,
    maxZoom: 2.2,
    wheelSensitivity: 0.25,
    style: [
        { selector: 'node', style: {
            'background-color': (n) => WARNA[n.data('jenis')], label: 'data(label)', 'font-family': 'Plus Jakarta Sans, system-ui, sans-serif',
            'font-size': 14, 'font-weight': 600, color: '#1b2622', 'text-valign': 'bottom', 'text-margin-y': 6, 'text-wrap': 'wrap', 'text-max-width': 130,
            width: (n) => (n.data('jenis') === 'tempat' ? 24 : 18), height: (n) => (n.data('jenis') === 'tempat' ? 24 : 18),
            'border-width': 3, 'border-color': LATAR, 'text-background-color': LATAR, 'text-background-opacity': 0.9, 'text-background-padding': 2,
            'min-zoomed-font-size': 6,
        } },
        { selector: 'edge', style: {
            width: 1.5, 'line-color': '#b9c4bf', 'curve-style': 'bezier', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#b9c4bf', 'arrow-scale': 0.8,
            'font-family': 'Plus Jakarta Sans, system-ui, sans-serif', 'font-size': 12, 'font-weight': 600, color: '#3f574e',
            'text-rotation': 'autorotate', 'text-background-color': LATAR, 'text-background-opacity': 1, 'text-background-padding': 2,
        } },
        { selector: 'edge[predikat = "dekatDengan"]', style: { 'line-style': 'dashed', 'target-arrow-shape': 'none' } },
        { selector: '.redup', style: { opacity: 0.5 } },
        { selector: 'edge.di-jalur', style: { label: 'data(label)', width: 5, 'line-color': '#e3a51c', 'target-arrow-color': '#e3a51c', opacity: 1, 'z-index': 9, color: '#1b2622' } },
        { selector: 'node.di-jalur', style: { width: 30, height: 30, 'border-color': '#e3a51c', 'border-width': 5, opacity: 1, 'z-index': 10, 'font-size': 15, 'font-weight': 700 } },
        { selector: 'node.sekitar', style: { opacity: 1 } },
        { selector: 'edge.sekitar', style: { label: 'data(label)', opacity: 1, width: 2.5, 'line-color': '#0f3b2e', 'target-arrow-color': '#0f3b2e', 'z-index': 8 } },
        { selector: 'edge.di-jalur.sekitar', style: { width: 5, 'line-color': '#e3a51c', 'target-arrow-color': '#e3a51c', 'z-index': 9 } },
        { selector: 'edge.label-datar', style: { 'text-rotation': 'none' } },
        { selector: 'node.terpilih', style: { opacity: 1, 'border-color': '#0f3b2e', 'border-width': 5, 'z-index': 11 } },
        { selector: '.tersembunyi', style: { display: 'none' } },
    ],
});

// ---- saring jenis simpul dan kedalaman ----
const jenisAktif = new Set([...document.querySelectorAll('[data-saring-jenis]')].filter((c) => c.checked).map((c) => c.value));
let kedalaman = Number(document.querySelector('[data-kedalaman][aria-pressed="true"]')?.dataset.kedalaman ?? 2);
let pusat = jalur[0] ?? 'istana-maimun';
const terlihat = () => cy.elements().not('.tersembunyi');
// kanvas potret (ponsel, panel sempit): rantai disusun tegak supaya tidak dimampatkan sampai labelnya tak terbaca
const tegak = () => wadah.clientHeight > wadah.clientWidth * 1.1;

// Susunan sendiri, bukan cose, supaya hasilnya sama setiap kali dan rantai selalu lurus.
// Rantai (atau simpul pusat bila tak ada rantai) dipasang di satu garis, mendatar atau tegak. Tetangga tiap
// simpul rantai dikipaskan bergantian ke dua sisi garis; cabang berikutnya meneruskan arah induknya.
// Terakhir, simpul yang labelnya bertumpuk didorong saling menjauh.
function susun() {
    const jangkar = (jalur.length > 1 ? jalur : [pusat]).map((id) => cy.getElementById(id)).filter((n) => n.nonempty() && !n.hasClass('tersembunyi'));
    const tunggal = jangkar.length === 1;
    const sumbuTegak = tegak();
    const pos = new Map();
    const sudut = new Map();
    jangkar.forEach((n, i) => {
        const t = i - (jangkar.length - 1) / 2;
        pos.set(n.id(), sumbuTegak ? { x: 0, y: t * 120 } : { x: t * 185, y: 0 });
    });
    cy.edges().toggleClass('label-datar', sumbuTegak);

    let antre = jangkar;
    while (antre.length) {
        const berikut = [];
        antre.forEach((induk) => {
            const anak = induk.neighborhood('node').filter((n) => !pos.has(n.id()) && !n.hasClass('tersembunyi'));
            if (!anak.length) return;
            const i = jangkar.indexOf(induk);
            const p = pos.get(induk.id());
            let dasar, lebar, jari;
            if (i > -1) {
                // simpul rantai: kipas ke atas/kiri untuk urutan genap, ke bawah/kanan untuk ganjil;
                // kedua ujung rantai dimiringkan keluar (keAwal memutar kipas ke arah awal rantai)
                const genap = i % 2 === 0;
                const keAwal = sumbuTegak ? (genap ? 0.35 : -0.35) : (genap ? -0.35 : 0.35);
                dasar = tunggal ? -Math.PI / 2 : sumbuTegak ? (genap ? Math.PI : 0) : (genap ? -1 : 1) * Math.PI / 2;
                if (!tunggal && i === 0) dasar += keAwal;
                if (!tunggal && i === jangkar.length - 1) dasar -= keAwal;
                lebar = tunggal ? Math.PI * 2 * (1 - 1 / anak.length) : Math.min(Math.PI * 0.9, 0.5 * anak.length);
                jari = Math.max(150, (75 * anak.length) / Math.max(lebar, 1));
            } else {
                dasar = sudut.get(induk.id());
                lebar = Math.min(Math.PI * 0.6, 0.45 * anak.length);
                jari = 130;
            }
            anak.forEach((n, j) => {
                const a = anak.length === 1 ? dasar : dasar - lebar / 2 + (lebar * j) / (anak.length - 1);
                const r = jari + (j % 2) * 50; // berselang, supaya label tidak berbaris sejajar
                pos.set(n.id(), { x: p.x + r * Math.cos(a), y: p.y + r * Math.sin(a) });
                sudut.set(n.id(), a);
                berikut.push(n);
            });
        });
        antre = berikut;
    }
    // simpul yang tak tersambung ke rantai (mis. setelah "Pusatkan di sini") berbaris di bawah
    let sisa = 0;
    const bawah = Math.max(...[...pos.values()].map((p) => p.y)) + 180;
    terlihat().nodes().forEach((n) => {
        if (!pos.has(n.id())) pos.set(n.id(), { x: (sisa % 3 - 1) * 170, y: bawah + Math.floor(sisa++ / 3) * 110 });
    });

    // Dorong menjauh simpul yang terlalu rapat. Jarak diukur sebagai elips selebar label (140 x 64 px),
    // simpul rantai tidak ikut bergeser.
    const tetap = new Set(jangkar.map((n) => n.id()));
    const id = [...pos.keys()];
    for (let putaran = 0; putaran < 80; putaran++) {
        let geser = false;
        for (let a = 0; a < id.length; a++) {
            for (let b = a + 1; b < id.length; b++) {
                const pa = pos.get(id[a]);
                const pb = pos.get(id[b]);
                let dx = pb.x - pa.x;
                let dy = pb.y - pa.y;
                if (dx === 0 && dy === 0) dy = 1;
                const nd = Math.hypot(dx / 140, dy / 64);
                if (nd >= 1) continue;
                const d = Math.hypot(dx, dy);
                const dorong = (d / nd - d) / d;
                const ka = tetap.has(id[a]) ? 0 : tetap.has(id[b]) ? 1 : 0.5;
                const kb = tetap.has(id[b]) ? 0 : 1 - ka;
                pa.x -= dx * dorong * ka; pa.y -= dy * dorong * ka;
                pb.x += dx * dorong * kb; pb.y += dy * dorong * kb;
                geser = true;
            }
        }
        if (!geser) break;
    }
    cy.batch(() => pos.forEach((p, k) => cy.getElementById(k).position(p)));
}

// Graf kecil tidak diperbesar melewati ZOOM_PAS, supaya label tidak membesar dan saling tumpuk.
function pas() {
    cy.fit(terlihat(), 40);
    if (cy.zoom() > ZOOM_PAS) {
        cy.zoom(ZOOM_PAS);
        cy.center(terlihat());
    }
}

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
    susun();
    sorotJalur();
    pas();
    document.querySelector('[data-jumlah-terlihat]').textContent = `${cy.nodes().not('.tersembunyi').length} simpul terlihat`;
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

// simpul terpilih dan tetangganya tampil penuh, sisi-sisinya berlabel
function sorot(n) {
    cy.elements().removeClass('terpilih sekitar');
    if (!n) return;
    n.addClass('terpilih');
    n.connectedEdges().addClass('sekitar');
    n.neighborhood('node').addClass('sekitar');
}

function tandaiRantai(id) {
    panel.querySelectorAll('.mv-rantai [data-lompat]').forEach((b) => b.setAttribute('aria-current', b.dataset.lompat === id ? 'true' : 'false'));
}

function tampilkanPanel(n) {
    const d = n.data();
    const sisi = n.connectedEdges();
    const tetangga = sisi.map((e) => {
        const maju = e.source().id() === n.id();
        const lawan = maju ? e.target() : e.source();
        return `<li><span>${esc(maju ? e.data('label') : e.data('label_balik'))}</span>
            <button type="button" data-lompat="${esc(lawan.id())}"><span class="mv-graf__titik" data-kelas="${esc(lawan.data('jenis'))}"></span>${esc(lawan.data('label'))}</button></li>`;
    }).join('');
    isiSimpul.innerHTML = `
        <span class="kelas" data-kelas="${esc(d.jenis)}">${esc(LABEL[d.jenis])}</span>
        <h2>${esc(d.label)}</h2>
        ${d.ringkas ? `<p>${esc(d.ringkas)}</p>` : ''}
        <div class="mv-graf__aksi">
            ${d.url ? `<a class="mv-btn mv-btn--hijau mv-btn--kecil" href="${esc(d.url)}">Buka halaman</a>` : ''}
            <button class="mv-graf__tombol" type="button" data-kembangkan="${esc(d.id)}">Pusatkan di sini</button>
        </div>
        <h3>Terhubung langsung (${sisi.length})</h3>
        <ul class="mv-graf__tetangga">${tetangga}</ul>`;
    tandaiRantai(d.id);
}

function pilih(n) {
    cy.$(':selected').unselect();
    n.select();
    sorot(n);
    tampilkanPanel(n);
}

cy.on('tap', 'node', (e) => pilih(e.target));
cy.on('tap', (e) => {
    if (e.target !== cy) return;
    cy.$(':selected').unselect();
    sorot(null);
});

panel.addEventListener('click', (e) => {
    const lompat = e.target.closest('[data-lompat]');
    if (lompat) {
        e.preventDefault();
        const n = cy.getElementById(lompat.dataset.lompat);
        if (n.hasClass('tersembunyi')) { pusat = n.id(); terapkan(); }
        pilih(n);
        cy.animate({ center: { eles: n }, duration: 300 });
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
const tengah = () => ({ x: wadah.clientWidth / 2, y: wadah.clientHeight / 2 });
document.querySelector('[data-zoom="masuk"]').addEventListener('click', () => cy.zoom({ level: cy.zoom() * 1.25, renderedPosition: tengah() }));
document.querySelector('[data-zoom="keluar"]').addEventListener('click', () => cy.zoom({ level: cy.zoom() / 1.25, renderedPosition: tengah() }));
document.querySelector('[data-zoom="pusat"]').addEventListener('click', pas);

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
    if (!q || !n) return;
    pusat = n.id();
    terapkan();
    pilih(n);
});

// ukuran kanvas berubah (jendela diubah, layar diputar): susun ulang bila orientasinya berganti, selain itu pas ulang
let tegakTadi = tegak();
new ResizeObserver(() => {
    cy.resize();
    if (tegak() !== tegakTadi) {
        tegakTadi = tegak();
        terapkan();
    } else {
        pas();
    }
}).observe(wadah);

terapkan();
})();
