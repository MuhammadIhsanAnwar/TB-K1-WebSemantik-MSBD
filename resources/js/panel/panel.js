// Dashboard mitra & kurator: grafik dari data di atribut (Charts dari tasteskill). Menu samping ada di dasar.js.
import './charts.js';

const C = window.Charts;
document.querySelectorAll('[data-grafik]').forEach((el) => {
    const d = JSON.parse(el.dataset.grafik);
    if (d.jenis === 'batang') {
        C.bars(el, { labels: d.label, values: d.nilai, format: C.fmt.int, highlight: d.sorot, xAt: d.xAt });
    } else if (d.jenis === 'garis') {
        C.line(el, { labels: d.label, series: [{ name: d.nama, values: d.nilai }], format: C.fmt.int, xAt: d.xAt });
    }
});
