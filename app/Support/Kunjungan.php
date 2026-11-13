<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class Kunjungan
{
    public static function catat(string $rute, ?string $slugTempat = null): void
    {
        $tanggal = now('Asia/Jakarta')->toDateString();
        self::tambah("medanvisit.kunjungan.situs.$tanggal");
        self::tambahDimensi("medanvisit.kunjungan.rute.$tanggal", $rute);
        if ($slugTempat !== null) {
            self::tambahDimensi("medanvisit.kunjungan.tempat.$tanggal", $slugTempat);
        }
    }

    public static function ringkasan(int $hari): array
    {
        $harian = [];
        $rute = [];
        $tempat = [];

        for ($i = $hari - 1; $i >= 0; $i--) {
            $tanggal = now('Asia/Jakarta')->subDays($i)->toDateString();
            $harian[$tanggal] = (int) Cache::get("medanvisit.kunjungan.situs.$tanggal", 0);
            foreach (Cache::get("medanvisit.kunjungan.rute.$tanggal", []) as $nama => $jumlah) {
                $rute[$nama] = ($rute[$nama] ?? 0) + $jumlah;
            }
            foreach (Cache::get("medanvisit.kunjungan.tempat.$tanggal", []) as $slug => $jumlah) {
                $tempat[$slug] = ($tempat[$slug] ?? 0) + $jumlah;
            }
        }

        arsort($rute);
        arsort($tempat);

        return ['harian' => $harian, 'rute' => $rute, 'tempat' => $tempat, 'total' => array_sum($harian)];
    }

    /** Tayangan halaman satu tempat wisata per hari, untuk grafik insight tempat itu. */
    public static function harianTempat(string $slug, int $hari): array
    {
        $harian = [];
        for ($i = $hari - 1; $i >= 0; $i--) {
            $tanggal = now('Asia/Jakarta')->subDays($i)->toDateString();
            $harian[$tanggal] = (int) (Cache::get("medanvisit.kunjungan.tempat.$tanggal", [])[$slug] ?? 0);
        }

        return $harian;
    }

    private static function tambah(string $key): void
    {
        Cache::add($key, 0, now('Asia/Jakarta')->addYears(5));
        Cache::increment($key);
    }

    private static function tambahDimensi(string $key, string $dimensi): void
    {
        Cache::lock($key.'.lock', 5)->block(2, function () use ($key, $dimensi): void {
            $jumlah = Cache::get($key, []);
            $jumlah[$dimensi] = ($jumlah[$dimensi] ?? 0) + 1;
            Cache::put($key, $jumlah, now('Asia/Jakarta')->addYears(5));
        });
    }
}
