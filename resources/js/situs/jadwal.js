// Jam buka tempat: aturan yang sama dengan Katalog::bukaSekarang di server.
// jadwal = { menit: [buka, tutup], hari: [1..7] } (1 = Senin), atau null kalau jam buka tidak diketahui.

// Hari dan menit sejak 00.00 menurut jam WIB, apa pun zona waktu perangkatnya.
export function waktuWib(tanggal = new Date()) {
    const bagian = Object.fromEntries(new Intl.DateTimeFormat('en-GB', {
