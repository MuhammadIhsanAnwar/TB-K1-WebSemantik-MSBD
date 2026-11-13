<?php

namespace App\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Penyimpanan transaksi MedanVisit.
 *
 * Pesanan tiket, ulasan, log pencarian, dan rencana disimpan di DB 2 (koneksi "transaksi", PostgreSQL).
 * Aturan bisnisnya ada di database: sp_buat_pesanan dan sp_bayar_pesanan (procedure), total pesanan,
 * rating tempat, dan poin dihitung trigger. Pengguna dirujuk lewat id_pengguna (users.id di DB 1),
 * tempat lewat slug resource Database 3.
 *
 * Check-in, voucher, dan penukaran poin masih di cache berkas: fiturnya tidak punya route lagi.
 */
class Penyimpanan
{
    private const ZONA = 'Asia/Jakarta';

    private static function db(): ConnectionInterface
    {
        return DB::connection('transaksi');
    }

    /** Untuk bacaan yang tampil di banyak halaman: kalau DB 2 tidak terjangkau, halaman tetap terbuka. */
    private static function aman(callable $baca, mixed $cadangan): mixed
    {
        try {
            return $baca();
        } catch (Throwable $e) {
            Log::warning('DB 2 (transaksi) tidak bisa dibaca: '.$e->getMessage());

            return $cadangan;
        }
    }

    private static function waktu(?string $nilai): ?string
    {
        return $nilai === null ? null : Carbon::parse($nilai)->setTimezone(self::ZONA)->toIso8601String();
    }

    // ---------- pesanan tiket ----------

    /** Semua pesanan (terbaru dulu), atau milik satu pengguna. */
    public static function pesanan(?int $idPengguna = null, bool $aman = false): array
    {
        $baca = function () use ($idPengguna) {
            $q = self::queryPesanan();
            if ($idPengguna !== null) {
                $q->where('p.id_pengguna', $idPengguna);
            }

            return self::bentukPesanan($q->orderByDesc('p.created_at')->orderByDesc('p.id_pesanan')->get()->all());
        };

        return $aman ? self::aman($baca, []) : $baca();
    }

    public static function cariPesanan(string $kode): ?array
    {
        return self::bentukPesanan(self::queryPesanan()->where('p.kode', $kode)->limit(1)->get()->all())[0] ?? null;
    }

    /**
     * Membuat pesanan lewat procedure sp_buat_pesanan (pesanan + item dalam satu transaksi,
     * total dihitung trigger, batas bayar 15 menit).
     */
    public static function buatPesanan(array $data): array
    {
        $item = array_map(fn ($i) => ['jenis' => $i['jenis'], 'harga' => (int) $i['harga'], 'jumlah' => (int) $i['jumlah']], $data['item']);
        $hasil = self::db()->selectOne('CALL sp_buat_pesanan(?, ?, ?, ?, ?, ?::date, ?, ?::jsonb, NULL)', [
            $data['id_pengguna'],
            $data['pemesan']['nama'],
            $data['pemesan']['email'],
            $data['pemesan']['hp'],
            $data['tempat'],
            $data['tanggal'],
            $data['metode'],
            json_encode($item),
        ]);

        return self::cariPesanan($hasil->p_kode);
    }
    private static function ambil(string $kunci): array
    {
        return Cache::get('medanvisit.'.$kunci, []);
    }

    private static function simpan(string $kunci, array $isi): void
    {
        Cache::forever('medanvisit.'.$kunci, $isi);
    }

    private static function tambah(string $kunci, array $baris): array
    {
        $isi = self::ambil($kunci);
        $baris += ['id' => (string) Str::ulid(), 'waktu' => now('Asia/Jakarta')->toIso8601String()];
        $isi[$baris['id']] = $baris;
        self::simpan($kunci, $isi);

        return $baris;
    }

    // ---------- pesanan tiket ----------
    public static function pesanan(?string $email = null): array
    {
        $semua = self::ambil('pesanan');

        return array_reverse(array_filter($semua, fn ($p) => $email === null || $p['email'] === $email));
    }

    public static function buatPesanan(array $data): array
    {
        $data['kode'] = 'MV-'.strtoupper(Str::random(4)).'-'.random_int(1000, 9999);
        $data['status'] = 'menunggu';
        $data['batas_bayar'] = now('Asia/Jakarta')->addMinutes(15)->toIso8601String();

        return self::tambah('pesanan', $data);
    }

    public static function cariPesanan(string $kode): ?array
    {
        foreach (self::ambil('pesanan') as $p) {
            if ($p['kode'] === $kode) {
                return $p;
            }
        }

        return null;
    }

    public static function ubahPesanan(string $kode, array $ubah): ?array
    {
        $semua = self::ambil('pesanan');
        foreach ($semua as $id => $p) {
            if ($p['kode'] === $kode) {
                $semua[$id] = array_merge($p, $ubah);
                self::simpan('pesanan', $semua);

                return $semua[$id];
            }
        }

        return null;
    }

