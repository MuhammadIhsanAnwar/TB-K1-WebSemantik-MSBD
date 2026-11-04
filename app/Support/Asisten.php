<?php

namespace App\Support;

use App\Http\Controllers\SitusController;

/**
 * Asisten Tanya Medan, versi uji tanpa model bahasa: mengenali maksud pertanyaan dari kata kunci,
 * lalu menyusun jawaban dari katalog (harga, jarak, kategori, relasi). Nanti bisa diganti model bahasa
 * yang menerima konteks dari query SPARQL yang sama.
 */
class Asisten
{
    public static function jawab(string $pesan): array
    {
        $p = mb_strtolower($pesan);
        $tempatDisebut = self::tempatDisebut($p);

        if (preg_match('/\b(tiket|harga|bayar|biaya)\b/', $p)) {
            return self::tiket($tempatDisebut);
        }
        if ($tempatDisebut && preg_match('/\b(dekat|sekitar|sebelah|jalan kaki)\b/', $p)) {
            return self::dekat($tempatDisebut, $p);
        }
        if (preg_match('/\b(rute|rencana|setengah hari|seharian|itinerary|jadwal)\b/', $p)) {
            return self::rute();
        }
        if (preg_match('/\b(makan|kuliner|kue|oleh-oleh|soto|bubur)\b/', $p)) {
            return self::kuliner($p);
        }
        if (preg_match('/\b(ibadah|masjid|kuil|vihara|gereja|kelenteng|religi)\b/', $p)) {
            return self::daftar('religi', 'Rumah ibadah yang terbuka untuk pengunjung di Medan:');
        }
        if (preg_match('/\b(museum|sejarah|istana|kolonial|tempo dulu)\b/', $p) && ! $tempatDisebut) {
            // kata kunci tetap dicatat, supaya "museum perjuangan" yang belum ada muncul di celah konten kurator
            Penyimpanan::catatCari($pesan, count(Katalog::cari($pesan)));
            $museum = str_contains($p, 'museum');

            return self::daftar($museum ? 'museum' : 'sejarah', $museum ? 'Museum yang tercatat di MedanVisit:' : 'Tempat bersejarah yang bisa kamu datangi:');
        }
        if ($tempatDisebut) {
            return self::tentang($tempatDisebut);
        }

        $hasil = Katalog::cari($pesan);
        Penyimpanan::catatCari($pesan, count($hasil));
        if ($hasil) {
            return [
                'teks' => ['Ini yang paling cocok dengan pertanyaanmu:'],
                'tempat' => array_map([self::class, 'kartu'], array_slice($hasil, 0, 3)),
            ];
        }

        return [
            'teks' => [
                'Aku belum menemukan jawabannya di data MedanVisit. Aku hanya bisa menjawab soal tempat wisata, kuliner, rute, dan tiket di Kota Medan.',
                'Coba tanyakan misalnya "kuliner dekat Istana Maimun" atau "berapa harga tiket Tjong A Fie Mansion".',
            ],
            'tempat' => [],
        ];
    }

    private static function tempatDisebut(string $p): ?array
    {
        $cocok = null;
        foreach (Katalog::entitas() as $e) {
            $nama = mb_strtolower($e['nama']);
            $inti = trim(str_replace(['deretan ', ' jl. mojopahit', ' al-mashun'], '', $nama));
            if (str_contains($p, $nama) || (mb_strlen($inti) > 5 && str_contains($p, $inti))) {
                if (! $cocok || mb_strlen($e['nama']) > mb_strlen($cocok['nama'])) {
                    $cocok = $e;
                }
            }
        }
        // "tjong a fie" bisa berarti tokohnya atau rumahnya: untuk pertanyaan wisata, utamakan tempat yang memuat nama itu
        if ($cocok && $cocok['jenis'] !== 'tempat') {
            foreach (Katalog::tempat() as $t) {
                if (str_contains(mb_strtolower($t['nama']), mb_strtolower($cocok['nama']))) {
                    $cocok = $t;
                    break;
                }
            }
        }
        if (! $cocok && preg_match('/\b(istana|maimun)\b/', $p)) {
            $cocok = Katalog::get('istana-maimun');
        }
        if (! $cocok && str_contains($p, 'tjong')) {
            $cocok = Katalog::get('tjong-a-fie-mansion');
        }

        return $cocok;
    }

