<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use App\Support\Asisten;
use App\Support\Katalog;
use App\Support\Kunjungan;
use App\Support\Penyimpanan;
use Illuminate\Http\Request;

class SitusController extends Controller
{
    public function beranda()
    {
        $tempat = Katalog::tempat();
        $benang = [];
        foreach (Katalog::relasi() as $relasi) {
            $dari = Katalog::get($relasi[0]);
            $ke = Katalog::get($relasi[2]);
            if (! $dari || ! $ke || $dari['jenis'] !== 'tempat' || $ke['jenis'] !== 'tempat') {
                continue;
            }
            $langkah = Katalog::benangMerah($dari['slug'], $ke['slug']);
            if ($langkah) {
                $benang[] = ['dari' => $dari, 'ke' => $ke, 'langkah' => $langkah];
            }
            if (count($benang) === 3) {
                break;
            }
        }

        $kategori = [];
        foreach (Katalog::KATEGORI as $k => $label) {
            $kategori[$k] = ['label' => $label, 'tempat' => Katalog::tempat($k)];
        }

        return view('situs.beranda', [
            'benang' => $benang,
            'kategori' => $kategori,
            'jalur' => Katalog::jalurTematik(),
            'bertiket' => Katalog::bertiket(),
            'petaTempat' => array_values($tempat),
        ]);
    }

    public function cari(Request $request)
    {
        $q = (string) $request->query('q', '');
        $jenis = $request->query('jenis');
        $hasil = Katalog::cari($q);
        if ($request->query('catat', '1') === '1' && $q !== '') {
            Penyimpanan::catatCari($q, count($hasil));
        }
        $perJenis = collect($hasil)->countBy('jenis')->all();
        if ($jenis) {
            $hasil = array_values(array_filter($hasil, fn ($e) => $e['jenis'] === $jenis));
        }

        return view('situs.cari', [
            'q' => $q,
            'jenis' => $jenis,
            'hasil' => $hasil,
            'perJenis' => $perJenis,
            'koreksi' => $hasil ? null : Katalog::koreksi($q),
            'cepat' => array_slice(array_values(Katalog::tempat()), 0, 4),
        ]);
    }

