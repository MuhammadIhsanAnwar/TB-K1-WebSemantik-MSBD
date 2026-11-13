<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Facade katalog. Seluruh entitas, relasi, dan jalur dibaca dari Database 3 SPARQL.
 */
class Katalog
{
    public const KELAS = [
        'tempat' => 'Tempat',
        'tokoh' => 'Tokoh',
        'kerajaan' => 'Kerajaan',
        'peristiwa' => 'Peristiwa',
        'budaya' => 'Budaya',
        'kuliner' => 'Kuliner',
        'transportasi' => 'Transportasi',
        'wilayah' => 'Wilayah',
    ];

    public const KATEGORI = [
        'sejarah' => 'Sejarah & cagar budaya',
        'museum' => 'Museum',
        'religi' => 'Rumah ibadah',
        'kuliner' => 'Kuliner',
        'ruang-publik' => 'Taman & ruang publik',
        'belanja' => 'Belanja',
        'rekreasi-air' => 'Rekreasi air',
    ];

    public const KALIMAT = [
        'dibangunOleh' => ['{a} dibangun oleh {b}.', '{a} membangun {b}.'],
        'memimpin' => ['{a} memimpin {b}.', '{a} dipimpin oleh {b}.'],
        'terlibatDalam' => ['{a} hidup dan berperan pada masa {b}.', 'Pada masa {a}, {b} ikut berperan.'],
        'merepresentasikan' => ['{a} mencerminkan budaya {b}.', 'Budaya {a} tercermin di {b}.'],
        'khasDari' => ['{a} adalah kuliner khas {b}.', 'Salah satu kuliner khas {a} adalah {b}.'],
        'dekatDengan' => ['{a} hanya {jarak} meter dari {b}.', '{a} hanya {jarak} meter dari {b}.'],
        'bergaya' => ['{a} memakai gaya {b}.', 'Gaya {a} juga terlihat di {b}.'],
        'bagianDari' => ['{a} berada di dalam kompleks {b}.', 'Di dalam kompleks {a} ada {b}.'],
        'berkaitanDengan' => ['{a} berkaitan dengan {b}.', '{a} berkaitan dengan {b}.'],
        'memilikiSejarah' => ['Sejarah {a} dimulai dari {b}.', '{a} melahirkan {b}.'],
        'menjual' => ['{a} menjual {b}.', '{a} bisa dicicipi di {b}.'],
        'dilayaniOleh' => ['{a} bisa dicapai dengan {b}.', '{a} biasa mengantar pengunjung ke {b}.'],
        'beradaDi' => ['{a} berada di Kecamatan {b}.', 'Kecamatan {a} menjadi tempat {b}.'],
    ];

    public const LABEL_RELASI = [
        'dibangunOleh' => 'dibangun oleh',
        'memimpin' => 'memimpin',
        'terlibatDalam' => 'terlibat dalam',
        'merepresentasikan' => 'mencerminkan',
        'khasDari' => 'khas dari',
        'dekatDengan' => 'dekat dengan',
        'bergaya' => 'bergaya',
        'bagianDari' => 'bagian dari',
        'berkaitanDengan' => 'berkaitan dengan',
        'memilikiSejarah' => 'punya sejarah',
        'menjual' => 'menjual',
        'dilayaniOleh' => 'dilayani oleh',
        'beradaDi' => 'berada di',
    ];

    public const LABEL_BALIK = [
        'dibangunOleh' => 'membangun',
        'memimpin' => 'dipimpin oleh',
        'terlibatDalam' => 'melibatkan',
        'merepresentasikan' => 'tercermin di',
        'khasDari' => 'punya kuliner khas',
        'dekatDengan' => 'dekat dengan',
        'bergaya' => 'terlihat di',
        'bagianDari' => 'mencakup',
        'berkaitanDengan' => 'berkaitan dengan',
        'memilikiSejarah' => 'melahirkan',
        'menjual' => 'dijual di',
        'dilayaniOleh' => 'mengantar ke',
        'beradaDi' => 'menjadi tempat',
    ];

