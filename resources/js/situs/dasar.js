// Interaksi dasar MedanVisit: menu ponsel, pencarian cepat + saran langsung,
// asisten melayang, lightbox foto, saringan beranda, sandi, dan kemunculan bertahap.

const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

// ---- Pemuatan beranda pada setiap kunjungan ----
const pemuatan = document.querySelector('[data-pemuatan-beranda]');
if (pemuatan) {
    const mulai = performance.now();
    const status = pemuatan.querySelector('[data-pemuatan-status]');
    const bar = pemuatan.querySelector('[data-pemuatan-bar]');
    const persen = pemuatan.querySelector('[data-pemuatan-persen]');
    const progressbar = pemuatan.querySelector('[role="progressbar"]');
    const gambar = [...document.images].filter((img) => img !== pemuatan.querySelector('img'));
    const total = Math.max(1, gambar.length + 1);
    let selesai = gambar.filter((img) => img.complete).length;
    let target = Math.min(96, Math.round((selesai / total) * 100));
    let nilai = 0;
    const koneksi = navigator.connection;
    const cepat = typeof koneksi?.downlink === 'number' && koneksi.downlink >= 5;
    const teks = [
        [0, 'Menyiapkan beranda…'],
        [35, 'Memuat cerita Medan…'],
        [70, cepat ? 'Menghubungkan fitur wisata…' : 'Menyesuaikan dengan jaringanmu…'],
        [96, 'Beranda siap ditampilkan.'],
    ];
    const perbarui = (nilaiBaru) => {
        nilai = Math.max(nilai, Math.min(100, nilaiBaru));
        bar.style.width = `${nilai}%`;
        persen.textContent = `${Math.round(nilai)}%`;
        progressbar.setAttribute('aria-valuenow', String(Math.round(nilai)));
        status.textContent = [...teks].reverse().find(([ambang]) => nilai >= ambang)?.[1] ?? teks[0][1];
    };
    const naikkan = () => {
        target = Math.min(96, target + (100 - target > 20 ? 8 : 2));
        perbarui(target);
    };
    gambar.forEach((img) => {
        if (!img.complete) {
            img.addEventListener('load', naikkan, { once: true });
            img.addEventListener('error', naikkan, { once: true });
        }
    });
    document.fonts?.ready.then(naikkan).catch(() => {});
    const selesaikanPemuatan = () => {
        perbarui(100);
        window.setTimeout(() => {
            pemuatan.classList.add('mv-pemuatan--selesai');
            window.setTimeout(() => pemuatan.remove(), 420);
        }, Math.max(500 - (performance.now() - mulai), 0));
    };
    if (document.readyState === 'complete') {
        selesaikanPemuatan();
    } else {
        window.addEventListener('load', selesaikanPemuatan, { once: true });
    }
    perbarui(target);
} else {
    pemuatan?.remove();
}

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

    async function muat() {
        const q = input.value.trim();
        if (q.length < 2) {
            sembunyi();
            return;
        }
        kotak.innerHTML = '<div class="mv-saran-status" role="status">Mencari…</div>';
        kotak.hidden = false;
        try {
            const res = await fetch(`${saranUrl}?q=${encodeURIComponent(q)}${jenis ? `&jenis=${jenis}` : ''}`, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error(String(res.status));
            const data = await res.json();
            if (input.value.trim() !== q) return; // sudah diketik ulang, hasil ini usang
            const hasil = data.saran ?? data.hasil ?? [];
            const urlSemua = data.semua || `/cari?q=${encodeURIComponent(q)}`;
            const urlTanya = data.tanya || `/tanya?q=${encodeURIComponent(q)}`;
            const butir = hasil.map((h, i) => {
                const sub = [h.ringkas ?? h.sub, h.harga].filter(Boolean).join(', ');
                const label = h.label_jenis ?? h.jenis ?? '';
                return `
                <a class="mv-saran-butir" id="${idKotak}-${i}" role="option" aria-selected="false" href="${esc(h.url)}" data-nama="${esc(h.nama)}" data-jenis="${esc(label)}">
                    <span><b>${esc(h.nama)}</b><span>${esc(sub)}</span></span>
                    <span class="mv-saran-butir__jenis">${esc(label)}</span>
                </a>`;
            }).join('');
            const kosong = `<div class="mv-saran-status" role="status">Tidak ada nama persis untuk “${esc(q)}”${jenis ? ' di jenis ini' : ''}. Tekan Cari atau tanyakan ke asisten.</div>`;
            kotak.innerHTML = (butir || kosong) + `
                <div class="mv-saran-kaki">
                    <a href="${esc(urlSemua)}">Semua hasil “${esc(q)}”</a>
                    <a href="${esc(urlTanya)}">Tanyakan ke asisten</a>
                </div>`;
            aktif = -1;
            input.setAttribute('aria-expanded', 'true');
        } catch {
            kotak.innerHTML = '<div class="mv-saran-status" role="status">Saran tidak bisa dimuat. Tekan Cari untuk tetap mencari.</div>';
        }
    }

    input.addEventListener('input', () => {
        clearTimeout(tunda);
        tunda = setTimeout(muat, 180);
    });

    tombolJenis.forEach((b) => b.addEventListener('click', () => {
        jenis = b.dataset.jenisCari;
        tombolJenis.forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
        muat();
        input.focus();
    }));

    input.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (kotak.hidden || !pilihan().length) return;
            e.preventDefault();
            tandai(aktif + (e.key === 'ArrowDown' ? 1 : -1));
        } else if (e.key === 'Enter' && aktif > -1) {
            e.preventDefault();
            const o = pilihan()[aktif];
            catatRiwayat({ nama: o.dataset.nama, url: o.getAttribute('href'), jenis: o.dataset.jenis });
            window.location.href = o.href;
        } else if (e.key === 'Escape' && !kotak.hidden) {
            e.preventDefault(); // Esc pertama menutup saran, Esc kedua menutup dialog
            sembunyi();
        }
    });

    kotak.addEventListener('click', (e) => {
        const o = e.target.closest('[role="option"]');
        if (o) catatRiwayat({ nama: o.dataset.nama, url: o.getAttribute('href'), jenis: o.dataset.jenis });
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

    // Maskot interaktif asisten
    const maskot = {
        menyapa: widget.dataset.maskotMenyapa || '/images/maskot/mascot-menyapa.png',
        mendengarkan: widget.dataset.maskotMendengarkan || '/images/maskot/mascot-mendengarkan.png',
        berpikir: widget.dataset.maskotBerpikir || '/images/maskot/mascot-berpikir.png',
        membantu: widget.dataset.maskotMembantu || '/images/maskot/mascot-membantu.png',
        bingung: widget.dataset.maskotBingung || '/images/maskot/mascot-bingung.png',
        sukses: widget.dataset.maskotSukses || '/images/maskot/mascot-sukses.png',
    };

    const maskotPanelImg = panel?.querySelector('[data-panel-maskot]');
    const maskotSampingImg = panel?.querySelector('[data-maskot-samping]');
    const statusPanelEl = panel?.querySelector('[data-panel-status]');
    const maskotPemicuImg = pemicu?.querySelector('[data-pemicu-maskot]');
    let timerEkspresi = null;

    const aturEkspresi = (tipe, statusTeks = '') => {
        const url = maskot[tipe];
        if (!url) return;

        if (maskotPanelImg && maskotPanelImg.dataset.ekspresi !== tipe) {
            maskotPanelImg.dataset.ekspresi = tipe;
            maskotPanelImg.classList.add('mv-maskot--ganti');
            setTimeout(() => {
                maskotPanelImg.src = url;
                maskotPanelImg.classList.remove('mv-maskot--ganti');
            }, 120);
        }

        if (maskotSampingImg && maskotSampingImg.dataset.ekspresi !== tipe) {
            maskotSampingImg.dataset.ekspresi = tipe;
            maskotSampingImg.classList.add('mv-maskot--ganti');
            setTimeout(() => {
                maskotSampingImg.src = url;
                maskotSampingImg.classList.remove('mv-maskot--ganti');
            }, 120);
        }

        if (maskotPemicuImg && (tipe === 'menyapa' || tipe === 'sukses')) {
            maskotPemicuImg.src = url;
        }

        if (statusPanelEl && statusTeks) {
            statusPanelEl.textContent = statusTeks;
        }
    };

    const maskotAtasEl = pemicu?.querySelector('.mv-asisten-widget__maskot-atas');

    const terbangKePanel = () => {
        const rectAwal = maskotAtasEl?.getBoundingClientRect();

        // Buka panel terlebih dahulu agar target bounding rect dapat dihitung
        panel.hidden = false;
        panel.classList.add('mv-panel--muncul');
        pemicu.setAttribute('aria-expanded', 'true');

        if (maskotSampingImg) {
            maskotSampingImg.style.visibility = 'hidden';
            maskotSampingImg.classList.remove('mv-maskot-samping--muncul');
        }

        const rectTujuan = maskotSampingImg?.getBoundingClientRect();

        // Jika rect tersedia dan animasi didukung
        if (rectAwal && rectTujuan && rectAwal.width > 0 && typeof maskotAtasEl?.animate === 'function') {
            const startX = rectAwal.left + rectAwal.width / 2;
            const startY = rectAwal.top + rectAwal.height / 2;
            const targetX = rectTujuan.left + rectTujuan.width / 2;
            const targetY = rectTujuan.top + rectTujuan.height / 2;
            const deltaX = targetX - startX;
            const deltaY = targetY - startY;

            // 1. Maskot di atas tombol meletup menjadi kepulan asap
            maskotAtasEl.classList.add('mv-maskot-atas--puff');

            // 2. Buat elemen gumpalan asap terbang
            const asap = document.createElement('div');
            asap.className = 'mv-asap-terbang';
            asap.style.left = `${startX - 32}px`;
            asap.style.top = `${startY - 32}px`;
            asap.innerHTML = `
                <div class="mv-asap-blob mv-asap-blob--1"></div>
                <div class="mv-asap-blob mv-asap-blob--2"></div>
                <div class="mv-asap-blob mv-asap-blob--3"></div>
            `;
            document.body.appendChild(asap);

            // Jejak partikel asap lembut di sepanjang lintasan terbang
            const timerJejak = setInterval(() => {
                const rectAsap = asap.getBoundingClientRect();
                const jejak = document.createElement('div');
                jejak.className = 'mv-asap-jejak';
                const s = 22 + Math.random() * 18;
                jejak.style.width = `${s}px`;
                jejak.style.height = `${s}px`;
                jejak.style.left = `${rectAsap.left + 16}px`;
                jejak.style.top = `${rectAsap.top + 16}px`;
                document.body.appendChild(jejak);
                setTimeout(() => jejak.remove(), 500);
            }, 60);

            // Animasi terbang melengkung ke kiri atas menuju samping chatbox
            const anim = asap.animate([
                { transform: 'translate(0px, 0px) scale(0.7) rotate(0deg)', opacity: 0.95 },
                { transform: `translate(${deltaX * 0.3}px, ${deltaY * 0.18 - 30}px) scale(1.3) rotate(120deg)`, opacity: 0.9, offset: 0.3 },
                { transform: `translate(${deltaX * 0.7}px, ${deltaY * 0.6 - 45}px) scale(1.15) rotate(260deg)`, opacity: 0.85, offset: 0.7 },
                { transform: `translate(${deltaX}px, ${deltaY}px) scale(1.35) rotate(380deg)`, opacity: 0.95, offset: 0.92 },
                { transform: `translate(${deltaX}px, ${deltaY}px) scale(2) rotate(420deg)`, opacity: 0, offset: 1 }
            ], {
                duration: 620,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                fill: 'forwards'
            });

            anim.onfinish = () => {
                clearInterval(timerJejak);
                asap.remove();
                pemicu.hidden = true;
                maskotAtasEl.classList.remove('mv-maskot-atas--puff');

                // Mas Nonov muncul dari asap di sebelah samping chatbox!
                if (maskotSampingImg) {
                    maskotSampingImg.style.visibility = '';
                    maskotSampingImg.classList.add('mv-maskot-samping--muncul');
                    aturEkspresi('menyapa', 'Mas Nonov siap membantumu');
                }
                setTimeout(() => input?.focus(), 60);
            };
        } else {
            // Fallback langsung
            pemicu.hidden = true;
            if (maskotSampingImg) {
                maskotSampingImg.style.visibility = '';
                maskotSampingImg.classList.add('mv-maskot-samping--muncul');
            }
            aturEkspresi('menyapa', 'Mas Nonov siap membantumu');
            setTimeout(() => input?.focus(), 40);
        }
    };

    const aturBuka = (status) => {
        if (status) {
            terbangKePanel();
        } else {
            panel.hidden = true;
            panel.classList.remove('mv-panel--muncul');
            pemicu.hidden = false;
            pemicu.setAttribute('aria-expanded', 'false');
            maskotAtasEl?.classList.remove('mv-maskot-atas--puff');
            if (maskotSampingImg) {
                maskotSampingImg.classList.remove('mv-maskot-samping--muncul');
            }
            pemicu.focus();
        }
    };

    pemicu?.addEventListener('click', () => aturBuka(true));
    tutup?.addEventListener('click', () => aturBuka(false));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) aturBuka(false);
    });

    // Deteksi saat pengguna mengetik pertanyaan -> ekspresi mendengarkan
    input?.addEventListener('input', () => {
        const v = input.value.trim();
        clearTimeout(timerEkspresi);
        if (v.length > 0) {
            aturEkspresi('mendengarkan', 'Mas Nonov sedang menyimak…');
            timerEkspresi = setTimeout(() => {
                aturEkspresi('menyapa', 'Asisten Wisata MedanVisit');
            }, 4500);
        } else {
            aturEkspresi('menyapa', 'Asisten Wisata MedanVisit');
        }
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
        
        clearTimeout(timerEkspresi);
        aturEkspresi('berpikir', 'Mas Nonov sedang mencari jawaban…');

        const ketik = tambahPesan(
            `<div class="mv-mengetik-maskot">` +
            `<img src="${esc(maskot.berpikir)}" alt="Mas Nonov berpikir" width="28" height="28" class="mv-mini-maskot">` +
            `<span>Mas Nonov menyusun jawaban…</span>` +
            `</div>`,
            'mv-gelembung--asisten'
        );
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

            const adaTempat = Array.isArray(j.tempat) && j.tempat.length > 0;
            const isi = (j.teks ?? []).map((p) => `<p>${esc(p)}</p>`).join('')
                + (j.tempat ?? []).map(kartuMini).join('')
                + (j.tautan ? `<div class="aksi-mini"><a href="${esc(j.tautan.url)}">${esc(j.tautan.label)}</a></div>` : '');
            
            tambahPesan(isi || '<p>Belum ada data yang cocok di katalog.</p>', 'mv-gelembung--asisten');

            const teksKosong = (!j.teks || j.teks.length === 0) && !adaTempat;
            if (teksKosong) {
                aturEkspresi('bingung', 'Mas Nonov belum menemukan datanya');
            } else if (adaTempat) {
                aturEkspresi('membantu', 'Mas Nonov punya rekomendasi wisata!');
            } else {
                aturEkspresi('sukses', 'Jawaban siap dari Mas Nonov!');
            }

            clearTimeout(timerEkspresi);
            timerEkspresi = setTimeout(() => {
                aturEkspresi('menyapa', 'Tanyakan apa saja ke Mas Nonov!');
            }, 6500);

        } catch (err) {
            ketik.remove();
            aturEkspresi('bingung', 'Maaf, terjadi kendala koneksi');
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

// ---- Hero Beranda Crossfade ----
const hero = document.querySelector('[data-hero-crossfade]');
if (hero) {
    const slides = [...hero.querySelectorAll('[data-hero-slide]')];
    const titik = [...hero.querySelectorAll('[data-hero-ke]')];
    const labelNama = hero.querySelector('[data-hero-nama]');
    const labelSub = hero.querySelector('[data-hero-sub]');
    let idx = 0;
    const gerakDikurangi = matchMedia('(prefers-reduced-motion: reduce)').matches;

    const tampil = (n) => {
        idx = (n + slides.length) % slides.length;
        slides.forEach((s, i) => s.classList.toggle('aktif', i === idx));
        titik.forEach((t, i) => t.setAttribute('aria-current', i === idx ? 'true' : 'false'));
        const aktif = slides[idx];
        if (labelNama && aktif?.dataset.nama) labelNama.textContent = aktif.dataset.nama;
        if (labelSub && aktif?.dataset.sub) labelSub.textContent = aktif.dataset.sub;
    };

    titik.forEach((t, i) => t.addEventListener('click', () => tampil(i)));
    if (!gerakDikurangi) {
        window.setInterval(() => {
            if (document.visibilityState === 'visible') tampil(idx + 1);
        }, 6000);
    }
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
        b.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
        b.innerHTML = tampil
            ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-3.2 3.8M6.2 6.2C3.9 7.7 2.5 12 2.5 12s3.5 6 9.5 6c1.1 0 2.1-.2 3-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>'
            : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>';
        b.setAttribute('aria-pressed', tampil ? 'true' : 'false');
    });
});
document.querySelectorAll('[data-kekuatan-sandi]').forEach((wadah) => {
    const inp = document.getElementById(wadah.dataset.kekuatanSandi);
    const bar = wadah.querySelector('[data-bar-sandi]');
    const teks = wadah.querySelector('[data-teks-sandi]');
    if (!inp || !bar || !teks) return;
    const perbaruiKekuatan = () => {
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
        const status = v.length === 0
            ? 'kosong'
            : v.length < 8
                ? 'lemah'
                : skor < 2
                    ? 'cukup'
                    : skor < 4
                        ? 'kuat'
                        : 'sangat-kuat';
        bar.dataset.status = status;
        bar.style.width = v.length < 8 && v.length > 0 ? '20%' : peta[0];
        teks.textContent = v.length > 0 && v.length < 8 ? `Kurang ${8 - v.length} karakter lagi` : peta[2];
    };
    inp.addEventListener('input', perbaruiKekuatan);
    perbaruiKekuatan();
});

// ---- Kemunculan bertahap saat masuk layar ----
const tandaiOtomatis = [
    'main .jalur-indeks > a > *', 'main .lini > li', 'main .daftar-kredit > li > *',
    'main .tempat-populer > a > *', 'main .strip-foto > a', 'main .rekomendasi > a',
    'main .kepala-halaman > *', 'main .detail__kepala > *', 'main .detail__seksi > *',
    'main .tiket-blok', 'main .isi-halaman > *',
    '.mv-kartu-tempat', '.mv-menu-harga', '.mv-jalur-item', '.mv-paspor-butir',
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