    // ---------- check-in ----------
    public static function checkin(?string $email = null): array
    {
        return array_reverse(array_filter(self::ambil('checkin'), fn ($c) => $email === null || $c['email'] === $email));
    }

    public static function catatCheckin(string $email, string $nama, string $tempat, int $jarak): array
    {
        return self::tambah('checkin', ['email' => $email, 'nama' => $nama, 'tempat' => $tempat, 'jarak_m' => $jarak]);
    }

    public static function sudahCheckinHariIni(string $email, string $tempat): bool
    {
        $hariIni = now('Asia/Jakarta')->toDateString();
        foreach (self::checkin($email) as $c) {
            if ($c['tempat'] === $tempat && str_starts_with($c['waktu'], $hariIni)) {
                return true;
            }
        }

        return false;
    }

    /** Poin: 10 per check-in, +25 sekali saat dua tempat bersejarah berbeda sudah dikunjungi, dikurangi penukaran. */
    public static function poin(string $email): array
    {
        $checkin = self::checkin($email);
        $sejarah = collect($checkin)->pluck('tempat')->unique()->filter(fn ($s) => (Katalog::get($s)['kategori'] ?? null) === 'sejarah');
        $badge = [];
        $poin = count($checkin) * 10;
        if ($sejarah->count() >= 2) {
            $badge[] = ['nama' => 'Pemburu Sejarah', 'keterangan' => '2 tempat bersejarah, bonus 25 poin'];
            $poin += 25;
        }
        $dipakai = collect(self::penukaran($email))->sum('poin');

        return ['total' => $poin - $dipakai, 'didapat' => $poin, 'dipakai' => $dipakai, 'badge' => $badge];
    }

    // ---------- ulasan ----------
    public static function ulasan(?string $tempat = null, ?string $status = null): array
    {
        return array_reverse(array_filter(self::ambil('ulasan'), fn ($u) => ($tempat === null || $u['tempat'] === $tempat) && ($status === null || $u['status'] === $status)));
    }

    public static function tulisUlasan(array $data): array
    {
        return self::tambah('ulasan', $data + [
            'status' => 'tayang',
        ]);
    }

    public static function moderasi(string $id, string $status): void
    {
        $semua = self::ambil('ulasan');
        if (isset($semua[$id])) {
            $semua[$id]['status'] = $status;
            self::simpan('ulasan', $semua);
        }
    }

    // ---------- log pencarian ----------
    public static function catatCari(string $q, int $jumlahHasil): void
    {
        if (trim($q) !== '') {
            self::tambah('cari', ['q' => mb_strtolower(trim($q)), 'hasil' => $jumlahHasil]);
        }
    }

    /** Kata kunci yang dicari tetapi tanpa hasil, dengan jumlahnya. */
    public static function celahKonten(): array
    {
        return collect(self::ambil('cari'))->where('hasil', 0)->countBy('q')->sortDesc()->all();
    }

    public static function jumlahCari(): int
    {
        return count(self::ambil('cari'));
    }

    // ---------- voucher & penukaran ----------
    public static function voucher(): array
    {
        return self::ambil('voucher');
    }

    public static function buatVoucher(array $data): array
    {
        return self::tambah('voucher', $data);
    }

    public static function penukaran(?string $email = null): array
    {
        return array_reverse(array_filter(self::ambil('penukaran'), fn ($p) => $email === null || $p['email'] === $email));
    }

    public static function tukar(string $email, array $voucher): array
    {
        $semua = self::voucher();
        $semua[$voucher['id']]['stok']--;
        self::simpan('voucher', $semua);

        return self::tambah('penukaran', [
            'email' => $email, 'voucher' => $voucher['id'], 'nama' => $voucher['nama'], 'usaha' => $voucher['usaha'],
            'poin' => $voucher['poin'], 'kode' => 'V-'.strtoupper(Str::random(6)), 'dipakai' => false,
        ]);
    }

    public static function pakaiPenukaran(string $kode): ?array
    {
        $semua = self::ambil('penukaran');
        foreach ($semua as $id => $p) {
            if ($p['kode'] === strtoupper(trim($kode))) {
                $sebelum = $p['dipakai'];
                $semua[$id]['dipakai'] = true;
                self::simpan('penukaran', $semua);

                return $p + ['sudah_dipakai' => $sebelum];
            }
        }

        return null;
    }

    // ---------- rencana tersimpan ----------
    public static function rencana(?string $email = null): array
    {
        return array_reverse(array_filter(self::ambil('rencana'), fn ($r) => $email === null || $r['email'] === $email));
    }

    public static function simpanRencana(array $data): array
    {
        $data['kode'] = 'mdn-'.strtolower(Str::random(4));

        return self::tambah('rencana', $data);
    }

    public static function cariRencana(string $kode): ?array
    {
        foreach (self::ambil('rencana') as $r) {
            if ($r['kode'] === $kode) {
                return $r;
            }
        }

        return null;
    }
}
