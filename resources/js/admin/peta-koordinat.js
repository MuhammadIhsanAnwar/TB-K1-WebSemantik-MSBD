
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const BATAS = {
    minLat: 3.4,
    maxLat: 3.8,
    minLng: 98.5,
    maxLng: 98.8,
};

const PUSAT = [3.5826, 98.6790];

document.querySelectorAll('[data-peta-koordinat]').forEach((root) => {
    const form = root.closest('form');

    const latInput = form?.querySelector('[name="lat"]');
    const lngInput = form?.querySelector('[name="lng"]');
    const alamatInput = form?.querySelector('[name="alamat"]');

    const mapElement = root.querySelector('[data-peta-admin]');
    const searchInput = root.querySelector('[data-cari-alamat]');
    const resultsElement = root.querySelector('[data-hasil-alamat]');
    const statusElement = root.querySelector('[data-status-peta]');

    if (
        !latInput ||
        !lngInput ||
        !mapElement ||
        !searchInput ||
        !statusElement
    ) {
        console.error('Elemen peta admin tidak lengkap.');
        return;
    }

    const valid = (lat, lng) =>
        Number.isFinite(lat) &&
        Number.isFinite(lng) &&
        lat >= BATAS.minLat &&
        lat <= BATAS.maxLat &&
        lng >= BATAS.minLng &&
        lng <= BATAS.maxLng;

    const status = (pesan) => {
        statusElement.textContent = pesan;
    };

    const latAwal = Number(latInput.value);
    const lngAwal = Number(lngInput.value);

    const punyaKoordinat =
        latInput.value.trim() !== '' &&
        lngInput.value.trim() !== '' &&
        valid(latAwal, lngAwal);

    const peta = L.map(mapElement, {
        scrollWheelZoom: false,
    }).setView(
        punyaKoordinat ? [latAwal, lngAwal] : PUSAT,
        punyaKoordinat ? 16 : 12
    );

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(peta);

    const ikon = L.divIcon({
        className: '',
        html: '<div style="width:20px;height:20px;background:#c9541e;border:3px solid white;border-radius:50%;box-shadow:0 1px 5px #333"></div>',
        iconSize: [20, 20],
        iconAnchor: [10, 10],
    });

    let marker = null;
    let posisiTerakhir = null;

    function perbaruiInput(lat, lng) {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
    }

    function buatMarker(lat, lng) {
        if (!marker) {
            marker = L.marker([lat, lng], {
                draggable: true,
                icon: ikon,
            }).addTo(peta);

            marker.on('dragend', () => {
                const posisi = marker.getLatLng();

                if (!valid(posisi.lat, posisi.lng)) {
                    marker.setLatLng(posisiTerakhir);
                    status('Koordinat harus berada di wilayah Medan.');
                    return;
                }

                posisiTerakhir = marker.getLatLng();
                perbaruiInput(posisi.lat, posisi.lng);
                status('Koordinat berhasil diperbarui.');
            });
        } else {
            marker.setLatLng([lat, lng]);
        }

        posisiTerakhir = L.latLng(lat, lng);
    }

    function pilihKoordinat(lat, lng, geserPeta = false) {
        if (!valid(lat, lng)) {
            status('Pilih lokasi yang masih berada di wilayah Medan.');
            return false;
        }

        perbaruiInput(lat, lng);
        buatMarker(lat, lng);

        if (geserPeta) {
            peta.flyTo([lat, lng], 17);
        }

        status(
            `Latitude: ${lat.toFixed(6)}, Longitude: ${lng.toFixed(6)}`
        );

        return true;
    }

    if (punyaKoordinat) {
        pilihKoordinat(latAwal, lngAwal);
    } else {
        status(
            'Koordinat belum valid. Klik peta atau cari alamat untuk menentukan lokasi.'
        );
    }

    peta.on('click', (event) => {
        pilihKoordinat(
            event.latlng.lat,
            event.latlng.lng
        );
    });

    function sinkronisasiManual() {
        if (
            latInput.value.trim() === '' ||
            lngInput.value.trim() === ''
        ) {
            status('Isi latitude dan longitude terlebih dahulu.');
            return;
        }

        const lat = Number(latInput.value);
        const lng = Number(lngInput.value);

        if (!pilihKoordinat(lat, lng, true)) {
            status('Koordinat tidak valid atau di luar wilayah Medan.');
        }
    }

    latInput.addEventListener('change', sinkronisasiManual);
    lngInput.addEventListener('change', sinkronisasiManual);

    let debounce = null;
    let controller = null;

    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        controller?.abort();

        const query = searchInput.value.trim();

        resultsElement?.replaceChildren();

        if (query.length < 3) return;

        debounce = setTimeout(async () => {
            controller = new AbortController();

            status('Mencari alamat...');

            try {
                const params = new URLSearchParams({
                    q: `${query}, Medan, Indonesia`,
                    limit: '5',
                });

                const response = await fetch(
                    `https://photon.komoot.io/api/?${params}`,
                    { signal: controller.signal }
                );

                if (!response.ok) {
                    throw new Error('Pencarian gagal.');
                }

                const data = await response.json();
                const features = data.features ?? [];

                resultsElement?.replaceChildren();

                let jumlahValid = 0;

                features.forEach((feature) => {
                    const props = feature.properties ?? {};
                    const coordinates = feature.geometry?.coordinates;

                    if (!coordinates || coordinates.length < 2) return;

                    const [lng, lat] = coordinates;

                    if (!valid(lat, lng)) return;

                    const nama = [
                        props.name,
                        props.street,
                        props.district,
                        props.city,
                    ].filter(Boolean);

                    const alamat = [...new Set(nama)].join(', ');

                    const button = document.createElement('button');

                    button.type = 'button';
                    button.textContent = alamat || 'Lokasi tanpa nama';
                    button.className = 'p-btn';
                    button.style.marginBottom = '6px';

                    button.addEventListener('click', () => {
                        if (pilihKoordinat(lat, lng, true)) {
                            if (alamatInput) {
                                alamatInput.value = alamat;
                            }

                            searchInput.value = alamat;
                            resultsElement?.replaceChildren();
                            status('Lokasi berhasil dipilih.');
                        }
                    });

                    resultsElement?.appendChild(button);
                    jumlahValid++;
                });

                if (jumlahValid === 0) {
                    status('Tidak ditemukan lokasi di wilayah Medan.');
                } else {
                    status(`${jumlahValid} lokasi ditemukan. Pilih salah satu.`);
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Photon error:', error);
                    status('Pencarian gagal. Periksa koneksi internet.');
                }
            }
        }, 500);
    });

    const observer = new ResizeObserver(() => {
        peta.invalidateSize();
    });

    observer.observe(mapElement);

    setTimeout(() => peta.invalidateSize(), 300);
});