    private static ?array $entities = null;
    private static ?array $relations = null;
    private static ?array $routes = null;

    public static function forgetCache(): void
    {
        self::$entities = self::$relations = self::$routes = null;
        Database3Catalog::forgetCache();
    }

    public static function entitas(): array
    {
        return self::$entities ??= Database3Catalog::entities();
    }

    public static function relasi(): array
    {
        if (self::$relations !== null) {
            return self::$relations;
        }

        $relations = Database3Catalog::relations();
        foreach ($relations as &$relation) {
            if ($relation[1] === 'dekatDengan') {
                $a = self::get($relation[0]);
                $b = self::get($relation[2]);
                if (! $a || ! $b) {
                    throw new RuntimeException('Relasi SPARQL merujuk entitas yang tidak ditemukan.');
                }
                $relation['jarak_m'] = self::jarakMeter($a, $b);
            }
        }

        return self::$relations = $relations;
    }

    public static function get(string $slug): ?array
    {
        return self::entitas()[$slug] ?? null;
    }

    public static function jenis(string $jenis): array
    {
        return array_filter(self::entitas(), fn ($entity) => $entity['jenis'] === $jenis);
    }

    public static function tempat(?string $kategori = null): array
    {
        return array_filter(self::jenis('tempat'), fn ($place) => $kategori === null || $place['kategori'] === $kategori);
    }

    public static function bertiket(): array
    {
        return array_filter(self::tempat(), fn ($place) => ! empty($place['tiket']));
    }

    public static function hargaTermurah(array $tempat): ?int
    {
        return empty($tempat['tiket']) ? null : (int) min($tempat['tiket']);
    }

    public static function tetangga(string $slug, bool $denganWilayah = true): array
    {
        $hasil = [];
        foreach (self::relasi() as $i => $relation) {
            [$subjek, $predikat, $objek] = $relation;
            if ($subjek !== $slug && $objek !== $slug) {
                continue;
            }
            $lawan = $subjek === $slug ? $objek : $subjek;
            $entitasLawan = self::get($lawan);
            if (! $entitasLawan || (! $denganWilayah && $entitasLawan['jenis'] === 'wilayah')) {
                continue;
            }
            $hasil[] = [
                'id' => $i,
                'predikat' => $predikat,
                'maju' => $subjek === $slug,
                'lawan' => $lawan,
                'relasi' => $relation,
            ];
        }

        return $hasil;
    }

    public static function benangMerah(string $dari, string $ke): ?array
    {
        if (! self::get($dari) || ! self::get($ke)) {
            return null;
        }

        $antre = [$dari];
        $asal = [$dari => null];
        while ($antre) {
            $kini = array_shift($antre);
            if ($kini === $ke) {
                break;
            }
            foreach (self::tetangga($kini, false) as $tetangga) {
                if (! array_key_exists($tetangga['lawan'], $asal)) {
                    $asal[$tetangga['lawan']] = [$kini, $tetangga];
                    $antre[] = $tetangga['lawan'];
                }
            }
        }
        if (! array_key_exists($ke, $asal)) {
            return null;
        }

        $langkah = [];
        for ($node = $ke; $asal[$node] !== null; $node = $asal[$node][0]) {
            [$sebelum, $tetangga] = $asal[$node];
            $langkah[] = [
                'dari' => $sebelum,
                'ke' => $node,
                'kalimat' => self::kalimat($tetangga['relasi'], $sebelum),
                'relasi' => $tetangga['relasi'],
            ];
        }

        return array_reverse($langkah);
    }

