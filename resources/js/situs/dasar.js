// Menu ponsel, pesan yang bisa ditutup, dan kemunculan bagian saat masuk layar.

const menu = document.getElementById('lembar-menu');
document.querySelectorAll('[data-buka-menu]').forEach((b) => {
    b.addEventListener('click', () => {
        if (menu?.showModal) menu.showModal();
        b.setAttribute('aria-expanded', 'true');
    });
});
menu?.querySelector('[data-tutup-menu]')?.addEventListener('click', () => menu.close());
menu?.addEventListener('close', () => document.querySelectorAll('[data-buka-menu]').forEach((b) => b.setAttribute('aria-expanded', 'false')));
menu?.addEventListener('click', (e) => {
    if (e.target === menu) menu.close();
});

// Menu samping panel di ponsel, dipasang di bundel dasar supaya tombolnya langsung berfungsi.
const samping = document.querySelector('.ps__samping');
document.querySelectorAll('[data-buka-samping]').forEach((b) => b.addEventListener('click', () => {
    samping.classList.add('buka');
    b.setAttribute('aria-expanded', 'true');
    samping.querySelector('a, button')?.focus();
}));
document.querySelectorAll('[data-tutup-samping]').forEach((b) => b.addEventListener('click', () => {
    samping.classList.remove('buka');
    document.querySelector('[data-buka-samping]')?.setAttribute('aria-expanded', 'false');
}));
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && samping?.classList.contains('buka')) samping.classList.remove('buka');
});

document.querySelectorAll('[data-tutup-pesan]').forEach((b) => b.addEventListener('click', () => b.closest('[role="status"], [role="alert"]')?.remove()));

// Halaman konten (bukan layar alat): bagian tiap seksi muncul berurutan, judul lalu teks lalu gambar,
// selang 80 ms, supaya mata mengikuti urutan baca. Atribut dipasang skrip, jadi tanpa skrip semuanya tampil.
if (document.querySelector('main .detail, main .kepala-halaman, main .jalur-detail')) {
    // Butir daftar ditandai lebih dulu, supaya wadah daftarnya tidak ikut ditandai dan isinya muncul satu per satu.
    const butir = 'main .jalur-indeks > a > *, main .lini > li, main .daftar-kredit > li > *, main .tempat-populer > a > *, main .strip-foto > a, main .rekomendasi > a';
    const blok = 'main .kepala-halaman > *, main .detail__kepala > *, main .detail__seksi > *, main .tiket-blok, main .isi-halaman > *';
    const tandai = (el) => {
        if (el.closest('[data-muncul]') || el.querySelector('[data-muncul]')) return;
        el.setAttribute('data-muncul', '');
        el.style.setProperty('--i', String(Math.min(5, [...el.parentElement.children].indexOf(el))));
    };
    document.querySelectorAll(butir).forEach(tandai);
    document.querySelectorAll(blok).forEach(tandai);
}

// Kemunculan: dipicu sekali, tidak pernah menyembunyikan isi bila skrip gagal (kelas .js dipasang di <head>).
const target = document.querySelectorAll('[data-muncul]');
if (target.length) {
    if (!('IntersectionObserver' in window)) {
        target.forEach((el) => el.classList.add('masuk'));
    } else {
        const io = new IntersectionObserver((entri) => {
            for (const e of entri) {
                if (!e.isIntersecting) continue;
                e.target.classList.add('masuk');
                io.unobserve(e.target);
            }
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.08 });
        target.forEach((el) => io.observe(el));
        // jaring pengaman: apa pun yang sudah di layar tetap muncul
        window.addEventListener('load', () => setTimeout(() => {
            target.forEach((el) => {
                const r = el.getBoundingClientRect();
                if (r.top < innerHeight && r.bottom > 0) el.classList.add('masuk');
            });
        }, 1500));
    }
}

// Simpan PDF (cetak) dan salin tautan dipakai di beberapa halaman.
document.querySelectorAll('[data-cetak]').forEach((b) => b.addEventListener('click', () => window.print()));
document.querySelectorAll('[data-salin]').forEach((b) => b.addEventListener('click', async () => {
    try {
        await navigator.clipboard.writeText(b.dataset.salin);
        const awal = b.textContent;
        b.textContent = 'Tersalin';
        setTimeout(() => (b.textContent = awal), 1600);
    } catch {
        b.textContent = 'Salin manual';
    }
}));

// Kursor tangan untuk kontrol (R-39), sekali di lapisan dasar.
document.documentElement.classList.add('siap');
