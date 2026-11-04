// Jalur tematik: panggung dipin satu layar; setiap jalur mendapat satu tinggi layar gulir.
// Saat jalur tiba, fotonya disapu masuk dari bawah dan barisnya menjadi tinta penuh.

const akar = document.querySelector('[data-jalur-pin]');
const baris = [...akar.querySelectorAll('[data-jalur]')];
const foto = [...akar.querySelectorAll('[data-jalur-foto]')];
const n = baris.length;
let aktif = 0;

function set(i) {
    if (i === aktif) return;
    aktif = i;
    baris.forEach((b, j) => (j === i ? b.setAttribute('aria-current', 'true') : b.removeAttribute('aria-current')));
    foto.forEach((f, j) => f.classList.toggle('aktif', j <= i));
}

function hitung() {
    if (matchMedia('(max-width: 900px)').matches) return;
    const r = akar.getBoundingClientRect();
    const jarak = r.height - innerHeight;
    if (jarak <= 0) return;
    const p = Math.min(1, Math.max(0, -r.top / jarak));
    set(Math.min(n - 1, Math.floor(p * n * 0.999)));
}

addEventListener('scroll', hitung, { passive: true });
addEventListener('resize', hitung);
hitung();

// pada ponsel (tanpa pin), arahkan atau fokus ke baris untuk mengganti fotonya
baris.forEach((b, i) => {
    b.addEventListener('pointerenter', () => matchMedia('(max-width: 900px)').matches || set(i));
    b.addEventListener('focus', () => set(i));
});
