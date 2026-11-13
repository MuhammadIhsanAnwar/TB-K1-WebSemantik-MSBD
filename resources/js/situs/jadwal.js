// Jam buka tempat: aturan yang sama dengan Katalog::bukaSekarang di server.
// jadwal = { menit: [buka, tutup], hari: [1..7] } (1 = Senin), atau null kalau jam buka tidak diketahui.

// Hari dan menit sejak 00.00 menurut jam WIB, apa pun zona waktu perangkatnya.
export function waktuWib(tanggal = new Date()) {
    const bagian = Object.fromEntries(new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Jakarta', weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).formatToParts(tanggal).map((p) => [p.type, p.value]));
    const hari = { Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6, Sun: 7 }[bagian.weekday];
    return { hari, menit: Number(bagian.hour) * 60 + Number(bagian.minute) };
}

// Jam yang lewat tengah malam (tutup lebih kecil dari buka) ikut dihitung.
export function bukaPada(jadwal, kini = waktuWib()) {
    if (!jadwal) return false;
