<?php

namespace App\Http\Controllers;

use App\Support\Katalog;
use App\Support\Database3CatalogWriter;
use App\Support\Kunjungan;
use App\Support\Penyimpanan;
use App\Support\SupabaseApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    private function usulanRelasi(): array
    {
        return Cache::get('medanvisit.usulan_relasi', []);
    }

    public function ringkasan(Request $request)
    {
        $periode = (int) $request->query('periode', 30);
        $periode = in_array($periode, [7, 30, 90], true) ? $periode : 30;
        $sejak = now('Asia/Jakarta')->subDays($periode)->toIso8601String();

        $lunas = collect(Penyimpanan::pesanan())->filter(fn ($p) => $p['status'] === 'lunas' && ($p['dibayar'] ?? '') >= $sejak);
        $tanpaSumber = collect(Katalog::relasi())->filter(fn ($r) => str_contains($r['sumber'], 'perlu verifikasi'));
        $tanpaFoto = collect(Katalog::tempat())->filter(fn ($t) => empty($t['foto']));

        return view('admin.ringkasan', [
            'periode' => $periode,
            'jumlahTempat' => count(Katalog::tempat()),
            'tiket' => $lunas->sum(fn ($p) => array_sum(array_column($p['item'], 'jumlah'))),
            'pendapatan' => $lunas->sum('total'),
            'ulasanMenunggu' => Penyimpanan::ulasan(null, 'menunggu'),
            'celah' => Penyimpanan::celahKonten(),
            'jumlahCari' => Penyimpanan::jumlahCari(),
            'tanpaSumber' => $tanpaSumber,
            'tanpaFoto' => $tanpaFoto,
            'usulan' => $this->usulanRelasi(),
        ]);
    }

    public function insight(Request $request)
    {
        $periode = (int) $request->query('periode', 30);
        $periode = in_array($periode, [7, 30, 90], true) ? $periode : 30;
        $kunjungan = Kunjungan::ringkasan($periode);
        $sejak = now('Asia/Jakarta')->subDays($periode)->toIso8601String();

        // admin tempat hanya melihat tempatnya sendiri; superadmin bisa memilih satu tempat lewat ?tempat=
        );
        $transaksi = $pesanan->groupBy('tempat')->map(fn ($baris) => [
            'pesanan' => $baris->count(),
            'lunas' => $baris->where('status', 'lunas')->count(),
            'tiket' => $baris->where('status', 'lunas')->sum(fn ($pesanan) => array_sum(array_column($pesanan['item'], 'jumlah'))),
            'pendapatan' => $baris->where('status', 'lunas')->sum('total'),
        ])->sortByDesc('pendapatan');
        $akun = (new SupabaseApi)->users();

        return view('admin.insight', [
            'periode' => $periode,
            'kunjungan' => $kunjungan,
            'akun' => collect($akun)->countBy('peran')->all(),
            'tempatPopuler' => collect($kunjungan['tempat'])->take(10),
            'transaksi' => $transaksi,
            'pesanan' => $pesanan,
        ]);
    }

    public function akunAdmin(Request $request)
    {
        $q = mb_strtolower(trim((string) $request->query('q', '')));
        $peran = $request->query('peran');
        abort_if($peran && ! in_array($peran, ['admin', 'wisatawan'], true), 404);
        $semuaAkun = (new SupabaseApi)->users();
        $akun = collect($semuaAkun)
            ->filter(fn ($user) => in_array($user['peran'], ['admin', 'wisatawan'], true))
            ->when($peran, fn ($rows) => $rows->where('peran', $peran))
            ->when($q !== '', fn ($rows) => $rows->filter(fn ($user) =>
                str_contains(mb_strtolower($user['name']), $q)
                || str_contains(mb_strtolower($user['email']), $q)
            ))
            ->values();

        return view('admin.akun', [
            'akun' => $akun,
            'q' => $q,
            'peran' => $peran,
            'jumlah' => collect($semuaAkun)->countBy('peran')->all(),
        ]);
    }

    public function buatAkun(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'peran' => ['required', 'in:admin,wisatawan'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'],
        ], ['password.regex' => 'Password minimal 8 karakter dan harus memiliki huruf kecil, huruf kapital, angka, dan simbol.']);
        $api = new SupabaseApi;
        if ($api->findUserByEmail($data['email'])) {
            throw ValidationException::withMessages(['email' => 'Email sudah digunakan.']);
        }
        $waktu = now('Asia/Jakarta')->toDateTimeString();
        $api->createUser([
            'name' => $data['nama'],
            'email' => strtolower($data['email']),
            'peran' => $data['peran'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => $waktu,
            'created_at' => $waktu,
            'updated_at' => $waktu,
        ]);

        return redirect()->route('admin.akun')->with('pesan', 'Akun '.$data['peran'].' berhasil dibuat.');
    }

    public function ubahAkun(Request $request, string $id)
    {
        $api = new SupabaseApi;
        $user = collect($api->users())->first(fn ($user) => (string) $user['id'] === $id);
        abort_unless($user && in_array($user['peran'], ['admin', 'wisatawan'], true), 404);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'peran' => ['required', 'in:admin,wisatawan'],
            'password' => ['nullable', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'],
        ], ['password.regex' => 'Password minimal 8 karakter dan harus memiliki huruf kecil, huruf kapital, angka, dan simbol.']);
        $emailLama = $api->findUserByEmail($data['email']);
        if ($emailLama && (string) $emailLama['id'] !== $id) {
            throw ValidationException::withMessages(['email' => 'Email sudah digunakan akun lain.']);
        }

        $atribut = [
            'name' => $data['nama'],
            'email' => strtolower($data['email']),
            'peran' => $data['peran'],
            'updated_at' => now('Asia/Jakarta')->toDateTimeString(),
        ];
        if ($data['password'] !== null && $data['password'] !== '') {
            $atribut['password'] = Hash::make($data['password']);
        }
        $api->updateUser($id, $atribut);

        return redirect()->route('admin.akun')->with('pesan', 'Akun berhasil diperbarui.');
    }

    public function hapusAkun(Request $request, string $id)
    {
        abort_if((string) $request->session()->get('pengguna.id') === $id, 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        $user = collect((new SupabaseApi)->users())->first(fn ($user) => (string) $user['id'] === $id);
        abort_unless($user && in_array($user['peran'], ['admin', 'wisatawan'], true), 404);
        (new SupabaseApi)->deleteUser($id);

        return redirect()->route('admin.akun')->with('pesan', 'Akun '.$user['email'].' berhasil dihapus.');
    }

    /**
     * Slug tempat yang boleh dikelola akun ini. null = tidak dibatasi
     * (superadmin, dan admin lama yang belum ditautkan ke tempat).
     */
    private function slugKelolaan(Request $request): ?string
    {
        $pengguna = $request->session()->get('pengguna', []);
        if (($pengguna['peran'] ?? null) !== 'admin') {
            return null;
        }

        return $pengguna['tempat_slug'] ?? null;
    }

    private function pastikanBolehKelola(Request $request, string $slug): void
    {
        $milik = $this->slugKelolaan($request);
        abort_if($milik !== null && $milik !== $slug, 403, 'Akun ini hanya boleh mengelola tempatnya sendiri.');
    }

    public function entitas(Request $request)
    {
        $jenis = $request->query('jenis', $request->route('jenis'));
        $q = mb_strtolower((string) $request->query('q', ''));
        $data = collect(Katalog::entitas())->reject(fn ($e) => $e['jenis'] === 'wilayah')
            ->filter(fn ($e) => (! $jenis || $e['jenis'] === $jenis) && ($q === '' || str_contains(mb_strtolower($e['nama']), $q)))
            ->sortBy('nama');

        if ($milik = $this->slugKelolaan($request)) {
            $data = $data->filter(fn ($e) => $e['slug'] === $milik);
        }

        return view('admin.entitas', [
            'data' => $data,
            'jenis' => $jenis,
            'q' => $q,
            'jumlahRelasi' => collect(Katalog::relasi())->flatMap(fn ($r) => [$r[0], $r[2]])->countBy()->all(),
            'perJenis' => collect(Katalog::entitas())->reject(fn ($e) => $e['jenis'] === 'wilayah')->countBy('jenis')->all(),
        ]);
    }

    public function ubahEntitas(Request $request, string $slug)
    {
        $this->pastikanBolehKelola($request, $slug);
        $e = Katalog::get($slug);
        abort_unless($e && $e['jenis'] !== 'wilayah', 404);
        $kecamatan = Katalog::jenis('wilayah');
        $kecamatanTerpilih = collect($kecamatan)->first(fn ($wilayah) => $wilayah['nama'] === ($e['kecamatan'] ?? null))['slug'] ?? '';

        return view('admin.entitas-ubah', [
            'e' => $e,
            'tetangga' => Katalog::tetangga($slug, false),
            'kecamatan' => $kecamatan,
            'kecamatanTerpilih' => $kecamatanTerpilih,
        ]);
    }

    public function simpanEntitas(Request $request, string $slug)
    {
        $this->pastikanBolehKelola($request, $slug);
        $e = Katalog::get($slug);
        abort_unless($e, 404);
        $aturan = ['nama' => ['required', 'string', 'max:120'], 'ringkas' => ['required', 'string', 'max:240']];
        if ($e['jenis'] === 'tempat') {
            $aturan += [
                'lat' => ['required', 'numeric', 'between:3.4,3.8'], 'lng' => ['required', 'numeric', 'between:98.5,98.8'],
                'alamat' => ['required', 'string', 'max:160'], 'jam' => ['required', 'string', 'max:80'],
                'sumber' => ['required', 'string', 'min:3', 'max:240'],
                'kategori' => ['required', 'in:'.implode(',', array_keys(Katalog::KATEGORI))],
                'kecamatan' => ['nullable', 'string', 'max:100'],
                'foto' => ['nullable', 'string', 'max:160', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
                'harga' => ['nullable', 'array'], 'harga.*' => ['nullable', 'integer', 'min:0'],
            ];
        } else {
            $aturan['status'] = ['required', 'in:terbit,draf'];
        }
        $data = $request->validate($aturan, [
            'lat.between' => 'Lintang harus di wilayah Kota Medan (3,4 sampai 3,8).',
            'lng.between' => 'Bujur harus di wilayah Kota Medan (98,5 sampai 98,8).',
        ]);
        if ($e['jenis'] === 'tempat') {
            if (! empty($data['kecamatan'])) {
                $wilayah = Katalog::get($data['kecamatan']);
                if (! $wilayah || $wilayah['jenis'] !== 'wilayah') {
                    throw ValidationException::withMessages(['kecamatan' => 'Kecamatan tidak ditemukan di Database 3.']);
                }
            }
            $data['foto'] = (string) ($data['foto'] ?? '');
            $data['kecamatan'] = (string) ($data['kecamatan'] ?? '');
            Database3CatalogWriter::update($slug, $data, $data['harga'] ?? []);

            return back()->with('pesan', 'Perubahan '.$data['nama'].' berhasil disimpan ke Database 3.');
        }

        Cache::forever('medanvisit.draf_entitas.'.$slug, $data + ['waktu' => now('Asia/Jakarta')->toIso8601String()]);

        return back()->with('pesan', 'Perubahan '.$data['nama'].' tersimpan sebagai draf.');
    }

    public function buatTempat()
    {
        return view('admin.tempat-baru', ['kecamatan' => Katalog::jenis('wilayah')]);
    }

    public function simpanTempat(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'ringkas' => ['required', 'string', 'max:240'],
            'kategori' => ['required', 'in:'.implode(',', array_keys(Katalog::KATEGORI))],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'lat' => ['required', 'numeric', 'between:3.4,3.8'],
            'lng' => ['required', 'numeric', 'between:98.5,98.8'],
            'alamat' => ['required', 'string', 'max:160'],
            'jam' => ['required', 'string', 'max:80'],
            'sumber' => ['required', 'string', 'min:3', 'max:240'],
            'foto' => ['nullable', 'string', 'max:160', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
            'harga' => ['nullable', 'integer', 'min:0'],
        ], [
            'lat.between' => 'Lintang harus di wilayah Kota Medan (3,4 sampai 3,8).',
            'lng.between' => 'Bujur harus di wilayah Kota Medan (98,5 sampai 98,8).',
        ]);
        if (! empty($data['kecamatan'])) {
            $wilayah = Katalog::get($data['kecamatan']);
            if (! $wilayah || $wilayah['jenis'] !== 'wilayah') {
                throw ValidationException::withMessages(['kecamatan' => 'Kecamatan tidak ditemukan di Database 3.']);
            }
        }

        $slug = Str::slug($data['nama']);
        if ($slug === '' || Katalog::get($slug)) {
            throw ValidationException::withMessages(['nama' => 'Nama tempat sudah digunakan atau tidak dapat dijadikan alamat.']);
        }
        $harga = isset($data['harga']) && $data['harga'] !== '' ? ['Reguler' => (int) $data['harga']] : [];
        $data['foto'] = (string) ($data['foto'] ?? '');
        $data['kecamatan'] = (string) ($data['kecamatan'] ?? '');
        Database3CatalogWriter::create($slug, $data, $harga);

        return redirect()->route('admin.entitas.ubah', $slug)->with('pesan', 'Tempat wisata berhasil dibuat di Database 3.');
    }

    public function hapusTempat(string $slug)
    {
        $tempat = Katalog::get($slug);
        abort_unless($tempat && $tempat['jenis'] === 'tempat', 404);
        Database3CatalogWriter::delete($slug);

        return redirect()->route('admin.entitas', ['jenis' => 'tempat'])->with('pesan', 'Tempat wisata '.$tempat['nama'].' berhasil dihapus dari Database 3.');
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
        $slug = $request->query('tempat', array_key_first(Katalog::tempat()) ?? '');
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
            foreach (['katalog' => ['users', 'PostgreSQL Database 1 (akun, Laravel, katalog)'], 'transaksi' => ['pesanan', 'PostgreSQL Database 2 (transaksi)']] as $koneksi => [$tabel, $label]) {
                $mulai = microtime(true);
                try {
                    (new SupabaseApi($koneksi))->ping($tabel);
                    $hasil[] = ['label' => $label, 'ok' => true, 'teks' => 'Tersambung dalam '.round((microtime(true) - $mulai) * 1000).' ms.'];
                } catch (\Throwable $e) {
                    $pesan = preg_replace('/password=[^\s]+/i', 'password=***', $e->getMessage());
                    $hasil[] = ['label' => $label, 'ok' => false, 'teks' => str($pesan)->before(' (Connection:')->limit(180)->toString()];
                }
            }
            $endpoint = config('services.sparql.endpoint');
            if (! $endpoint) {
                $hasil[] = ['label' => 'Database 3 ('.config('services.sparql.driver').'/SPARQL)', 'ok' => false, 'teks' => 'SPARQL_ENDPOINT belum diisi di .env.'];
            } else {
                try {
                    $http = Http::timeout((int) config('services.sparql.timeout', 30));
                    $username = config('services.sparql.username');
                    $password = config('services.sparql.password');
                    if ($username !== null && $password !== null) {
                        $http = $http->withBasicAuth($username, $password);
                    }
                    $res = $http->asForm()->post($endpoint, ['query' => 'ASK { ?s ?p ?o }']);
                    $hasil[] = ['label' => 'Database 3 ('.config('services.sparql.driver').'/SPARQL)', 'ok' => $res->successful(), 'teks' => $res->successful() ? 'Endpoint menjawab.' : 'Endpoint menjawab dengan status '.$res->status().'.'];
                } catch (\Throwable $e) {
                    $hasil[] = ['label' => 'Database 3 ('.config('services.sparql.driver').'/SPARQL)', 'ok' => false, 'teks' => 'Tidak bisa dihubungi: '.str($e->getMessage())->limit(120)];
                }
            }
            try {
                $hasil[] = ['label' => 'Isi Database 2 (transaksi)', 'ok' => true, 'teks' => count(Penyimpanan::pesanan()).' pesanan, '.count(Penyimpanan::ulasan()).' ulasan, '.count(Penyimpanan::rencana()).' rencana, '.Penyimpanan::jumlahCari().' pencarian tercatat.'];
            } catch (\Throwable $e) {
                $hasil[] = ['label' => 'Isi Database 2 (transaksi)', 'ok' => false, 'teks' => 'Tidak bisa dibaca lewat koneksi langsung: '.str($e->getMessage())->before(' (Connection:')->limit(120)];
            }
        }

        return view('admin.sinkronisasi', ['hasil' => $hasil, 'diperiksa' => $hasil ? now('Asia/Jakarta') : null]);
    }
}
