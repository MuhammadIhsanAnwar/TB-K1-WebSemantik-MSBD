// Interaksi dasar MedanVisit: menu ponsel, pencarian cepat + saran langsung,
// asisten melayang, lightbox foto, saringan beranda, sandi, dan kemunculan bertahap.

const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

// ---- Menu navigasi ponsel ----
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

// ---- Menu samping panel Mitra/Kurator di ponsel ----
const samping = document.querySelector('.ps__samping');
document.querySelectorAll('[data-buka-samping]').forEach((b) => b.addEventListener('click', () => {
    samping?.classList.add('buka');
    b.setAttribute('aria-expanded', 'true');
    samping?.querySelector('a, button')?.focus();
}));
document.querySelectorAll('[data-tutup-samping]').forEach((b) => b.addEventListener('click', () => {
    samping?.classList.remove('buka');
    document.querySelector('[data-buka-samping]')?.setAttribute('aria-expanded', 'false');
}));

// ---- Dropdown akun wisatawan ----
const akunMenu = document.querySelector('[data-akun-menu]');
document.addEventListener('click', (e) => {
    if (akunMenu?.open && !akunMenu.contains(e.target)) akunMenu.open = false;
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (samping?.classList.contains('buka')) samping.classList.remove('buka');
        if (akunMenu?.open) akunMenu.open = false;
    }
});

// ---- Tutup pesan flash ----
document.querySelectorAll('[data-tutup-pesan]').forEach((b) => {
    b.addEventListener('click', () => b.closest('[role="status"], [role="alert"]')?.remove());
});

// ---- Dialog pencarian cepat ----
const dialogCari = document.querySelector('[data-dialog-cari]');
const saranUrl = dialogCari?.dataset.saranUrl || '/cari/saran.json';

document.querySelectorAll('[data-buka-cari]').forEach((b) => {
    b.addEventListener('click', () => {
        if (!dialogCari?.showModal) return;
        dialogCari.showModal();
        setTimeout(() => dialogCari.querySelector('input[type="search"]')?.focus(), 40);
    });
});
dialogCari?.querySelector('[data-tutup-cari]')?.addEventListener('click', () => dialogCari.close());
dialogCari?.addEventListener('click', (e) => {
    if (e.target === dialogCari) dialogCari.close();
});
document.addEventListener('keydown', (e) => {
    if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        const tag = document.activeElement?.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || document.activeElement?.isContentEditable) return;
        if (dialogCari?.showModal && !dialogCari.open) {
            e.preventDefault();
            tampilkanRiwayat();
            dialogCari.showModal();
            dialogCari.querySelector('input[type="search"]')?.focus();
        }
    }
});

// ---- Riwayat "terakhir dibuka" di dialog pencarian (hanya di peramban ini) ----
const KUNCI_RIWAYAT = 'mv-cari-terakhir';
const bacaRiwayat = () => {
    try { return JSON.parse(localStorage.getItem(KUNCI_RIWAYAT) || '[]'); } catch { return []; }
};
const catatRiwayat = (butir) => {
    try {
        const baru = [butir, ...bacaRiwayat().filter((r) => r.url !== butir.url)].slice(0, 5);
        localStorage.setItem(KUNCI_RIWAYAT, JSON.stringify(baru));
    } catch { /* penyimpanan peramban tidak tersedia: riwayat dilewati */ }
};
function tampilkanRiwayat() {
    const blok = dialogCari?.querySelector('[data-cari-terakhir]');
    if (!blok) return;
    const riwayat = bacaRiwayat();
    blok.hidden = riwayat.length === 0;
    blok.querySelector('ul').innerHTML = riwayat.map((r) => `<li><a href="${esc(r.url)}">${esc(r.nama)}</a><span>${esc(r.jenis)}</span></li>`).join('');
}
document.querySelectorAll('[data-buka-cari]').forEach((b) => b.addEventListener('click', tampilkanRiwayat));
dialogCari?.addEventListener('click', (e) => {
    const a = e.target.closest('[data-cari-terakhir] a');
    if (a) catatRiwayat({ nama: a.textContent, url: a.getAttribute('href'), jenis: a.nextElementSibling?.textContent ?? '' });
});