    public static function kartu(array $e, ?int $jarak = null): array
    {
        return [
            'nama' => $e['nama'],
            'jenis' => Katalog::KELAS[$e['jenis']],
            'keterangan' => $e['ringkas'] ?? '',
            'foto' => ! empty($e['foto']) ? asset('images/foto/'.$e['foto']) : null,
            'url' => SitusController::urlEntitas($e),
            'harga' => $e['jenis'] === 'tempat' ? Katalog::rupiah(Katalog::hargaTermurah($e)) : null,
            'tiket' => ! empty($e['tiket']) ? route('tiket.pilih', $e['slug']) : null,
            'jarak' => $jarak,
        ];
    }

    private static function tiket(?array $t): array
    {
        if ($t && $t['jenis'] === 'tempat') {
            if (empty($t['tiket'])) {
                return ['teks' => [$t['nama'].' tidak memungut tiket masuk.'], 'tempat' => [self::kartu($t)]];
            }
            $rincian = collect($t['tiket'])->map(fn ($h, $j) => $j.' '.Katalog::rupiah($h))->implode(', ');

            return [
                'teks' => ['Tiket '.$t['nama'].': '.$rincian.'. Harga ini masih contoh dan akan dicek ulang ke pengelola.', 'Kamu bisa memesan dan langsung mendapat e-tiket ber-QR.'],
                'tempat' => [self::kartu($t)],
            ];
        }

        return [
            'teks' => ['Tempat yang memakai tiket masuk dan bisa dipesan di MedanVisit:'],
            'tempat' => array_map([self::class, 'kartu'], array_values(Katalog::bertiket())),
        ];
    }

    private static function dekat(array $t, string $p): array
    {
        $kategori = preg_match('/\b(makan|kuliner|soto|kue)\b/', $p) ? 'kuliner' : null;
        $lain = array_filter(Katalog::tempat($kategori), fn ($x) => $x['slug'] !== $t['slug']);
        $lain = array_map(fn ($x) => [$x, Katalog::jarakMeter($t, $x)], $lain);
        usort($lain, fn ($a, $b) => $a[1] <=> $b[1]);
        $lain = array_slice($lain, 0, 3);

        return [
            'teks' => ['Yang paling dekat dari '.$t['nama'].($kategori ? ' untuk makan' : '').':'],
            'tempat' => array_map(fn ($x) => self::kartu($x[0], $x[1]), $lain),
        ];
    }

    private static function rute(): array
    {
        $r = Katalog::susunRute(['sejarah', 'religi', 'kuliner'], 4, ['jalan', 'becak'], 'istana-maimun');
        $baris = array_map(fn ($s) => Katalog::jamTeks($s['mulai']).' '.$s['tempat']['nama'], $r['singgah']);

        return [
            'teks' => ['Rute setengah hari dari Istana Maimun, jalan kaki dan becak motor:', implode(' · ', $baris), 'Perkiraan biaya tiket dan becak '.Katalog::rupiah($r['biaya']).' per orang. Kamu bisa mengubah minat dan durasinya di Perencana Rute.'],
            'tempat' => [],
            'tautan' => ['label' => 'Buka Perencana Rute', 'url' => route('rencana', ['minat' => ['sejarah', 'religi', 'kuliner'], 'durasi' => 4, 'moda' => ['jalan', 'becak'], 'mulai' => 'istana-maimun'])],
        ];
    }

    private static function kuliner(string $p): array
    {
        if (str_contains($p, 'melayu')) {
            $k = array_values(array_filter(Katalog::jenis('kuliner'), fn ($e) => collect(Katalog::tetangga($e['slug']))->contains(fn ($t) => $t['lawan'] === 'melayu-deli')));

            return ['teks' => ['Kuliner khas Melayu Deli yang tercatat di MedanVisit:'], 'tempat' => array_map([self::class, 'kartu'], $k)];
        }

        return self::daftar('kuliner', 'Tempat makan dan oleh-oleh yang tercatat di MedanVisit:');
    }

    private static function daftar(string $kategori, string $pembuka): array
    {
        return ['teks' => [$pembuka], 'tempat' => array_map([self::class, 'kartu'], array_values(Katalog::tempat($kategori)))];
    }

    private static function tentang(array $e): array
    {
        $teks = [$e['ringkas'] ?? $e['nama']];
        foreach (array_slice(Katalog::tetangga($e['slug'], false), 0, 3) as $t) {
            $teks[] = Katalog::kalimat($t['relasi'], $e['slug']);
        }
        if ($e['jenis'] === 'tempat') {
            $teks[] = 'Jam buka: '.$e['jam'].(! empty($e['jam_contoh']) ? ' (contoh)' : '').'.';
        }

        return ['teks' => $teks, 'tempat' => [self::kartu($e)]];
    }
}
