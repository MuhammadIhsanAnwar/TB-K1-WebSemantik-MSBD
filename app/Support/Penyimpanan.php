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

    /**
     * Mengubah pesanan:
     * - ['status' => 'lunas']        lewat sp_bayar_pesanan (catat pembayaran, terbitkan e-tiket;
     *                                 kalau sudah lewat batas bayar, procedure menandainya kedaluwarsa)
     * - ['status' => 'dibatalkan'|'kedaluwarsa']  hanya untuk pesanan yang masih menunggu
     * - ['dipakai' => waktu]         e-tiket dipindai di lokasi
     */
    public static function ubahPesanan(string $kode, array $ubah): ?array
    {
        $db = self::db();
        if (($ubah['status'] ?? null) === 'lunas') {
            $db->statement('CALL sp_bayar_pesanan(?, ?, NULL)', [$kode, $ubah['referensi'] ?? 'SIMULASI']);
        } elseif (in_array($ubah['status'] ?? null, ['dibatalkan', 'kedaluwarsa'], true)) {
            $db->table('pesanan')->where('kode', $kode)->where('status', 'menunggu')
                ->update(['status' => $ubah['status'], 'updated_at' => now()]);
        }
        if (isset($ubah['dipakai'])) {
            $id = $db->table('pesanan')->where('kode', $kode)->value('id_pesanan');
            $db->table('e_tiket')->where('id_pesanan', $id)->whereNull('dipakai_pada')
                ->update(['dipakai_pada' => Carbon::parse($ubah['dipakai'])]);
        }

        return self::cariPesanan($kode);
    }

    private static function queryPesanan()
    {
        return self::db()->table('pesanan as p')
            ->leftJoin('pembayaran as b', 'b.id_pesanan', '=', 'p.id_pesanan')
            ->leftJoin('e_tiket as e', 'e.id_pesanan', '=', 'p.id_pesanan')
            ->select('p.*', 'b.dibayar_pada', 'b.referensi', 'e.kode_qr', 'e.dipakai_pada');
    }

    /** Baris database menjadi bentuk yang dipakai controller dan view. */
    private static function bentukPesanan(array $baris): array
    {
        if (! $baris) {
            return [];
        }
        $item = self::db()->table('pesanan_item')
            ->whereIn('id_pesanan', array_column($baris, 'id_pesanan'))
            ->orderBy('id_item')->get()->groupBy('id_pesanan');

        return array_map(fn ($p) => [
            'id' => (string) $p->id_pesanan,
            'kode' => $p->kode,
            'id_pengguna' => $p->id_pengguna === null ? null : (int) $p->id_pengguna,
            'pemesan' => ['nama' => $p->nama_pemesan, 'email' => $p->email_pemesan, 'hp' => $p->hp_pemesan],
            'tempat' => $p->tempat_slug,
            'tanggal' => Carbon::parse($p->tanggal_kunjungan)->toDateString(),
            'item' => ($item[$p->id_pesanan] ?? collect())->map(fn ($i) => [
                'jenis' => $i->jenis_pengunjung, 'harga' => (int) $i->harga,
                'jumlah' => (int) $i->jumlah, 'subtotal' => (int) $i->subtotal,
            ])->values()->all(),
            'total' => (int) $p->total,
            'metode' => $p->metode,
            'status' => $p->status,
            'batas_bayar' => self::waktu($p->batas_bayar),
            'waktu' => self::waktu($p->created_at),
            'dibayar' => self::waktu($p->dibayar_pada),
            'kode_qr' => $p->kode_qr,
            'dipakai' => self::waktu($p->dipakai_pada),
        ], $baris);
    }

    // ---------- ulasan ----------

    /** Ulasan (terbaru dulu), bisa disaring per tempat dan status. Rating dihitung trigger ke ringkasan_ulasan. */
    public static function ulasan(?string $tempat = null, ?string $status = null, ?int $idPengguna = null, bool $aman = false): array
    {
        $baca = function () use ($tempat, $status, $idPengguna) {
            $q = self::db()->table('ulasan')->orderByDesc('created_at')->orderByDesc('id_ulasan');
            $tempat === null || $q->where('tempat_slug', $tempat);
            $status === null || $q->where('status', $status);
            $idPengguna === null || $q->where('id_pengguna', $idPengguna);

            return $q->get()->map(fn ($u) => [
                'id' => (string) $u->id_ulasan,
                'id_pengguna' => (int) $u->id_pengguna,
                'tempat' => $u->tempat_slug,
                'nama' => $u->nama_pengguna,
                'email' => $u->email_pengguna,
                'bintang' => (int) $u->bintang,
                'teks' => $u->teks,
                'status' => $u->status,
                'waktu' => self::waktu($u->created_at),
            ])->all();
        };

        return $aman ? self::aman($baca, []) : $baca();
    }

    /** Rating per tempat dari tabel ringkasan_ulasan (diisi trigger ulasan_ringkasan). */
    public static function ringkasanUlasan(): array
    {
        return self::aman(fn () => self::db()->table('ringkasan_ulasan')->get()
            ->mapWithKeys(fn ($r) => [$r->tempat_slug => [
                'rating' => $r->rata_rata_bintang === null ? 0 : (float) $r->rata_rata_bintang,
                'jumlahUlasan' => (int) $r->jumlah_ulasan,
            ]])->all(), []);
    }

    public static function tulisUlasan(array $data): array
    {
        $id = self::db()->table('ulasan')->insertGetId([
            'id_pengguna' => $data['id_pengguna'],
            'tempat_slug' => $data['tempat'],
            'nama_pengguna' => Str::limit($data['nama'] ?? 'Wisatawan', 77),
            'email_pengguna' => $data['email'] ?? null,
            'bintang' => $data['bintang'],
            'teks' => $data['teks'],
            'status' => 'tayang',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_ulasan');

        return ['id' => (string) $id] + $data;
    }

    public static function moderasi(string $id, string $status): void
    {
        self::db()->table('ulasan')->where('id_ulasan', (int) $id)->update(['status' => $status, 'updated_at' => now()]);
    }

    // ---------- log pencarian ----------

    public static function catatCari(string $q, int $jumlahHasil): void
    {
        if (trim($q) === '') {
            return;
        }
        self::aman(fn () => self::db()->table('log_pencarian')->insert([
            'kata_kunci' => Str::limit(mb_strtolower(trim($q)), 117),
            'jumlah_hasil' => min($jumlahHasil, 32767),
        ]), null);
    }

    /** Kata kunci yang dicari tetapi tanpa hasil, dengan jumlahnya (view v_celah_konten). */
    public static function celahKonten(): array
    {
        return self::aman(fn () => self::db()->table('v_celah_konten')
            ->orderByDesc('jumlah_dicari')->pluck('jumlah_dicari', 'kata_kunci')
            ->map(fn ($n) => (int) $n)->all(), []);
    }

    public static function jumlahCari(): int
    {
        return self::aman(fn () => self::db()->table('log_pencarian')->count(), 0);
    }

    // ---------- rencana tersimpan ----------

    public static function rencana(?int $idPengguna = null, bool $aman = false): array
    {
        $baca = function () use ($idPengguna) {
            $q = self::db()->table('rencana')->orderByDesc('created_at')->orderByDesc('id_rencana');
            $idPengguna === null || $q->where('id_pengguna', $idPengguna);

            return self::bentukRencana($q->get()->all());
        };

        return $aman ? self::aman($baca, []) : $baca();
    }

    public static function simpanRencana(array $data): array
    {
        $kode = 'mdn-'.strtolower(Str::random(4));
        self::db()->transaction(function () use ($data, $kode) {
            $id = self::db()->table('rencana')->insertGetId([
                'kode' => $kode,
                'id_pengguna' => $data['id_pengguna'],
                'judul' => $data['judul'],
                'durasi_jam' => (int) $data['durasi'],
                'moda_jalan' => in_array('jalan', $data['moda'], true),
                'moda_becak' => in_array('becak', $data['moda'], true),
                'tempat_mulai_slug' => $data['mulai'],
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_rencana');
            foreach (array_unique($data['minat']) as $kategori) {
                self::db()->table('rencana_minat')->insert(['id_rencana' => $id, 'kode_kategori' => $kategori]);
            }
        });

        return self::cariRencana($kode);
    }

    public static function cariRencana(string $kode): ?array
    {
        return self::bentukRencana(self::db()->table('rencana')->where('kode', $kode)->limit(1)->get()->all())[0] ?? null;
    }

    private static function bentukRencana(array $baris): array
    {
        if (! $baris) {
            return [];
        }
        $minat = self::db()->table('rencana_minat')
            ->whereIn('id_rencana', array_column($baris, 'id_rencana'))->get()->groupBy('id_rencana');

        return array_map(fn ($r) => [
            'id' => (string) $r->id_rencana,
            'kode' => $r->kode,
            'id_pengguna' => (int) $r->id_pengguna,
            'judul' => $r->judul,
            'minat' => ($minat[$r->id_rencana] ?? collect())->pluck('kode_kategori')->all(),
            'durasi' => (int) $r->durasi_jam,
            'moda' => array_keys(array_filter(['jalan' => (bool) $r->moda_jalan, 'becak' => (bool) $r->moda_becak])),
            'mulai' => $r->tempat_mulai_slug,
            'waktu' => self::waktu($r->created_at),
        ], $baris);
    }

    // ---------- fitur tanpa route: masih di cache berkas ----------

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
        $baris += ['id' => (string) Str::ulid(), 'waktu' => now(self::ZONA)->toIso8601String()];
        $isi[$baris['id']] = $baris;
        self::simpan($kunci, $isi);

        return $baris;
    }

    public static function checkin(?string $email = null): array
    {
        return array_reverse(array_filter(self::ambil('checkin'), fn ($c) => $email === null || $c['email'] === $email));
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
