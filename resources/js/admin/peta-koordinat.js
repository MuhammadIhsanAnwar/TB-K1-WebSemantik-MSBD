
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const BATAS = {
    minLat: 3.4,
    maxLat: 3.8,
    minLng: 98.5,
    maxLng: 98.8,
};

document.querySelectorAll('[data-peta-koordinat]').forEach((root) => {
    const form = root.closest('form');
    const latInput = form?.querySelector('[name="lat"]');
    const lngInput = form?.querySelector('[name="lng"]');
    const mapElement = root.querySelector('[data-peta-admin]');
    const searchInput = root.querySelector('[data-cari-alamat]');
    const resultsElement = root.querySelector('[data-hasil-alamat]');
    const statusElement = root.querySelector('[data-status-peta]');

    if (!latInput || !lngInput || !mapElement || !searchInput) return;

    const parseKoordinat = () => ({
        lat: Number(latInput.value),
        lng: Number(lngInput.value),
    });

    const valid = (lat, lng) =>
        Number.isFinite(lat) &&
        Number.isFinite(lng) &&
        lat >= BATAS.minLat &&
        lat <= BATAS.maxLat &&
        lng >= BATAS.minLng &&
        lng <= BATAS.maxLng;

    const awal = parseKoordinat();
    const punyaKoordinat = valid(awal.lat, awal.lng);

    const peta = L.map(mapElement, {
        scrollWheelZoom: false,
    }).setView(
        punyaKoordinat ? [awal.lat, awal.lng] : [3.5826, 98.6790],
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

    function perbaruiInput(lat, lng) {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
    }

    function pilihKoordinat(lat, lng) {
        if (!valid(lat, lng)) {
            statusElement.textContent =
                'Pilih lokasi yang masih berada di wilayah Medan.';
            return false;
        }

        perbaruiInput(lat, lng);

        if (!marker) {
            marker = L.marker([lat, lng], {
                draggable: true,
                icon: ikon,
            }).addTo(peta);

            marker.on('dragend', () => {
                const posisi = marker.getLatLng();

                if (!valid(posisi.lat, posisi.lng)) {
                    marker.setLatLng([latInput.value, lngInput.value]);
                    statusElement.textContent =
                        'Koordinat harus berada di wilayah Medan.';
                    return;
                }

                perbaruiInput(posisi.lat, posisi.lng);
                statusElement.textContent = 'Koordinat berhasil diperbarui.';
            });
        } else {
            marker.setLatLng([lat, lng]);
        }

        statusElement.textContent =
            `Latitude: ${Number(lat).toFixed(6)}, Longitude: ${Number(lng).toFixed(6)}`;

        return true;
    }

    if (punyaKoordinat) {
        pilihKoordinat(awal.lat, awal.lng);
    }

    peta.on('click', (event) => {
        const { lat, lng } = event.latlng;

        if (pilihKoordinat(lat, lng)) {
            peta.panTo([lat, lng]);
        }
    });

    function sinkronisasiManual() {
        const { lat, lng } = parseKoordinat();

        if (!valid(lat, lng)) return;

        pilihKoordinat(lat, lng);
        peta.panTo([lat, lng]);
    }

    latInput.addEventListener('change', sinkronisasiManual);
    lngInput.addEventListener('change', sinkronisasiManual);

    let debounce = null;
    let controller = null;

    searchInput.addEventListener('input', () => {
        clearTimeout(debounce);
        controller?.abort();

        const query = searchInput.value.trim();
        resultsElement.replaceChildren();

        if (query.length < 3) return;

        debounce = setTimeout(async () => {
            controller = new AbortController();

            statusElement.textContent = 'Mencari alamat...';

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
                    throw new Error('Pencarian alamat gagal.');
                }

                const data = await response.json();
                resultsElement.replaceChildren();

                data.features.forEach((feature) => {
                    const props = feature.properties;
                    const [lng, lat] = feature.geometry.coordinates;

                    if (!valid(lat, lng)) return;

                    const nama = [
                        props.name,
                        props.street,
                        props.district,
                        props.city,
                    ].filter(Boolean);

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = [...new Set(nama)].join(', ');

                    button.addEventListener('click', () => {
                        if (pilihKoordinat(lat, lng)) {
                            peta.flyTo([lat, lng], 17);
                            resultsElement.replaceChildren();
                            searchInput.value = button.textContent;
                        }
                    });

                    resultsElement.appendChild(button);
                });

                if (!resultsElement.children.length) {
                    statusElement.textContent =
                        'Tidak ditemukan lokasi di wilayah Medan.';
                } else {
                    statusElement.textContent =
                        'Pilih alamat untuk menentukan koordinat.';
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    statusElement.textContent =
                        'Pencarian gagal. Periksa koneksi internetmu.';
                }
            }
        }, 500);
    });

    petaElementResize();

    function petaElementResize() {
        setTimeout(() => peta.invalidateSize(), 250);

        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(() => {
                peta.invalidateSize();
            });

            observer.observe(mapElement);
        }
    }
});
