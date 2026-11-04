<?php

namespace App\Http\Controllers;

use App\Support\Katalog;
use App\Support\Penyimpanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    private const KECAMATAN_MEDAN = [
        'Medan Amplas', 'Medan Area', 'Medan Barat', 'Medan Baru', 'Medan Belawan', 'Medan Deli', 'Medan Denai',
        'Medan Helvetia', 'Medan Johor', 'Medan Kota', 'Medan Labuhan', 'Medan Maimun', 'Medan Marelan', 'Medan Perjuangan',
        'Medan Petisah', 'Medan Polonia', 'Medan Selayang', 'Medan Sunggal', 'Medan Tembung', 'Medan Timur', 'Medan Tuntungan',
    ];

    private function usulanRelasi(): array
    {
        return Cache::get('medanvisit.usulan_relasi', []);
    }

    public function ringkasan(Request $request)
    {
        $periode = (int) $request->query('periode', 30);
        $periode = in_array($periode, [7, 30, 90], true) ? $periode : 30;
        $sejak = now('Asia/Jakarta')->subDays($periode)->toIso8601String();

        $checkin = collect(Penyimpanan::checkin())->filter(fn ($c) => $c['waktu'] >= $sejak);
        $perKecamatan = collect(self::KECAMATAN_MEDAN)->mapWithKeys(fn ($k) => [$k => 0])->all();
        foreach ($checkin as $c) {
            $perKecamatan[Katalog::get($c['tempat'])['kecamatan']]++;
        }
        arsort($perKecamatan);

        $lunas = collect(Penyimpanan::pesanan())->filter(fn ($p) => $p['status'] === 'lunas' && ($p['dibayar'] ?? '') >= $sejak);
        $tanpaSumber = collect(Katalog::relasi())->filter(fn ($r) => str_contains($r['sumber'], 'perlu verifikasi'));
        $tanpaFoto = collect(Katalog::tempat())->filter(fn ($t) => empty($t['foto']));

        return view('admin.ringkasan', [
            'periode' => $periode,
            'checkin' => $checkin->count(),
            'pengguna' => $checkin->pluck('email')->unique()->count(),
            'tiket' => $lunas->sum(fn ($p) => array_sum(array_column($p['item'], 'jumlah'))),
            'pendapatan' => $lunas->sum('total'),
            'penukaran' => collect(Penyimpanan::penukaran())->filter(fn ($p) => $p['waktu'] >= $sejak)->count(),
            'perKecamatan' => $perKecamatan,
            'ulasanMenunggu' => Penyimpanan::ulasan(null, 'menunggu'),
            'celah' => Penyimpanan::celahKonten(),
            'jumlahCari' => Penyimpanan::jumlahCari(),
            'tanpaSumber' => $tanpaSumber,
            'tanpaFoto' => $tanpaFoto,
            'usulan' => $this->usulanRelasi(),
        ]);
    }

    public function entitas(Request $request)
    {
        $jenis = $request->query('jenis');
        $q = mb_strtolower((string) $request->query('q', ''));
        $data = collect(Katalog::entitas())->reject(fn ($e) => $e['jenis'] === 'wilayah')
            ->filter(fn ($e) => (! $jenis || $e['jenis'] === $jenis) && ($q === '' || str_contains(mb_strtolower($e['nama']), $q)))
            ->sortBy('nama');

        return view('admin.entitas', [
            'data' => $data,
            'jenis' => $jenis,
            'q' => $q,
            'jumlahRelasi' => collect(Katalog::relasi())->flatMap(fn ($r) => [$r[0], $r[2]])->countBy()->all(),
            'perJenis' => collect(Katalog::entitas())->reject(fn ($e) => $e['jenis'] === 'wilayah')->countBy('jenis')->all(),
        ]);
    }

    public function ubahEntitas(string $slug)
    {
        $e = Katalog::get($slug);
        abort_unless($e && $e['jenis'] !== 'wilayah', 404);

        return view('admin.entitas-ubah', ['e' => $e, 'tetangga' => Katalog::tetangga($slug, false)]);
    }

    public function simpanEntitas(Request $request, string $slug)
    {
        $e = Katalog::get($slug);
        abort_unless($e, 404);
        $aturan = ['nama' => ['required', 'string', 'max:120'], 'ringkas' => ['required', 'string', 'max:240'], 'status' => ['required', 'in:terbit,draf']];
        if ($e['jenis'] === 'tempat') {
            $aturan += [
                'lat' => ['required', 'numeric', 'between:3.4,3.8'], 'lng' => ['required', 'numeric', 'between:98.5,98.8'],
                'alamat' => ['required', 'string', 'max:160'], 'jam' => ['required', 'string', 'max:80'],
                'harga' => ['array'], 'harga.*' => ['nullable', 'integer', 'min:0'],
            ];
        }
        $data = $request->validate($aturan, [
            'lat.between' => 'Lintang harus di wilayah Kota Medan (3,4 sampai 3,8).',
            'lng.between' => 'Bujur harus di wilayah Kota Medan (98,5 sampai 98,8).',
        ]);
        Cache::forever('medanvisit.draf_entitas.'.$slug, $data + ['waktu' => now('Asia/Jakarta')->toIso8601String()]);

        return back()->with('pesan', 'Perubahan '.$data['nama'].' tersimpan sebagai draf. Draf akan ditulis ke PostgreSQL setelah database tersambung.');
    }

    public function relasi(Request $request)
    {
        $predikat = $request->query('predikat');

        return view('admin.relasi', [
            'relasi' => collect(Katalog::relasi())->reject(fn ($r) => $r[1] === 'beradaDi')->filter(fn ($r) => ! $predikat || $r[1] === $predikat)->values(),
            'predikat' => $predikat,
            'pilihan' => collect(Katalog::entitas())->reject(fn ($e) => $e['jenis'] === 'wilayah')->sortBy('nama'),
            'usulan' => $this->usulanRelasi(),
        ]);
    }

    public function tambahRelasi(Request $request)
    {
        $data = $request->validate([
            'subjek' => ['required', 'string'], 'predikat' => ['required', 'in:'.implode(',', array_keys(Katalog::LABEL_RELASI))],
            'objek' => ['required', 'string', 'different:subjek'], 'sumber' => ['required', 'string', 'min:8', 'max:240'],
        ], ['objek.different' => 'Subjek dan objek harus berbeda.', 'sumber.required' => 'Relasi tanpa sumber tidak boleh tayang. Isi tautan atau judul sumbernya.']);
        abort_unless(Katalog::get($data['subjek']) && Katalog::get($data['objek']), 422);
        $usulan = $this->usulanRelasi();
        $usulan[] = $data + ['waktu' => now('Asia/Jakarta')->toIso8601String(), 'oleh' => $request->session()->get('pengguna.nama')];
        Cache::forever('medanvisit.usulan_relasi', $usulan);

        return back()->with('pesan', 'Usulan relasi tersimpan: '.Katalog::get($data['subjek'])['nama'].' '.Katalog::LABEL_RELASI[$data['predikat']].' '.Katalog::get($data['objek'])['nama'].'.');
    }

    public function cerita(Request $request)
    {
        $slug = $request->query('tempat', 'istana-maimun');
        $t = Katalog::get($slug);
        abort_unless($t && $t['jenis'] === 'tempat', 404);
        $bahasa = $request->query('bahasa', 'id');
        $draf = Cache::get("medanvisit.cerita.$slug.$bahasa");

        return view('admin.cerita', [
            't' => $t, 'bahasa' => in_array($bahasa, ['id', 'en', 'zh', 'ms'], true) ? $bahasa : 'id', 'draf' => $draf,
            'tempat' => Katalog::tempat(),
            'status' => collect(['id', 'en', 'zh', 'ms'])->mapWithKeys(fn ($b) => [$b => $b === 'id' || Cache::has("medanvisit.cerita.$slug.$b")])->all(),
        ]);
    }

    public function simpanCerita(Request $request)
    {
        $data = $request->validate([
            'tempat' => ['required', 'string'], 'bahasa' => ['required', 'in:id,en,zh,ms'],
            'judul' => ['required', 'string', 'max:120'], 'isi' => ['required', 'string', 'min:40'],
        ], ['isi.min' => 'Cerita minimal 40 karakter.']);
        Cache::forever("medanvisit.cerita.{$data['tempat']}.{$data['bahasa']}", $data + ['waktu' => now('Asia/Jakarta')->toIso8601String()]);

        return redirect()->route('admin.cerita', ['tempat' => $data['tempat'], 'bahasa' => $data['bahasa']])->with('pesan', 'Draf cerita tersimpan.');
    }

    public function ulasan(Request $request)
    {
        $tab = $request->query('tab', 'menunggu');

        return view('admin.ulasan', [
            'tab' => in_array($tab, ['menunggu', 'tayang', 'ditolak'], true) ? $tab : 'menunggu',
            'jumlah' => collect(Penyimpanan::ulasan())->countBy('status')->all(),
            'ulasan' => Penyimpanan::ulasan(null, in_array($tab, ['menunggu', 'tayang', 'ditolak'], true) ? $tab : 'menunggu'),
        ]);
    }

    public function moderasi(Request $request, string $id)
    {
        $data = $request->validate(['keputusan' => ['required', 'in:tayang,ditolak']]);
        Penyimpanan::moderasi($id, $data['keputusan']);

        return back()->with('pesan', $data['keputusan'] === 'tayang' ? 'Ulasan ditayangkan.' : 'Ulasan ditolak.');
    }

    public function mitra()
    {
        $voucher = collect(Penyimpanan::voucher());
        $penukaran = collect(Penyimpanan::penukaran());

        return view('admin.mitra', [
            'mitra' => $voucher->groupBy('usaha')->map(fn ($vs, $usaha) => [
                'usaha' => $usaha, 'voucher' => $vs->count(), 'stok' => $vs->sum('stok'),
                'ditukar' => $penukaran->whereIn('voucher', $vs->pluck('id'))->count(),
            ])->values(),
            'voucher' => $voucher->sortByDesc('waktu')->values(),
        ]);
    }

    public function tiket(Request $request)
    {
        return view('admin.tiket', [
            'pesanan' => collect(Penyimpanan::pesanan())->values(),
            'bertiket' => Katalog::bertiket(),
        ]);
    }

    public function validasi(Request $request)
    {
        $data = $request->validate(['kode' => ['required', 'string', 'max:20']], ['kode.required' => 'Pindai atau ketik kode pada e-tiket.']);
        $p = Penyimpanan::cariPesanan(strtoupper(trim($data['kode'])));
        $hasil = match (true) {
            ! $p => ['status' => 'tidak', 'judul' => 'Tidak ditemukan', 'teks' => 'Tidak ada pesanan dengan kode '.strtoupper($data['kode']).'.'],
            $p['status'] !== 'lunas' => ['status' => 'tidak', 'judul' => 'Belum dibayar', 'teks' => 'Pesanan '.$p['kode'].' berstatus '.$p['status'].'.'],
            ! empty($p['dipakai']) => ['status' => 'dipakai', 'judul' => 'Sudah dipakai', 'teks' => 'Tiket ini dipakai '.substr($p['dipakai'], 11, 5).'.'],
            $p['tanggal'] !== now('Asia/Jakarta')->toDateString() => ['status' => 'tidak', 'judul' => 'Bukan untuk hari ini', 'teks' => 'Tiket berlaku tanggal '.$p['tanggal'].'.'],
            default => ['status' => 'valid', 'judul' => 'Valid', 'teks' => collect($p['item'])->map(fn ($i) => $i['jumlah'].' '.$i['jenis'])->implode(', ').' atas nama '.$p['pemesan']['nama'].'.'],
        };
        if ($hasil['status'] === 'valid') {
            Penyimpanan::ubahPesanan($p['kode'], ['dipakai' => now('Asia/Jakarta')->toIso8601String()]);
        }

        return back()->with('hasil', $hasil);
    }

    public function sinkronisasi(Request $request)
    {
        $hasil = null;
        if ($request->boolean('periksa')) {
            $hasil = [];
            foreach (['pgsql' => 'PostgreSQL 1 (atribut & transaksi)', 'supabase2' => 'PostgreSQL 2 (tiket & pembayaran)'] as $koneksi => $label) {
                config(["database.connections.$koneksi.connect_timeout" => 4]);
                $mulai = microtime(true);
                try {
                    DB::connection($koneksi)->getPdo();
                    $hasil[] = ['label' => $label, 'ok' => true, 'teks' => 'Tersambung dalam '.round((microtime(true) - $mulai) * 1000).' ms.'];
                } catch (\Throwable $e) {
                    $pesan = preg_replace('/password=[^\s]+/i', 'password=***', $e->getMessage());
                    $hasil[] = ['label' => $label, 'ok' => false, 'teks' => str($pesan)->before(' (Connection:')->limit(180)->toString()];
                }
            }
            $endpoint = env('SPARQL_ENDPOINT');
            if (! $endpoint) {
                $hasil[] = ['label' => 'Apache Jena Fuseki (SPARQL)', 'ok' => false, 'teks' => 'SPARQL_ENDPOINT belum diisi di .env.'];
            } else {
                try {
                    $res = Http::timeout(4)->asForm()->post($endpoint, ['query' => 'ASK { ?s ?p ?o }']);
                    $hasil[] = ['label' => 'Apache Jena Fuseki (SPARQL)', 'ok' => $res->successful(), 'teks' => $res->successful() ? 'Endpoint menjawab.' : 'Endpoint menjawab dengan status '.$res->status().'.'];
                } catch (\Throwable $e) {
                    $hasil[] = ['label' => 'Apache Jena Fuseki (SPARQL)', 'ok' => false, 'teks' => 'Tidak bisa dihubungi: '.str($e->getMessage())->limit(120)];
                }
            }
            $hasil[] = ['label' => 'Penyimpanan sementara (cache berkas)', 'ok' => true, 'teks' => count(Penyimpanan::pesanan()).' pesanan, '.count(Penyimpanan::checkin()).' check-in, '.count(Penyimpanan::ulasan()).' ulasan tersimpan.'];
        }

        return view('admin.sinkronisasi', ['hasil' => $hasil, 'diperiksa' => $hasil ? now('Asia/Jakarta') : null]);
    }
}
