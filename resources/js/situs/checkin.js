// Check-in: baca lokasi GPS, isi lat/lng tersembunyi, lalu kirim. Server menghitung jarak ke tempat
// dan menolak bila di luar radius. Tanpa izin lokasi, formulir tidak dikirim dan alasannya ditampilkan.
document.querySelectorAll('[data-checkin]').forEach((form) => {
    const tombol = form.querySelector('button');
    const pesan = form.querySelector('[data-pesan-checkin]');
    form.addEventListener('submit', (e) => {
        if (form.dataset.siap === '1') return;
        e.preventDefault();
        if (!navigator.geolocation) {
            pesan.textContent = 'Peramban ini tidak bisa membaca lokasi, jadi check-in belum bisa dilakukan.';
            return;
        }
        tombol.disabled = true;
        const label = tombol.textContent;
        tombol.textContent = 'Memeriksa lokasimu…';
        pesan.textContent = '';
        navigator.geolocation.getCurrentPosition((pos) => {
            form.querySelector('[name="lat"]').value = pos.coords.latitude;
            form.querySelector('[name="lng"]').value = pos.coords.longitude;
            form.querySelector('[name="akurasi"]').value = Math.round(pos.coords.accuracy);
            form.dataset.siap = '1';
            form.submit();
        }, (err) => {
            tombol.disabled = false;
            tombol.textContent = label;
            pesan.textContent = err.code === 1
                ? 'Izin lokasi ditolak. Izinkan akses lokasi untuk situs ini, lalu coba lagi.'
                : 'Sinyal GPS lemah. Coba lagi di tempat terbuka.';
        }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 });
    });
});
