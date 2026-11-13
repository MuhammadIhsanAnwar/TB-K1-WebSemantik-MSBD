// Peta Leaflet + OpenStreetMap. Mode: "ringkas" (beranda), "alat" (halaman peta, sinkron dengan daftar),
// "rute" (jalur dan rencana, penanda bernomor dan garis rute).
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export const WARNA = { sejarah: '#e3a51c', religi: '#6a4c9c', kuliner: '#c9541e', museum: '#2f6690', 'ruang-publik': '#2e7d4f', belanja: '#9b6b3d', 'rekreasi-air': '#2f8a8a' };
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

function ikon(warna, nomor = null) {
    const isi = nomor
        ? `<text x="14" y="18" text-anchor="middle" font-size="12" font-weight="700" fill="#0f3b2e" font-family="system-ui">${nomor}</text>`
        : '<circle cx="14" cy="13.5" r="4.5" fill="#f6f1e4"/>';
    return L.divIcon({
        className: 'penanda',
        html: `<svg viewBox="0 0 28 36" aria-hidden="true"><path d="M14 35.2 3.6 22.4A13 13 0 1 1 24.4 22.4Z" fill="${warna}" stroke="#0f3b2e" stroke-width="1.6"/>${isi}</svg>`,
        iconSize: [28, 36],
        iconAnchor: [14, 35],
        popupAnchor: [0, -32],
    });
}

function popup(t) {
    const rating = Number(t.rating ?? 0);
    const jumlahUlasan = Number(t.jumlahUlasan ?? 0);
    const jumlahKunjungan = Number(t.jumlahKunjungan ?? 0);

    const infoRating = jumlahUlasan > 0
        ? `★ ${rating.toLocaleString('id', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
        })} (${jumlahUlasan} ulasan)`
        : 'Belum ada rating';

    return `
        <div class="popup-tempat">
            <b>${esc(t.nama)}</b>
            <span>${esc(t.harga ?? '')}</span>
            <div style="margin-top:6px;font-size:0.85rem">
                <div>${infoRating}</div>
                <div>👁 ${jumlahKunjungan.toLocaleString('id')} kunjungan</div>
            </div>
            <div class="aksi">
                <a href="${esc(t.url)}">Lihat tempat</a>
                ${t.tiket ? `<a href="${esc(t.tiket)}">Pesan tiket</a>` : ''}
            </div>
        </div>
    `;
}

function buatPeta(el, opsi = {}) {
    el.querySelector('.peta-diam')?.remove();
    const peta = L.map(el, { scrollWheelZoom: false, zoomControl: true, ...opsi }).setView([3.5826, 98.6790], 15);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; kontributor <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(peta);
    el.addEventListener('focus', () => peta.scrollWheelZoom.enable());
    el.addEventListener('blur', () => peta.scrollWheelZoom.disable());
    return peta;
}

function modeRingkas(el) {
    const tempat = JSON.parse(el.dataset.tempat);
    const peta = buatPeta(el);
    const grup = L.featureGroup(tempat.map((t) => L.marker([t.lat, t.lng], { icon: ikon(WARNA[t.kategori]), title: t.nama, alt: t.nama }).bindPopup(popup(t)))).addTo(peta);
    const dekat = tempat.filter((t) => t.lat > 3.55);
    peta.fitBounds(L.latLngBounds(dekat.map((t) => [t.lat, t.lng])).pad(0.12), { padding: [24, 24] });
    setTimeout(() => peta.invalidateSize(), 200);
    return { peta, grup };
}



