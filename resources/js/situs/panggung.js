// Panggung beranda: peta 1930 tetap di layar. Membuka satu jenis tempat menyalakan titiknya di peta;
// mengarahkan kursor (atau fokus) ke satu tempat memudarkan peta ke fotonya. Gerak khas: crossfade pelan.

const layar = document.querySelector('.panggung__layar');
const gambar = [...layar.querySelectorAll('[data-layar]')];
const petaSvg = layar.querySelector('[data-layar-peta]');
const kiri = layar.querySelector('[data-bilah-kiri]');
const kanan = layar.querySelector('[data-bilah-kanan]');
const awal = { kiri: kiri.textContent, kanan: kanan.textContent };
const titik = [...petaSvg.querySelectorAll('[data-titik]')];

function tampilkan(kunci) {
    const ada = gambar.some((g) => g.dataset.layar === kunci);
    const pilih = ada ? kunci : 'peta';
    gambar.forEach((g) => g.classList.toggle('aktif', g.dataset.layar === pilih));
    petaSvg.toggleAttribute('hidden', pilih !== 'peta');
}

function sorotKategori(kategori, label) {
    titik.forEach((g) => {
        g.classList.toggle('redam', kategori && g.dataset.kategori !== kategori);
        g.classList.toggle('sorot', kategori && g.dataset.kategori === kategori);
    });
    if (kategori) {
        const n = titik.filter((g) => g.dataset.kategori === kategori).length;
        kiri.textContent = label;
        kanan.textContent = n ? `${n} di peta 1930` : 'Di luar bingkai peta 1930';
    } else {
        kiri.textContent = awal.kiri;
        kanan.textContent = awal.kanan;
    }
}

let kategoriAktif = null;
let labelAktif = '';
const bilah = [...document.querySelectorAll('.bilah[data-kategori]')];
bilah.forEach((d) => {
    d.addEventListener('toggle', () => {
        if (d.open) {
            bilah.filter((x) => x !== d).forEach((x) => (x.open = false));
            kategoriAktif = d.dataset.kategori;
            labelAktif = d.querySelector('summary').childNodes[0].textContent.trim();
            sorotKategori(kategoriAktif, labelAktif);
        } else if (kategoriAktif === d.dataset.kategori) {
            kategoriAktif = null;
            sorotKategori(null);
        }
    });
});

let tunda;
document.querySelectorAll('.baris-tempat[data-sorot]').forEach((a) => {
    const masuk = () => {
        clearTimeout(tunda);
        tampilkan(a.dataset.sorot);
        kiri.textContent = a.dataset.label;
        kanan.textContent = a.dataset.sub;
        titik.forEach((g) => g.classList.toggle('sorot', g.dataset.titik === a.dataset.sorot));
    };
    const keluar = () => {
        tunda = setTimeout(() => {
            tampilkan('peta');
            sorotKategori(kategoriAktif, labelAktif);
        }, 260);
    };
    a.addEventListener('pointerenter', masuk);
    a.addEventListener('focus', masuk);
    a.addEventListener('pointerleave', keluar);
    a.addEventListener('blur', keluar);
});

// jenis pertama terbuka saat halaman dibuka: sorot titiknya juga
const terbuka = bilah.find((d) => d.open);
if (terbuka) {
    kategoriAktif = terbuka.dataset.kategori;
    labelAktif = terbuka.querySelector('summary').childNodes[0].textContent.trim();
    // sorotan baru dinyalakan saat kolom tempat masuk layar, supaya layar pertama menampilkan peta utuh
    const io = new IntersectionObserver(([e]) => {
        if (e.isIntersecting) {
            sorotKategori(kategoriAktif, labelAktif);
            io.disconnect();
        }
    }, { threshold: 0.25 });
    io.observe(document.querySelector('.daftar-besar'));
}
