// Benang merah: tab pasangan tempat. Rantai langkah dihitung server dari relasi graf (BFS);
// skrip ini hanya berganti panel dan menggambar ulang benang emas di peta kota lama.

const akar = document.querySelector('[data-benang]');
const tab = [...akar.querySelectorAll('[data-tab-benang]')];
const panel = [...akar.querySelectorAll('[data-panel-benang]')];

function gambar(i) {
    akar.querySelectorAll(`svg[data-panel-benang="${i}"] .utas, svg[data-panel-benang="${i}"] .utas-alas`).forEach((p) => {
        p.classList.remove('tergambar');
        void p.getBoundingClientRect();
        requestAnimationFrame(() => p.classList.add('tergambar'));
    });
}

function pilih(i, fokus = false) {
    tab.forEach((t) => {
        const aktif = t.dataset.tabBenang === String(i);
        t.setAttribute('aria-selected', aktif ? 'true' : 'false');
        t.tabIndex = aktif ? 0 : -1;
        if (aktif && fokus) t.focus();
    });
    panel.forEach((p) => (p.hidden = p.dataset.panelBenang !== String(i)));
    gambar(i);
}

tab.forEach((t, n) => {
    t.addEventListener('click', () => pilih(t.dataset.tabBenang));
    t.addEventListener('keydown', (e) => {
        const arah = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
        if (arah) {
            e.preventDefault();
            pilih(tab[(n + arah + tab.length) % tab.length].dataset.tabBenang, true);
        } else if (e.key === 'Home') {
            e.preventDefault();
            pilih(tab[0].dataset.tabBenang, true);
        } else if (e.key === 'End') {
            e.preventDefault();
            pilih(tab[tab.length - 1].dataset.tabBenang, true);
        }
    });
});

// benang pertama digambar saat seksi masuk layar
const io = new IntersectionObserver(([e]) => {
    if (e.isIntersecting) {
        gambar(tab.find((t) => t.getAttribute('aria-selected') === 'true')?.dataset.tabBenang ?? 0);
        io.disconnect();
    }
}, { threshold: 0.35 });
io.observe(akar);