async function modeRute(el) {
    const titik = JSON.parse(el.dataset.rute);
    const peta = buatPeta(el);

    const valid = titik.filter((t) =>
        Number.isFinite(Number(t.lat)) &&
        Number.isFinite(Number(t.lng)) &&
        Number(t.lat) >= -90 &&
        Number(t.lat) <= 90 &&
        Number(t.lng) >= -180 &&
        Number(t.lng) <= 180
    );

    const posisi = valid.map((t) => [
        Number(t.lat),
        Number(t.lng),
    ]);

    const penanda = L.featureGroup().addTo(peta);

    valid.forEach((t, i) => {
        L.marker(
            [Number(t.lat), Number(t.lng)],
            {
                icon: ikon('#e3a51c', i + 1),
                title: t.nama,
                alt: `${i + 1}. ${t.nama}`,
            }
        )
        .bindPopup(popup(t))
        .addTo(penanda);
    });

    const info = L.control({ position: 'bottomleft' });

    info.onAdd = () => {
        const div = L.DomUtil.create('div', 'leaflet-control-attribution');
        div.textContent = 'Memuat jalur perjalanan...';
        return div;
    };

    info.addTo(peta);

    if (posisi.length < 2) {
        info.getContainer().textContent = 'Minimal dua tempat diperlukan.';
        if (posisi.length === 1) peta.setView(posisi[0], 16);
        return;
    }

    const garisCadangan = (awal, akhir) =>
        L.polyline([awal, akhir], {
            color: '#777777',
            weight: 4,
            dashArray: '2 8',
            lineCap: 'round',
        }).addTo(peta);

    async function ambilJalur(awal, akhir, berjalan) {
        const controller = new AbortController();
        const batasWaktu = setTimeout(() => controller.abort(), 20000);

        try {
            let response;

            if (berjalan) {
                response = await fetch(
                    'https://valhalla1.openstreetmap.de/route',
                    {
                        method: 'POST',
                        signal: controller.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Client-Id': 'medanvisit.neoverse.my.id',
                        },
                        body: JSON.stringify({
                            locations: [
                                {
                                    lat: awal[0],
                                    lon: awal[1],
                                },
                                {
                                    lat: akhir[0],
                                    lon: akhir[1],
                                },
                            ],
                            costing: 'pedestrian',
                            units: 'kilometers',
                            format: 'osrm',
                            shape_format: 'geojson',
                            directions_type: 'none',
                        }),
                    }
                );
            } else {
                const koordinat = [
                    `${awal[1]},${awal[0]}`,
                    `${akhir[1]},${akhir[0]}`,
                ].join(';');

                const url =
                    `https://router.project-osrm.org/route/v1/driving/${koordinat}?overview=full&geometries=geojson&steps=false`;

                response = await fetch(url, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            const koordinat = data.routes?.[0]?.geometry?.coordinates;

            if (
                (data.code && data.code !== 'Ok') ||
                !koordinat?.length
            ) {
                throw new Error('Jalur tidak ditemukan.');
            }

            return koordinat.map(([lng, lat]) => [lat, lng]);
        } finally {
            clearTimeout(batasWaktu);
        }
    }

    let jumlahJalanKaki = 0;
    let jumlahKendaraan = 0;
    let jumlahGagal = 0;

    for (let i = 1; i < valid.length; i++) {
        const awal = posisi[i - 1];
        const akhir = posisi[i];

        const berjalan =
            String(valid[i].moda ?? '').toLowerCase() === 'jalan kaki';

        try {
            const jalur = await ambilJalur(awal, akhir, berjalan);

            L.polyline(jalur, {
                color: berjalan ? '#23865b' : '#0f3b2e',
                weight: 5,
                opacity: 0.9,
                lineCap: 'round',
                lineJoin: 'round',
            }).addTo(peta);

            if (berjalan) {
                jumlahJalanKaki++;
            } else {
                jumlahKendaraan++;
            }
        } catch (error) {
            console.warn(
                `Rute segmen ${i} tidak tersedia:`,
                error
            );

            garisCadangan(awal, akhir);
            jumlahGagal++;
        }
    }

    const keterangan = [];

    if (jumlahJalanKaki) {
        keterangan.push('Jalan kaki dari Valhalla');
    }

    if (jumlahKendaraan) {
        keterangan.push('Kendaraan dari OSRM');
    }

    if (jumlahGagal) {
        keterangan.push(`${jumlahGagal} segmen memakai garis penghubung`);
    }

    info.getContainer().textContent =
        keterangan.join(' · ') || 'Jalur perjalanan tidak tersedia.';

    peta.fitBounds(L.latLngBounds(posisi).pad(0.15));

    setTimeout(() => peta.invalidateSize(), 200);
}

// Satu tempat beserta area check-in (lingkaran seluas radius aslinya) dan tempat lain dalam 1,5 km.
// Dipakai sebagai gambar utama halaman tempat yang belum punya foto berlisensi.
function modeLokasi(el) {
    const l = JSON.parse(el.dataset.lokasi);
    // di layar sentuh, geser satu jari tetap untuk menggulir halaman, bukan menggeser peta
    const peta = buatPeta(el, { dragging: !L.Browser.mobile });
    L.circle([l.lat, l.lng], { radius: l.radius, color: '#0f3b2e', weight: 2, fillColor: '#e3a51c', fillOpacity: 0.3 }).addTo(peta);
    L.marker([l.lat, l.lng], { icon: ikon(WARNA[l.kategori] ?? '#e3a51c'), title: l.nama, alt: l.nama, zIndexOffset: 1000 }).addTo(peta);

    const titik = [[l.lat, l.lng]];
    l.sekitar.filter((t) => t.jarak <= 1500).forEach((t) => {
        const isi = `<div class="popup-tempat"><b>${esc(t.nama)}</b><span>${t.jarak.toLocaleString('id')} m dari sini</span><div class="aksi"><a href="${esc(t.url)}">Lihat tempat</a></div></div>`;
        L.marker([t.lat, t.lng], { icon: ikon(WARNA[t.kategori] ?? '#e3a51c'), title: t.nama, alt: t.nama, opacity: 0.8 }).bindPopup(isi).addTo(peta);
        titik.push([t.lat, t.lng]);
    });
    if (titik.length > 1) peta.fitBounds(L.latLngBounds(titik).pad(0.2), { maxZoom: 17 });
    else peta.setView([l.lat, l.lng], 16);
    setTimeout(() => peta.invalidateSize(), 200);
}

function modeAlat(el) {
    const tempat = JSON.parse(el.dataset.tempat);
    const peta = buatPeta(el, { scrollWheelZoom: true });
    const daftar = document.querySelector('[data-peta-daftar]');
    const hitung = document.querySelector('[data-peta-hitung]');
    const kosong = document.querySelector('[data-peta-kosong]');
    const inputCari = document.querySelector('[data-peta-cari]');
    const pilihUrut = document.querySelector('[data-peta-urut]');
    const lembar = document.querySelector('[data-lembar-peta]');
    const saring = { kategori: new Set(Object.keys(WARNA)), buka: false, tiket: false, teks: '', urut: 'bawaan' };
    const titikPusat = { lat: 3.5752, lng: 98.6838 }; // Istana Maimun sebagai patokan jarak awal
    let lokasi = null;
    let lokasiPenanda = null;

    const penanda = new Map();
    tempat.forEach((t) => {
        const m = L.marker([t.lat, t.lng], { icon: ikon(WARNA[t.kategori]), title: t.nama, alt: t.nama }).bindPopup(() => popup(t));
        m.on('click', () => sorot(t.slug, false));
        penanda.set(t.slug, m);
    });

    function aturLembar(mode) {
        if (!lembar) return;
        lembar.dataset.tinggi = mode;
        document.querySelectorAll('[data-tinggi-peta]').forEach((b) => {
            b.setAttribute('aria-pressed', b.dataset.tinggiPeta === mode ? 'true' : 'false');
        });
        setTimeout(() => peta.invalidateSize(), 260);
    }

    document.querySelectorAll('[data-tinggi-peta]').forEach((b) => {
        b.addEventListener('click', () => aturLembar(b.dataset.tinggiPeta));
    });

    function sorot(slug, terbang = true) {
        daftar.querySelectorAll('[data-slug]').forEach((a) => (a.dataset.slug === slug ? a.setAttribute('aria-current', 'true') : a.removeAttribute('aria-current')));
        const t = tempat.find((x) => x.slug === slug);
        if (terbang && t) {
            if (innerWidth <= 900) aturLembar('intip');
            peta.flyTo([t.lat, t.lng], Math.max(peta.getZoom(), 16), { duration: 0.6 });
            penanda.get(slug)?.openPopup();
        }
        daftar.querySelector(`[data-slug="${slug}"]`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function jarak(a, b) {
        const r = 6371000, d = Math.PI / 180;
        const h = Math.sin(((b.lat - a.lat) * d) / 2) ** 2 + Math.cos(a.lat * d) * Math.cos(b.lat * d) * Math.sin(((b.lng - a.lng) * d) / 2) ** 2;
        return Math.round(2 * r * Math.asin(Math.sqrt(h)));
    }

    function gambar() {
        const jam = new Date().getHours();
        const kueri = saring.teks.toLowerCase();
        let tampil = tempat.filter((t) => {
            const cocokKat = saring.kategori.has(t.kategori);
            const cocokJam = !saring.buka || (t.buka && jam >= t.buka[0] && jam < t.buka[1]);
            const cocokTiket = !saring.tiket || Boolean(t.tiket);
            const cocokTeks = !kueri || `${t.nama} ${t.kecamatan ?? ''} ${t.kategori}`.toLowerCase().includes(kueri);
            return cocokKat && cocokJam && cocokTiket && cocokTeks;
        });

        tampil = tampil.map((t) => ({ ...t, jarak: lokasi ? jarak(lokasi, t) : null, jarakPusat: jarak(titikPusat, t) }));

        if (saring.urut === 'rating') {
    tampil.sort((a, b) =>
        (b.rating - a.rating) ||
        (b.jumlahUlasan - a.jumlahUlasan)
    );
        } else if (saring.urut === 'kunjungan') {
            tampil.sort((a, b) =>
                b.jumlahKunjungan - a.jumlahKunjungan
            );
        } else if (saring.urut === 'terdekat' || lokasi) {
            tampil.sort((a, b) =>
                (a.jarak ?? a.jarakPusat) - (b.jarak ?? b.jarakPusat)
            );
        } else if (saring.urut === 'termurah') {
            tampil.sort((a, b) =>
                (a.hargaAngka ?? 0) - (b.hargaAngka ?? 0)
            );
        }

        const terlihat = new Set(tampil.map((t) => t.slug));
        penanda.forEach((m, slug) => (terlihat.has(slug) ? m.addTo(peta) : m.remove()));
        daftar.querySelectorAll('[data-slug]').forEach((a) => {
            const t = tampil.find((x) => x.slug === a.dataset.slug);
            a.hidden = !t;
            const j = a.querySelector('[data-jarak]');
            if (j) {
                j.textContent = t && t.jarak != null
                    ? `${t.jarak.toLocaleString('id')} m dari lokasimu`
                    : (saring.urut === 'terdekat' && t ? `${t.jarakPusat.toLocaleString('id')} m dari Istana Maimun` : '');
            }
        });
        tampil.forEach((t) => {
            const kartu = daftar.querySelector(`[data-slug="${t.slug}"]`);
            if (kartu) daftar.insertBefore(kartu, kosong);
        });
        hitung.textContent = `${tampil.length} dari ${tempat.length} tempat`;
        kosong.hidden = tampil.length > 0;
    }

    inputCari?.addEventListener('input', () => {
        saring.teks = inputCari.value.trim();
        gambar();
    });
    pilihUrut?.addEventListener('change', () => {
        saring.urut = pilihUrut.value;
        gambar();
    });

    document.querySelectorAll('[data-saring-kategori]').forEach((b) => {
        b.addEventListener('click', () => {
            const k = b.dataset.saringKategori;
            saring.kategori.has(k) ? saring.kategori.delete(k) : saring.kategori.add(k);
            b.setAttribute('aria-pressed', saring.kategori.has(k) ? 'true' : 'false');
            gambar();
        });
    });
    document.querySelectorAll('[data-saring]').forEach((b) => {
        b.addEventListener('click', () => {
            const k = b.dataset.saring;
            saring[k] = !saring[k];
            b.setAttribute('aria-pressed', saring[k] ? 'true' : 'false');
            gambar();
        });
    });
    document.querySelector('[data-hapus-saring]')?.addEventListener('click', () => {
        saring.kategori = new Set(Object.keys(WARNA));
        saring.buka = saring.tiket = false;
        saring.teks = '';
        saring.urut = 'bawaan';
        if (inputCari) inputCari.value = '';
        if (pilihUrut) pilihUrut.value = 'bawaan';
        document.querySelectorAll('[data-saring-kategori]').forEach((b) => b.setAttribute('aria-pressed', 'true'));
        document.querySelectorAll('[data-saring]').forEach((b) => b.setAttribute('aria-pressed', 'false'));
        gambar();
    });

    const tombolLokasi = document.querySelector('[data-lokasi-saya]');
    const pesanLokasi = document.querySelector('[data-pesan-lokasi]');
    tombolLokasi?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            pesanLokasi.textContent = 'Peramban ini tidak bisa membaca lokasi.';
            return;
        }
        tombolLokasi.setAttribute('aria-busy', 'true');
        pesanLokasi.textContent = 'Membaca lokasimu…';
        navigator.geolocation.getCurrentPosition((pos) => {
            lokasi = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            lokasiPenanda?.remove();
            lokasiPenanda = L.circleMarker([lokasi.lat, lokasi.lng], { radius: 8, color: '#ffffff', weight: 3, fillColor: '#2f6690', fillOpacity: 1 }).addTo(peta).bindTooltip('Kamu di sini');
            tombolLokasi.setAttribute('aria-pressed', 'true');
            tombolLokasi.removeAttribute('aria-busy');
            const terdekat = Math.min(...tempat.map((t) => jarak(lokasi, t)));
            pesanLokasi.textContent = terdekat > 30000 ? 'Kamu sedang jauh dari Medan. Daftar tetap diurutkan dari yang paling dekat.' : 'Diurutkan dari yang terdekat denganmu.';
            gambar();
        }, () => {
            tombolLokasi.removeAttribute('aria-busy');
            pesanLokasi.textContent = 'Izin lokasi ditolak. Peta tetap bisa dipakai tanpa lokasimu.';
        }, { enableHighAccuracy: true, timeout: 10000 });
    });

    daftar.querySelectorAll('[data-slug]').forEach((a) => {
        a.addEventListener('mouseenter', () => penanda.get(a.dataset.slug)?.setZIndexOffset(400));
        a.addEventListener('mouseleave', () => penanda.get(a.dataset.slug)?.setZIndexOffset(0));
    });
    daftar.querySelectorAll('[data-fokus-peta]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            sorot(btn.dataset.fokusPeta, true);
        });
    });

    gambar();
    peta.fitBounds(L.latLngBounds(tempat.filter((t) => t.lat > 3.55).map((t) => [t.lat, t.lng])).pad(0.1));
    setTimeout(() => peta.invalidateSize(), 200);
}

document.querySelectorAll('[data-peta]').forEach((el) => {
    const mode = el.dataset.petaMode;
    if (mode === 'ringkas') modeRingkas(el);
    else if (mode === 'rute') modeRute(el);
    else if (mode === 'alat') modeAlat(el);
    else if (mode === 'lokasi') modeLokasi(el);
});