// ---- Saran langsung toleran salah ketik, bisa dipilih dengan panah keyboard (pola combobox) ----
document.querySelectorAll('[data-cari-cepat]').forEach((form, nomor) => {
    const input = form.querySelector('input[type="search"]');
    const kotak = form.querySelector('[data-saran-hasil]');
    if (!input || !kotak) return;
    const idKotak = `saran-${nomor}`;
    kotak.id = idKotak;
    kotak.setAttribute('role', 'listbox');
    kotak.setAttribute('aria-label', 'Saran pencarian');
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', idKotak);
    input.setAttribute('aria-expanded', 'false');

    const tombolJenis = [...form.querySelectorAll('[data-jenis-cari]')];
    let jenis = '';
    let tunda = null;
    let aktif = -1;

    const pilihan = () => [...kotak.querySelectorAll('[role="option"]')];
    const sembunyi = () => {
        kotak.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        aktif = -1;
    };
    const tandai = (i) => {
        const daftar = pilihan();
        if (!daftar.length) return;
        aktif = (i + daftar.length) % daftar.length;
        daftar.forEach((o, n) => o.setAttribute('aria-selected', n === aktif ? 'true' : 'false'));
        input.setAttribute('aria-activedescendant', daftar[aktif].id);
        daftar[aktif].scrollIntoView({ block: 'nearest' });
    };

    input.addEventListener('input', () => {
        clearTimeout(tunda);
        const q = input.value.trim();
        if (q.length < 2) {
            sembunyi();
            return;
        }
        tunda = setTimeout(async () => {
            try {
                const res = await fetch(`${saranUrl}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                const hasil = data.saran ?? data.hasil ?? [];
                const urlSemua = data.semua || `/cari?q=${encodeURIComponent(q)}`;
                const urlTanya = data.tanya || `/tanya?q=${encodeURIComponent(q)}`;
                const butir = hasil.map((h) => {
                    const sub = [h.ringkas ?? h.sub, h.harga].filter(Boolean).join(', ');
                    const jenis = h.label_jenis ?? h.jenis ?? '';
                    return `
                    <a class="mv-saran-butir" href="${esc(h.url)}">
                        <span><b>${esc(h.nama)}</b><span>${esc(sub)}</span></span>
                        <span class="mv-saran-butir__jenis">${esc(jenis)}</span>
                    </a>`;
                }).join('');
                const kosong = `<div style="padding:12px 14px;font-size:1rem;color:var(--mv-redup)">Tidak ada nama persis untuk “${esc(q)}”. Tekan Cari atau tanyakan ke asisten.</div>`;
                kotak.innerHTML = (butir || kosong) + `
                    <div class="mv-saran-kaki">
                        <a href="${esc(urlSemua)}">Semua hasil “${esc(q)}”</a>
                        <a href="${esc(urlTanya)}">Tanyakan ke asisten</a>
                    </div>`;
                kotak.hidden = false;
            } catch {
                sembunyi();
            }
        }, 180);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') sembunyi();
    });
    document.addEventListener('click', (e) => {
        if (!form.contains(e.target)) sembunyi();
    });
});

// ---- Widget Asisten Melayang ----
const widget = document.querySelector('[data-asisten-widget]');
if (widget) {
    const pemicu = widget.querySelector('[data-buka-asisten]');
    const panel = widget.querySelector('.mv-asisten-panel');
    const tutup = widget.querySelector('[data-tutup-asisten]');
    const log = widget.querySelector('[data-widget-log]');
    const form = widget.querySelector('[data-widget-form]');
    const input = form?.querySelector('textarea');
    const kirim = form?.querySelector('button[type="submit"]');
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    const aturBuka = (status) => {
        panel.hidden = !status;
        pemicu.hidden = status;
        pemicu.setAttribute('aria-expanded', status ? 'true' : 'false');
        if (status) setTimeout(() => input?.focus(), 40);
        else pemicu.focus();
    };

    pemicu?.addEventListener('click', () => aturBuka(true));
    tutup?.addEventListener('click', () => aturBuka(false));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) aturBuka(false);
    });

    const kartuMini = (t) => {
        const foto = t.foto ? `<img src="${esc(t.foto)}" alt="" width="72" height="56" loading="lazy">` : '';
        const sub = [t.jenis, t.harga].filter(Boolean).join(', ');
        const aksi = [t.url ? `<a href="${esc(t.url)}">Lihat tempat</a>` : '', t.tiket ? `<a href="${esc(t.tiket)}">Pesan tiket</a>` : ''].filter(Boolean).join('');
        return `<div><a class="kartu-mini" href="${esc(t.url ?? '#')}">${foto}<span><b>${esc(t.nama)}</b><span>${esc(sub)}</span></span></a>${aksi ? `<div class="aksi-mini">${aksi}</div>` : ''}</div>`;
    };

    const tambahPesan = (html, kelas) => {
        const el = document.createElement('div');
        el.className = `mv-gelembung ${kelas}`;
        el.innerHTML = html;
        log.append(el);
        log.scrollTop = log.scrollHeight;
        return el;
    };

    const kirimPesan = async (pesan) => {
        tambahPesan(`<p>${esc(pesan)}</p>`, 'mv-gelembung--aku');
        widget.querySelector('[data-widget-saran]')?.remove();
        const ketik = tambahPesan('<p>Menyusun jawaban…</p>', 'mv-gelembung--asisten');
        if (kirim) kirim.disabled = true;
        try {
            const res = await fetch(widget.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ pesan }),
            });
            if (res.status === 429) throw new Error('batas');
            if (!res.ok) throw new Error('galat');
            const j = await res.json();
            ketik.remove();
            const isi = (j.teks ?? []).map((p) => `<p>${esc(p)}</p>`).join('')
                + (j.tempat ?? []).map(kartuMini).join('')
                + (j.tautan ? `<div class="aksi-mini"><a href="${esc(j.tautan.url)}">${esc(j.tautan.label)}</a></div>` : '');
            tambahPesan(isi || '<p>Belum ada data yang cocok di katalog.</p>', 'mv-gelembung--asisten');
        } catch (err) {
            ketik.remove();
            const teks = err.message === 'batas' ? 'Terlalu banyak pertanyaan sekaligus. Tunggu sebentar lalu coba lagi.' : 'Jawaban belum bisa dimuat. Periksa sambunganmu.';
            const gagal = tambahPesan(`<p>${teks}</p><div class="aksi-mini"><a href="#" data-coba-lagi>Coba lagi</a></div>`, 'mv-gelembung--asisten');
            gagal.querySelector('[data-coba-lagi]')?.addEventListener('click', (ev) => {
                ev.preventDefault();
                gagal.remove();
                kirimPesan(pesan);
            });
        } finally {
            if (kirim) kirim.disabled = false;
            input?.focus();
        }
    };

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        const v = input.value.trim();
        if (!v) return;
        input.value = '';
        kirimPesan(v);
    });
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });
    widget.querySelectorAll('[data-widget-saran] button').forEach((b) => {
        b.addEventListener('click', () => kirimPesan(b.textContent.trim()));
    });
}

// ---- Lightbox Foto ----
const lightbox = document.querySelector('[data-lightbox]');
if (lightbox) {
    const gbr = lightbox.querySelector('[data-lightbox-gambar]');
    const jdl = lightbox.querySelector('[data-lightbox-judul]');
    const krd = lightbox.querySelector('[data-lightbox-kredit]');
    document.querySelectorAll('[data-buka-lightbox]').forEach((b) => {
        b.addEventListener('click', () => {
            if (!lightbox.showModal) return;
            gbr.src = b.dataset.src;
            gbr.alt = b.dataset.judul || '';
            jdl.textContent = b.dataset.judul || '';
            krd.textContent = b.dataset.kredit || '';
            lightbox.showModal();
        });
    });
    lightbox.querySelector('[data-tutup-lightbox]')?.addEventListener('click', () => lightbox.close());
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) lightbox.close();
    });
}

// ---- Hero Beranda Crossfade (dengan tombol Jeda untuk aksesibilitas) ----
const hero = document.querySelector('[data-hero-crossfade]');
if (hero) {
    const slides = [...hero.querySelectorAll('[data-hero-slide]')];
    const titik = [...hero.querySelectorAll('[data-hero-ke]')];
    const jedaBtn = hero.querySelector('[data-hero-jeda]');
    const labelNama = hero.querySelector('[data-hero-nama]');
    const labelSub = hero.querySelector('[data-hero-sub]');
    let idx = 0;
    let dijeda = matchMedia('(prefers-reduced-motion: reduce)').matches;

    const tampil = (n) => {
        idx = (n + slides.length) % slides.length;
        slides.forEach((s, i) => s.classList.toggle('aktif', i === idx));
        titik.forEach((t, i) => t.setAttribute('aria-current', i === idx ? 'true' : 'false'));
        const aktif = slides[idx];
        if (labelNama && aktif?.dataset.nama) labelNama.textContent = aktif.dataset.nama;
        if (labelSub && aktif?.dataset.sub) labelSub.textContent = aktif.dataset.sub;
    };

    titik.forEach((t, i) => t.addEventListener('click', () => tampil(i)));
    const interval = setInterval(() => {
        if (!dijeda && document.visibilityState === 'visible') tampil(idx + 1);
    }, 6000);

    jedaBtn?.addEventListener('click', () => {
        dijeda = !dijeda;
        jedaBtn.setAttribute('aria-pressed', dijeda ? 'true' : 'false');
        jedaBtn.textContent = dijeda ? 'Putar' : 'Jeda';
        if (dijeda) clearInterval(interval);
    });
}

// ---- Saringan Instan Tempat di Beranda ----
const saringBeranda = document.querySelector('[data-saring-beranda]');
if (saringBeranda) {
    const grid = document.querySelector('[data-grid-beranda]');
    const kartu = [...(grid?.querySelectorAll('[data-kategori]') ?? [])];
    const tombol = [...saringBeranda.querySelectorAll('button[data-filter]')];
    tombol.forEach((b) => {
        b.addEventListener('click', () => {
            const f = b.dataset.filter;
            tombol.forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
            grid?.classList.toggle('terfilter', f !== 'semua');
            kartu.forEach((k) => {
                k.hidden = f !== 'semua' && k.dataset.kategori !== f;
            });
        });
    });
}

// ---- Tampilkan/Sembunyikan Sandi & Indikator Kekuatan ----
document.querySelectorAll('[data-toggle-sandi]').forEach((b) => {
    b.addEventListener('click', () => {
        const inp = document.getElementById(b.dataset.toggleSandi);
        if (!inp) return;
        const tampil = inp.type === 'password';
        inp.type = tampil ? 'text' : 'password';
        b.textContent = tampil ? 'Sembunyikan' : 'Tampilkan';
        b.setAttribute('aria-pressed', tampil ? 'true' : 'false');
    });
});
document.querySelectorAll('[data-kekuatan-sandi]').forEach((wadah) => {
    const inp = document.getElementById(wadah.dataset.kekuatanSandi);
    const bar = wadah.querySelector('[data-bar-sandi]');
    const teks = wadah.querySelector('[data-teks-sandi]');
    if (!inp || !bar || !teks) return;
    inp.addEventListener('input', () => {
        const v = inp.value;
        let skor = 0;
        if (v.length >= 8) skor += 1;
        if (v.length >= 12) skor += 1;
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) skor += 1;
        if (/\d|[^A-Za-z0-9]/.test(v)) skor += 1;
        const peta = [
            ['0%', '#a3221b', 'Minimal 8 karakter'],
            ['35%', '#b26b00', 'Cukup: tambah huruf besar atau angka'],
            ['65%', '#1e6b3a', 'Kuat'],
            ['85%', '#0f3b2e', 'Kuat'],
            ['100%', '#0f3b2e', 'Sangat kuat'],
        ][v.length === 0 ? 0 : Math.max(1, skor)];
        bar.style.width = v.length < 8 && v.length > 0 ? '20%' : peta[0];
        bar.style.backgroundColor = v.length < 8 && v.length > 0 ? '#a3221b' : peta[1];
        teks.textContent = v.length > 0 && v.length < 8 ? `Kurang ${8 - v.length} karakter lagi` : peta[2];
    });
});

// ---- Kemunculan bertahap saat masuk layar ----
const tandaiOtomatis = [
    'main .jalur-indeks > a > *', 'main .lini > li', 'main .daftar-kredit > li > *',
    'main .tempat-populer > a > *', 'main .strip-foto > a', 'main .rekomendasi > a',
    'main .kepala-halaman > *', 'main .detail__kepala > *', 'main .detail__seksi > *',
    'main .tiket-blok', 'main .isi-halaman > *',
    '.mv-kartu-tempat', '.mv-baris-tiket', '.mv-jalur-item', '.mv-paspor-butir',
    '.mv-faq-daftar details', '.mv-seksi-detail > *', '.mv-kepala-seksi > *',
    '.mv-tempat-kepala__teks > *', '.mv-tempat-media', '.mv-kartu-samping > *',
].join(', ');

document.querySelectorAll(tandaiOtomatis).forEach((el) => {
    if (el.closest('[data-muncul]') || el.querySelector('[data-muncul]')) return;
    el.setAttribute('data-muncul', '');
    const saudara = el.parentElement ? [...el.parentElement.children].indexOf(el) : 0;
    el.style.setProperty('--i', String(Math.min(5, Math.max(0, saudara % 6))));
});

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
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });
        target.forEach((el) => io.observe(el));
        window.addEventListener('load', () => setTimeout(() => {
            target.forEach((el) => {
                const r = el.getBoundingClientRect();
                if (r.top < innerHeight && r.bottom > 0) el.classList.add('masuk');
            });
        }, 900));
    }
}

// ---- Simpan PDF (cetak) dan salin teks ----
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

document.documentElement.classList.add('siap');