    public static function labelRelasi(array $relasi, string $sudut): string
    {
        $labels = $relasi[0] === $sudut ? self::LABEL_RELASI : self::LABEL_BALIK;
        if (! isset($labels[$relasi[1]])) {
            throw new RuntimeException('Label relasi SPARQL tidak dikenali: '.$relasi[1]);
        }

        return $labels[$relasi[1]].(isset($relasi['jarak_m']) ? ' '.$relasi['jarak_m'].' m' : '');
    }

    public static function kalimat(array $relasi, string $sudut): string
    {
        [$subjek, $predikat, $objek] = $relasi;
        if (isset($relasi['kalimat']) && $sudut === $subjek) {
            return $relasi['kalimat'];
        }
        if (! isset(self::KALIMAT[$predikat])) {
            throw new RuntimeException('Kalimat relasi SPARQL tidak dikenali: '.$predikat);
        }

        $maju = $subjek === $sudut;
        $a = self::get($maju ? $subjek : $objek)['nama'];
        $b = self::get($maju ? $objek : $subjek)['nama'];
        $pola = self::KALIMAT[$predikat][$maju ? 0 : 1];

        return strtr($pola, [
            '{a}' => $a,
            '{b}' => $b,
            '{jarak}' => number_format($relasi['jarak_m'] ?? 0, 0, ',', '.'),
        ]);
    }

    public static function jarakMeter(array $a, array $b): int
    {
        $radiusBumi = 6371000;
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLng = deg2rad($b['lng'] - $a['lng']);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLng / 2) ** 2;

