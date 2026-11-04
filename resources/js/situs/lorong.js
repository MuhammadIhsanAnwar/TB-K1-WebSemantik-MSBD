// Lorong Waktu: geser untuk membandingkan foto arsip dengan foto kini. Kontrolnya input range asli,
// jadi bisa dipakai dengan papan ketik (panah kiri/kanan) dan pembaca layar.
document.querySelectorAll('[data-lorong]').forEach((el) => {
    const geser = el.querySelector('input[type="range"]');
    const set = () => el.style.setProperty('--pos', `${geser.value}%`);
    geser.addEventListener('input', set);
    set();
});
