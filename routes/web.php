<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\SitusController;
use App\Http\Controllers\TiketController;
use Illuminate\Support\Facades\Route;

// ---------- publik ----------
Route::controller(SitusController::class)->group(function () {
    Route::get('/', 'beranda')->name('beranda');
    Route::get('/cari', 'cari')->name('cari');
    Route::get('/cari/saran.json', 'saran')->name('cari.saran');
    Route::get('/tanya', 'tanya')->name('tanya');
    Route::post('/tanya', 'jawab')->name('tanya.jawab');
    Route::get('/peta', 'peta')->name('peta');
    Route::get('/tempat/{slug}', 'tempat')->name('tempat');
    Route::get('/jelajah', 'jelajah')->name('jelajah');
    Route::get('/jelajah/graf.json', 'graf')->name('jelajah.graf');
    Route::get('/jalur', 'jalur')->name('jalur');
    Route::get('/jalur/{slug}', 'jalurDetail')->name('jalur.detail');
    Route::get('/rencana', 'rencana')->name('rencana');
    Route::get('/r/{kode}', 'rencanaBagikan')->name('rencana.bagikan');
    Route::get('/data', 'data')->name('data');
    Route::post('/data/sparql', 'sparql')->name('data.sparql');
    Route::get('/data/medanvisit.ttl', 'turtle')->name('data.turtle');
    Route::get('/data/medanvisit.jsonld', 'jsonld')->name('data.jsonld');
    Route::get('/kredit-foto', 'kredit')->name('kredit');
    Route::get('/ketentuan', 'ketentuan')->name('ketentuan');
    Route::get('/{jenis}/{slug}', 'entitas')->whereIn('jenis', ['tokoh', 'budaya', 'kuliner', 'peristiwa', 'kerajaan', 'transportasi'])->name('entitas');
});

// ---------- akun ----------
Route::controller(AkunController::class)->group(function () {
    Route::get('/masuk', 'masuk')->name('masuk');
    Route::post('/masuk', 'prosesMasuk');
    Route::get('/daftar', 'daftar')->name('daftar');
    Route::post('/daftar', 'prosesDaftar');
    Route::post('/keluar', 'keluar')->name('keluar');

    Route::middleware('peran')->group(function () {
        Route::get('/akun', 'akun')->name('akun');
        Route::get('/paspor', 'paspor')->name('paspor');
        Route::post('/tempat/{slug}/checkin', 'checkin')->name('checkin');
        Route::get('/checkin/{id}', 'checkinHasil')->name('checkin.hasil');
        Route::post('/paspor/tukar/{id}', 'tukar')->name('paspor.tukar');
        Route::get('/voucher', 'voucher')->name('voucher');
        Route::get('/peringkat', 'peringkat')->name('peringkat');
        Route::get('/ulasan-saya', 'ulasanSaya')->name('ulasan.saya');
        Route::post('/tempat/{slug}/ulasan', 'tulisUlasan')->name('ulasan.tulis');
        Route::get('/rencana/saya', 'rencanaSaya')->name('rencana.saya');
        Route::post('/rencana/simpan', 'simpanRencana')->name('rencana.simpan');
    });
});

// ---------- tiket ----------
Route::controller(TiketController::class)->middleware('peran')->group(function () {
    Route::get('/tempat/{slug}/tiket', 'pilih')->name('tiket.pilih');
    Route::post('/tempat/{slug}/tiket', 'simpanPilihan');
    Route::get('/checkout', 'checkout')->name('checkout');
    Route::post('/checkout', 'bayar');
    Route::get('/pesanan/{kode}', 'status')->name('pesanan');
    Route::post('/pesanan/{kode}/simulasi', 'simulasi')->name('pesanan.simulasi');
    Route::post('/pesanan/{kode}/batal', 'batal')->name('pesanan.batal');
    Route::get('/tiket', 'daftar')->name('tiket.saya');
    Route::get('/tiket/{kode}', 'eTiket')->name('tiket.e');
});

// ---------- mitra UMKM ----------
Route::controller(MitraController::class)->middleware('peran:mitra')->prefix('mitra')->name('mitra.')->group(function () {
    Route::get('/', 'listing')->name('listing');
    Route::post('/', 'simpanListing');
    Route::get('/voucher', 'voucher')->name('voucher');
    Route::post('/voucher', 'buatVoucher');
    Route::get('/penukaran', 'penukaran')->name('penukaran');
    Route::post('/penukaran', 'verifikasi');
    Route::get('/statistik', 'statistik')->name('statistik');
});

// ---------- kurator & admin ----------
Route::controller(AdminController::class)->middleware('peran:kurator')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', 'ringkasan')->name('ringkasan');
    Route::get('/entitas', 'entitas')->name('entitas');
    Route::get('/entitas/{slug}', 'ubahEntitas')->name('entitas.ubah');
    Route::post('/entitas/{slug}', 'simpanEntitas');
    Route::get('/relasi', 'relasi')->name('relasi');
    Route::post('/relasi', 'tambahRelasi');
    Route::get('/cerita', 'cerita')->name('cerita');
    Route::post('/cerita', 'simpanCerita');
    Route::get('/ulasan', 'ulasan')->name('ulasan');
    Route::post('/ulasan/{id}', 'moderasi')->name('ulasan.moderasi');
    Route::get('/mitra', 'mitra')->name('mitra');
    Route::get('/tiket', 'tiket')->name('tiket');
    Route::post('/tiket/validasi', 'validasi')->name('tiket.validasi');
    Route::get('/sinkronisasi', 'sinkronisasi')->name('sinkronisasi');
});