        return (int) round(2 * $radiusBumi * asin(sqrt($h)));
    }

    /** Rute jalan sungguhan (OSRM/Valhalla), disimpan 30 hari karena letak tempat jarang berubah. */
    private static function ambilRuteApi(array $a, array $b, string $moda): ?array
    {
        $kunci = 'medanvisit.rute.'.$moda.'.'.md5($a['lat'].','.$a['lng'].';'.$b['lat'].','.$b['lng']);
        if (is_array($simpan = Cache::get($kunci))) {
            return $simpan;
        }
        $hasil = self::mintaRuteApi($a, $b, $moda);
        if ($hasil !== null) {
            Cache::put($kunci, $hasil, now()->addDays(30));
        }

        return $hasil;
    }

    private static function mintaRuteApi(array $a, array $b, string $moda): ?array
{
    try {
        // batas pendek: kalau layanan rute lambat, perkiraan garis lurus dipakai
        $timeout = min((int) config('services.routing.timeout', 15), 6);

        if ($moda === 'jalan') {
            $url = rtrim(config('services.routing.valhalla_url'), '/').'/route';

            $response = Http::connectTimeout(3)->timeout($timeout)
                ->acceptJson()
                ->post($url, [
                    'locations' => [
                        [
                            'lat' => (float) $a['lat'],
                            'lon' => (float) $a['lng'],
                        ],
                        [
                            'lat' => (float) $b['lat'],
                            'lon' => (float) $b['lng'],
                        ],
                    ],
                    'costing' => 'pedestrian',
                    'units' => 'kilometers',
                    'format' => 'osrm',
                ]);
        } else {
            $url = rtrim(config('services.routing.osrm_url'), '/')
                .'/route/v1/driving/'
                .$a['lng'].','.$a['lat'].';'
                .$b['lng'].','.$b['lat'];

            $response = Http::connectTimeout(3)->timeout($timeout)
                ->acceptJson()
                ->get($url, [
                    'overview' => 'false',
                    'steps' => 'false',
                ]);
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $rute = $data['routes'][0] ?? null;

        if (! $rute || ! isset($rute['distance'], $rute['duration'])) {
            return null;
        }

        return [
            'meter' => (int) round($rute['distance']),
            'menit' => max(1, (int) round($rute['duration'] / 60)),
        ];
    } catch (\Throwable $e) {
        report($e);

        return null;
    }
}

    public static function perpindahan(array $a, array $b, array $moda): array
    {
        $meter = self::jarakMeter($a, $b);
        if (($meter <= 700 && in_array('jalan', $moda, true)) || ! in_array('becak', $moda, true)) {
            return ['moda' => 'Jalan kaki', 'meter' => $meter, 'menit' => max(1, (int) round($meter / 75)), 'biaya' => 0];
        }

        return [
            'moda' => 'Becak motor',
            'meter' => $meter,
            'menit' => max(3, (int) round($meter / 300) + 2),
            'biaya' => 10000 + (int) (ceil(max(0, $meter - 1000) / 1000) * 5000),
        ];
    }

    private static function perpindahanDenganRute(
    array $a,
    array $b,
    array $moda
): array {
    $hasil = self::perpindahan($a, $b, $moda);

    $jenisModa = $hasil['moda'] === 'Jalan kaki'
        ? 'jalan'
        : 'becak';

    $rute = self::ambilRuteApi($a, $b, $jenisModa);

    if ($rute === null) {
        return $hasil;
    }

    $meter = $rute['meter'];

    return [
        'moda' => $hasil['moda'],
        'meter' => $meter,
        'menit' => $rute['menit'],
        'biaya' => $jenisModa === 'jalan'
            ? 0
            : 10000 + (int) (
                ceil(max(0, $meter - 1000) / 1000) * 5000
            ),
    ];
}

    public static function jalurTematik(): array
    {
        if (self::$routes !== null) {
            return self::$routes;
        }

        $routes = Database3Catalog::routes();
        foreach ($routes as &$route) {
            $meter = 0;
            $menit = 0;
            $sebelumnya = null;
            foreach ($route['singgah'] as $slug) {
                $tempat = self::get($slug);
                if (! $tempat || $tempat['jenis'] !== 'tempat') {
                    throw new RuntimeException('Jalur SPARQL merujuk tempat yang tidak ditemukan: '.$slug);
                }
                $menit += (int) ($tempat['singgah'] ?? 45);
                if ($sebelumnya !== null) {
                    $perpindahan = self::perpindahan($sebelumnya, $tempat, ['jalan', 'becak']);
                    $meter += $perpindahan['meter'];
                    $menit += $perpindahan['menit'];
                }
                $sebelumnya = $tempat;
            }
            $route['meter'] = $meter;
            $route['menit'] = $menit;
        }

        return self::$routes = $routes;
    }


    /**
     * Susun rute satu hari dari tempat awal: setiap langkah memilih tempat terdekat yang cocok dengan minat,
     * masih muat dalam sisa waktu, dan buka saat didatangi (jam buka dari Database 3).
     *
     * Calon diurutkan dengan jarak garis lurus, jadi layanan rute online hanya dipanggil sekali per langkah
     * untuk tempat yang terpilih. Kalau layanan itu gagal, perkiraan garis lurus dipakai.
     */
    public static function susunRute(array $minat, int $durasiJam, array $moda, string $mulai, ?\DateTimeInterface $tanggal = null): array
    {
        $awal = self::get($mulai);
        if (! $awal || $awal['jenis'] !== 'tempat') {
            throw new RuntimeException('Pilih tempat awal dari daftar tempat wisata.');
        }
        if ($durasiJam <= 0) {
            throw new RuntimeException('Durasi perjalanan harus lebih dari 0 jam.');
        }

        $pool = array_filter(self::tempat(), fn ($t) => in_array($t['kategori'], $minat, true) && $t['slug'] !== $mulai);
        $hari = (int) \Illuminate\Support\Carbon::instance($tanggal ?? now())->setTimezone('Asia/Jakarta')->isoFormat('E');

        // mulai pukul 08.30, atau saat tempat awal buka kalau lebih siang
        $jam = 8 * 60 + 30;
        $jadwalAwal = self::jadwal($awal);
        if ($jadwalAwal && in_array($hari, $jadwalAwal['hari'], true) && $jadwalAwal['menit'][0] < $jadwalAwal['menit'][1]) {
            $jam = max($jam, $jadwalAwal['menit'][0]);
        }

        $sisaMenit = $durasiJam * 60;
        $durasiAwal = (int) ($awal['singgah'] ?? 45);
        if ($durasiAwal > $sisaMenit) {
            throw new RuntimeException('Singgah di '.$awal['nama'].' butuh sekitar '.$durasiAwal.' menit, lebih lama dari waktu yang dipilih. Pilih waktu yang lebih panjang.');
        }

        $urut = [['tempat' => $awal, 'mulai' => $jam, 'pindah' => null]];
        $sisaMenit -= $durasiAwal;
        $jam += $durasiAwal;
        $total = self::hargaTermurah($awal) ?? 0;
        $kini = $awal;

        while ($pool) {
            $jarak = array_map(fn ($t) => self::jarakMeter($kini, $t), $pool);
            asort($jarak);
            $slugCalon = null;
            foreach ($jarak as $slug => $meter) {
                if ($meter > 6000) {
                    break;
                }
                $kira = self::perpindahan($kini, $pool[$slug], $moda);
                $singgah = (int) ($pool[$slug]['singgah'] ?? 45);
                if ($kira['menit'] + $singgah <= $sisaMenit && self::bukaSelama($pool[$slug], $hari, $jam + $kira['menit'], $singgah)) {
                    $slugCalon = $slug;
                    break;
                }
            }
            if ($slugCalon === null) {
                break;
            }

            $calon = $pool[$slugCalon];
            unset($pool[$slugCalon]);
            $perpindahan = self::perpindahanDenganRute($kini, $calon, $moda);
            $durasiSinggah = (int) ($calon['singgah'] ?? 45);
            // rute jalan sungguhan bisa lebih lama dari perkiraan garis lurus: periksa ulang
            if ($perpindahan['menit'] + $durasiSinggah > $sisaMenit || ! self::bukaSelama($calon, $hari, $jam + $perpindahan['menit'], $durasiSinggah)) {
                continue;
            }

            $sisaMenit -= $perpindahan['menit'] + $durasiSinggah;
            $jam += $perpindahan['menit'];
            $urut[] = ['tempat' => $calon, 'mulai' => $jam, 'pindah' => $perpindahan];
            $jam += $durasiSinggah;
            $total += $perpindahan['biaya'] + (self::hargaTermurah($calon) ?? 0);
            $kini = $calon;
        }

        return [
            'singgah' => $urut,
            'biaya' => $total,
            'selesai' => $jam,
        ];
    }

    /** Tempat buka saat tiba dan belum tutup sampai kunjungan selesai. Jam buka yang tidak diketahui tidak menghalangi. */
        throw new RuntimeException('Durasi perjalanan harus lebih dari 0 jam.');
    }

    $sisaMenit = $durasiJam * 60;
    $urut = [];
    $kini = $awal;
    $jam = 8 * 60 + 30;
    $total = 0;

    unset($pool[$awal['slug']]);

    $durasiAwal = (int) ($awal['singgah'] ?? 45);

    if ($durasiAwal > $sisaMenit) {
        throw new RuntimeException('Durasi kunjungan tempat awal melebihi durasi perjalanan.');
    }

    $urut[] = [
        'tempat' => $awal,
        'mulai' => $jam,
        'pindah' => null,
    ];

    $sisaMenit -= $durasiAwal;
    $jam += $durasiAwal;
    $total += self::hargaTermurah($awal) ?? 0;

    while ($pool) {
        $kandidat = [];

        foreach ($pool as $slug => $tempat) {
            $perpindahan = self::perpindahanDenganRute(
                $kini,
                $tempat,
                $moda
            );

            $kandidat[$slug] = [
                'tempat' => $tempat,
                'pindah' => $perpindahan,
            ];
        }

        uasort(
            $kandidat,
            fn ($a, $b) => $a['pindah']['meter'] <=> $b['pindah']['meter']
        );

        $slugCalon = array_key_first($kandidat);
        $calon = $kandidat[$slugCalon]['tempat'];
        $perpindahan = $kandidat[$slugCalon]['pindah'];

        $durasiSinggah = (int) ($calon['singgah'] ?? 45);

        if (
            $perpindahan['menit'] + $durasiSinggah > $sisaMenit
            || $perpindahan['meter'] > 6000
        ) {
            break;
        }

        $sisaMenit -= $perpindahan['menit'] + $durasiSinggah;

        $jam += $perpindahan['menit'];

        $urut[] = [
            'tempat' => $calon,
            'mulai' => $jam,
            'pindah' => $perpindahan,
        ];

        $jam += $durasiSinggah;

        $total += $perpindahan['biaya']
            + (self::hargaTermurah($calon) ?? 0);

        unset($pool[$slugCalon]);

        $kini = $calon;
    }

    return [
        'singgah' => $urut,
        'biaya' => $total,
        'selesai' => $jam,
    ];
}

    public static function jamTeks(int $menitDariTengahMalam): string
    {
        return sprintf('%02d.%02d', intdiv($menitDariTengahMalam, 60) % 24, $menitDariTengahMalam % 60);
    }

    public static function rupiah(?int $nilai): string
    {
        return $nilai === null ? 'Gratis' : 'Rp'.number_format($nilai, 0, ',', '.');
    }

    private const HARI = ['senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4, 'jumat' => 5, 'sabtu' => 6, 'minggu' => 7];

    /**
     * Jadwal buka sebuah tempat: jam buka dan tutup dalam menit, dan hari buka (1 = Senin ... 7 = Minggu).
     * Hari dibaca dari keterangan jam, misalnya "Selasa–Minggu, 09.00–16.00 (Senin tutup)". Nama hari hanya
     * dihitung kalau diikuti rentang jam, jadi "Di luar jam misa (Minggu 06.30, ...)" tidak membatasi hari.
     * Tanpa jam terstruktur hasilnya null, artinya jam buka tidak diketahui.
     */
    public static function jadwal(array $tempat): ?array
    {
        $menit = $tempat['jam_menit'] ?? null;
        if (! $menit) {
            return null;
        }
        $teks = mb_strtolower($tempat['jam'] ?? '');
        $nama = implode('|', array_keys(self::HARI));
        $hari = [];
        if (! str_contains($teks, 'setiap hari')) {
            preg_match_all("/\b($nama)(?:\s*[–-]\s*($nama))?,?\s+\d{1,2}\.\d{2}\s*[–-]/u", $teks, $cocok, PREG_SET_ORDER);
            foreach ($cocok as $c) {
                $awal = self::HARI[$c[1]];
                $akhir = isset($c[2]) && $c[2] !== '' ? self::HARI[$c[2]] : $awal;
                for ($h = $awal, $n = 0; $n < 7; $h = $h % 7 + 1, $n++) {
                    $hari[$h] = true;
                    if ($h === $akhir) {
                        break;
                    }
                }
            }
        }
        $hari = $hari ? array_keys($hari) : range(1, 7);
        preg_match_all("/\b($nama)\s+tutup\b/u", $teks, $tutup);
        $hari = array_values(array_diff($hari, array_map(fn ($h) => self::HARI[$h], $tutup[1])));
        sort($hari);

        return ['menit' => $menit, 'hari' => $hari];
    }

    /** Apakah tempat sedang buka, menurut jam WIB. Jam yang lewat tengah malam (tutup < buka) ikut dihitung. */
    public static function bukaSekarang(array $tempat, ?\DateTimeInterface $waktu = null): bool
    {
        $jadwal = self::jadwal($tempat);
        if (! $jadwal) {
            return false;
        }
        $waktu = \Illuminate\Support\Carbon::instance($waktu ?? now())->setTimezone('Asia/Jakarta');
        $hari = (int) $waktu->isoFormat('E');
        $sekarang = $waktu->hour * 60 + $waktu->minute;
        [$buka, $tutup] = $jadwal['menit'];
        if ($buka === $tutup) {
            return in_array($hari, $jadwal['hari'], true);
        }
        if ($tutup > $buka) {
            return in_array($hari, $jadwal['hari'], true) && $sekarang >= $buka && $sekarang < $tutup;
        }
        $kemarin = $hari === 1 ? 7 : $hari - 1;

        return (in_array($hari, $jadwal['hari'], true) && $sekarang >= $buka)
            || (in_array($kemarin, $jadwal['hari'], true) && $sekarang < $tutup);
    }

    public static function cari(string $q): array
    {
        $q = mb_strtolower(trim($q));
        if ($q === '') {
            return [];
        }

        $hasil = [];
        foreach (self::entitas() as $slug => $entity) {
            if ($entity['jenis'] === 'wilayah') {
                continue;
            }
            $teks = mb_strtolower($entity['nama'].' '.($entity['ringkas'] ?? '').' '.($entity['kecamatan'] ?? '').' '.($entity['kategori'] ?? ''));
            $nama = mb_strtolower($entity['nama']);
            $skor = 0;
            foreach (preg_split('/\s+/', $q) as $kata) {
                if (mb_strlen($kata) < 3) {
                    continue;
                }
                if (str_contains($teks, $kata)) {
                    $skor += 2;
                } else {
                    foreach (preg_split('/[\s\-]+/', $nama) as $bagianNama) {
                        if (mb_strlen($bagianNama) >= 4 && levenshtein($kata, $bagianNama) <= 1) {
                            $skor += 1.5;
                        }
                    }
                }
            }
            similar_text($q, $nama, $persen);
            $skor += $persen / 50;
            if ($skor >= 1.6) {
                $hasil[$slug] = $skor;
            }
        }
        arsort($hasil);

        return array_map(fn ($slug) => self::get($slug), array_keys($hasil));
    }

    public static function koreksi(string $q): ?string
    {
        $q = mb_strtolower(trim($q));
        $terbaik = null;
        $jarak = PHP_INT_MAX;
        foreach (self::entitas() as $entity) {
            $jarakNama = levenshtein($q, mb_strtolower($entity['nama']));
            if ($jarakNama < $jarak) {
                [$jarak, $terbaik] = [$jarakNama, $entity['nama']];
            }
        }

        return $jarak > 0 && $jarak <= 3 ? $terbaik : null;
    }

    public static function kredit(?string $berkas = null): array|string|null
    {
        static $kredit = null;
        if ($kredit === null) {
            $path = public_path('images/foto/credits.json');
            $kredit = [];
            foreach (is_file($path) ? json_decode(file_get_contents($path), true) : [] as $item) {
                $kredit[basename($item['file'])] = $item;
            }
        }

        return $berkas === null ? $kredit : ($kredit[$berkas]['credit'] ?? null);
    }

    public static function posisi1930(array $tempat): ?array
    {
        if (! isset($tempat['lat'], $tempat['lng'])) {
            return null;
        }
        $x = 818 + ($tempat['lng'] - 98.6837) * 31110;
        $y = 1150 - ($tempat['lat'] - 3.5752) * 30960;

        return ($x > 80 && $x < 1340 && $y > 300 && $y < 1470) ? ['x' => round($x), 'y' => round($y)] : null;
    }

    public static function posisiKotaLama(array $tempat): ?array
    {
        $posisi = self::posisi1930($tempat);
        if (! $posisi) {
            return null;
        }
        $x = ($posisi['x'] - 480) / 560 * 100;
        $y = ($posisi['y'] - 630) / 560 * 100;

        return ($x >= 0 && $x <= 100 && $y >= 0 && $y <= 100) ? ['x' => round($x, 2), 'y' => round($y, 2)] : null;
    }
}