    public function saran(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['saran' => [], 'tanya' => null]);
        }

        // saringan jenis dari dialog pencarian cepat: tempat, sejarah (tokoh, kerajaan, peristiwa, budaya), kuliner
        $jenis = $request->query('jenis');
        $cocok = match ($jenis) {
            'tempat' => fn ($e) => $e['jenis'] === 'tempat',
            'sejarah' => fn ($e) => in_array($e['jenis'], ['tokoh', 'kerajaan', 'peristiwa', 'budaya'], true),
            'kuliner' => fn ($e) => $e['jenis'] === 'kuliner' || ($e['kategori'] ?? null) === 'kuliner',
            default => fn () => true,
        };
        $items = array_slice(array_values(array_filter(Katalog::cari($q), $cocok)), 0, 6);
        $saran = array_map(fn ($e) => [
            'nama' => $e['nama'],
            'jenis' => $e['jenis'],
            'label_jenis' => $e['jenis'] === 'tempat' ? Katalog::KATEGORI[$e['kategori']] : Katalog::KELAS[$e['jenis']],
            'ringkas' => $e['kecamatan'] ?? ($e['ringkas'] ?? ''),
            'harga' => $e['jenis'] === 'tempat' ? Katalog::rupiah(Katalog::hargaTermurah($e)) : null,
            'url' => self::urlEntitas($e),
        ], $items);

        return response()->json([
            'saran' => array_values(array_filter($saran, fn ($x) => ! empty($x['url']))),
            'tanya' => route('tanya', ['q' => $q]),
            'semua' => route('cari', ['q' => $q]),
        ]);
    }

    public function tanya(Request $request)
    {
        $tempat = Katalog::tempat();

        return view('situs.tanya', [
            'awal' => (string) $request->query('q', ''),
            'contohTempat' => array_slice(array_values($tempat), 0, 3),
            'contohTiket' => array_values(Katalog::bertiket())[0] ?? null,
            'contohKuliner' => array_values(Katalog::tempat('kuliner'))[0] ?? null,
        ]);
    }

    public function jawab(Request $request)
    {
        $data = $request->validate(['pesan' => ['required', 'string', 'max:300']]);

        return response()->json(Asisten::jawab($data['pesan']));
    }

        public function peta()
    {
        $tempat = array_values(Katalog::tempat());
        $kunjungan = Kunjungan::ringkasan(365)['tempat'];

        $ulasan = collect(Penyimpanan::ulasan(null, 'tayang'))
            ->groupBy('tempat')
            ->map(fn ($items) => [
                'rating' => round($items->avg('bintang'), 2),
                'jumlahUlasan' => $items->count(),
            ]);

        foreach ($tempat as &$t) {
            $t['rating'] = $ulasan[$t['slug']]['rating'] ?? 0;
            $t['jumlahUlasan'] = $ulasan[$t['slug']]['jumlahUlasan'] ?? 0;
            $t['jumlahKunjungan'] = $kunjungan[$t['slug']] ?? 0;
            $t['jumlahSuka'] = (int) Cache::get('medanvisit.favorit.' . $t['slug'], 0);
        }
        unset($t);

        return view('situs.peta', compact('tempat'));
    }
    public function tempat(string $slug)
{
    $t = Katalog::get($slug);
    abort_unless($t && $t['jenis'] === 'tempat', 404);

    return view('situs.tempat', $this->dataEntitas($t) + [
        'ulasan' => Penyimpanan::ulasan($slug, 'tayang'),
        'ramai' => 0,
        'dekat' => $this->terdekat($t, 4),
    ]);
}
    public function sukai(Request $request, string $slug)
{
    $tempat = Katalog::get($slug);

    abort_unless($tempat && ($tempat['jenis'] ?? null) === 'tempat', 404);

    $disukai = $request->session()->get('tempat_disukai', []);

    if (!in_array($slug, $disukai, true)) {
        $kunci = 'medanvisit.favorit.' . $slug;

        Cache::add($kunci, 0, now()->addYears(10));
        Cache::increment($kunci);

        $disukai[] = $slug;
        $request->session()->put('tempat_disukai', $disukai);
    }

    return back()->with('status', 'Terima kasih sudah menyukai tempat ini.');
}
    public function entitas(string $jenis, string $slug)
    {
        $e = Katalog::get($slug);
        abort_unless($e && $e['jenis'] === $jenis, 404);

        return view('situs.entitas', $this->dataEntitas($e));
    }

    private function dataEntitas(array $e): array
    {
        $tetangga = Katalog::tetangga($e['slug'], false);
        $kelompok = collect($tetangga)->groupBy(fn ($t) => Katalog::get($t['lawan'])['jenis'])->all();

        return [
            'e' => $e,
            'tetangga' => $tetangga,
            'kelompok' => $kelompok,
            'grafMini' => $this->grafMini($e['slug']),
        ];
    }

    /** Tetangga 1–2 langkah, tanpa wilayah, untuk graf mini. */
    private function grafMini(string $slug): array
    {
        $simpul = [$slug => 0];
        $sisi = [];
        foreach ([1, 2] as $tingkat) {
            foreach (array_keys(array_filter($simpul, fn ($d) => $d === $tingkat - 1)) as $asal) {
                foreach (Katalog::tetangga($asal, false) as $t) {
                    $sisi[$t['id']] = $t['relasi'];
                    $simpul[$t['lawan']] ??= $tingkat;
                }
            }
        }

        return ['simpul' => $simpul, 'sisi' => array_values($sisi)];
    }

    private function terdekat(array $t, int $n): array
    {
        $lain = array_filter(Katalog::tempat(), fn ($x) => $x['slug'] !== $t['slug']);
        $hasil = array_map(fn ($x) => $x + ['jarak' => Katalog::jarakMeter($t, $x)], $lain);
        usort($hasil, fn ($a, $b) => $a['jarak'] <=> $b['jarak']);

        return array_slice($hasil, 0, $n);
    }

    public function jelajah(Request $request)
    {
        $entitas = collect(Katalog::entitas())->reject(fn ($e) => $e['jenis'] === 'wilayah')->sortBy('nama');
        $defaultDari = $entitas->keys()->first();
        $defaultKe = $entitas->keys()->skip(1)->first() ?? $defaultDari;
        $dari = $request->query('dari', $defaultDari);
        $ke = $request->query('ke', $defaultKe);
        $pilihan = collect(Katalog::entitas())->reject(fn ($e) => $e['jenis'] === 'wilayah')->sortBy('nama')->all();

        return view('situs.jelajah', [
            'dari' => Katalog::get($dari) ? $dari : $defaultDari,
            'ke' => Katalog::get($ke) ? $ke : $defaultKe,
            'langkah' => Katalog::benangMerah($dari, $ke),
            'pilihan' => $pilihan,
            'jumlah' => collect(Katalog::entitas())->countBy('jenis')->all(),
        ]);
    }

    public function graf()
    {
        $nodes = [];
        foreach (Katalog::entitas() as $slug => $e) {
            $nodes[] = ['data' => ['id' => $slug, 'label' => $e['nama'], 'jenis' => $e['jenis'], 'ringkas' => $e['ringkas'] ?? '', 'url' => self::urlEntitas($e)]];
        }
        $edges = [];
        foreach (Katalog::relasi() as $i => $r) {
            $edges[] = ['data' => [
                'id' => 'r'.$i, 'source' => $r[0], 'target' => $r[2], 'predikat' => $r[1],
                'label' => Katalog::labelRelasi($r, $r[0]), 'label_balik' => Katalog::labelRelasi($r, $r[2]),
                'kalimat' => Katalog::kalimat($r, $r[0]), 'sumber' => $r['sumber'],
            ]];
        }

        return response()->json(['nodes' => $nodes, 'edges' => $edges]);
    }

    public static function urlEntitas(array $e): ?string
    {
        return match ($e['jenis']) {
            'tempat' => route('tempat', $e['slug']),
            'wilayah' => null,
            default => route('entitas', [$e['jenis'], $e['slug']]),
        };
    }

    public function jalur()
    {
        return view('situs.jalur', ['jalur' => Katalog::jalurTematik()]);
    }

    public function jalurDetail(string $slug)
    {
        $semua = Katalog::jalurTematik();
        abort_unless(isset($semua[$slug]), 404);
        $j = $semua[$slug];
        $singgah = [];
        foreach ($j['singgah'] as $i => $s) {
            $t = Katalog::get($s);
            $singgah[] = ['tempat' => $t, 'pindah' => $i ? Katalog::perpindahan(Katalog::get($j['singgah'][$i - 1]), $t, ['jalan', 'becak']) : null];
        }
        $tiket = array_filter(array_column($singgah, 'tempat'), fn ($t) => ! empty($t['tiket']));

        return view('situs.jalur-detail', ['j' => $j, 'singgah' => $singgah, 'tiket' => $tiket, 'lain' => array_diff_key($semua, [$slug => 1])]);
    }

    public function rencana(Request $request)
    {
        $hasil = null;
        $pref = null;
        if ($request->has('minat')) {
            $pref = $request->validate([
                'minat' => ['required', 'array', 'min:1'], 'minat.*' => ['in:'.implode(',', array_keys(Katalog::KATEGORI))],
                'durasi' => ['required', 'integer', 'in:2,4,6,8'],
                'moda' => ['required', 'array', 'min:1'], 'moda.*' => ['in:jalan,becak'],
                'mulai' => ['required', 'string'],
            ], [
                'minat.required' => 'Pilih minimal satu minat.',
                'moda.required' => 'Pilih minimal satu cara berpindah.',
            ]);
            abort_unless(Katalog::get($pref['mulai']), 422);
            $hasil = Katalog::susunRute($pref['minat'], (int) $pref['durasi'], $pref['moda'], $pref['mulai']);
        }

        return view('situs.rencana', ['hasil' => $hasil, 'pref' => $pref, 'tempat' => Katalog::tempat()]);
    }

    public function rencanaBagikan(string $kode)
    {
        $r = Penyimpanan::cariRencana($kode);
        abort_unless($r, 404);

        return view('situs.rencana', [
            'hasil' => Katalog::susunRute($r['minat'], $r['durasi'], $r['moda'], $r['mulai']),
            'pref' => $r, 'tempat' => Katalog::tempat(), 'dibagikan' => $r,
        ]);
    }

    public function ketentuan()
    {
        return view('situs.ketentuan');
    }
}
