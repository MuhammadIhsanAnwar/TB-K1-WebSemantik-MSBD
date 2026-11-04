<?php

namespace App\Http\Controllers;

use App\Support\Katalog;
use App\Support\Penyimpanan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AkunController extends Controller
{
    private function pengguna(Request $r): array
    {
        return $r->session()->get('pengguna');
    }

    private function aman(?string $kembali): ?string
    {
        return $kembali && str_starts_with($kembali, '/') && ! str_starts_with($kembali, '//') ? $kembali : null;
    }

    public function masuk(Request $request)
    {
        return view('akun.masuk', ['kembali' => $this->aman($request->query('kembali'))]);
    }

    public function prosesMasuk(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
            'peran' => ['required', 'in:wisatawan,mitra,kurator'],
        ], [
            'email.required' => 'Isi alamat email.',
            'email.email' => 'Alamat email belum benar.',
            'password.required' => 'Isi kata sandi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        return $this->masukkan($request, $data['email'], null, $data['peran']);
    }

    private function masukkan(Request $request, string $email, ?string $nama, string $peran)
    {
        $nama ??= Str::of(Str::before($email, '@'))->replace(['.', '_', '-'], ' ')->title()->toString();
        $request->session()->regenerate();
        $request->session()->put('pengguna', ['email' => strtolower($email), 'nama' => $nama, 'peran' => $peran]);

        $tujuan = $this->aman($request->input('kembali')) ?? match ($peran) {
            'mitra' => route('mitra.listing'),
            'kurator' => route('admin.ringkasan'),
            default => route('paspor'),
        };

        return redirect($tujuan)->with('pesan', 'Halo, '.$nama.'.');
    }

    public function daftar(Request $request)
    {
        return view('akun.daftar', ['kembali' => $this->aman($request->query('kembali'))]);
    }

    public function prosesDaftar(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'setuju' => ['accepted'],
        ], [
            'nama.required' => 'Isi nama yang akan tampil di paspor.',
            'email.required' => 'Isi alamat email.',
            'email.email' => 'Alamat email belum benar.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Ulangi kata sandi yang sama.',
            'setuju.accepted' => 'Setujui ketentuan untuk membuat akun.',
        ]);

        return $this->masukkan($request, $data['email'], $data['nama'], 'wisatawan');
    }

    public function keluar(Request $request)
    {
        $request->session()->forget('pengguna');
        $request->session()->regenerateToken();

        return redirect()->route('beranda');
    }

    public function akun(Request $request)
    {
        $u = $this->pengguna($request);

        return view('akun.akun', [
            'u' => $u,
            'poin' => Penyimpanan::poin($u['email']),
            'stempel' => collect(Penyimpanan::checkin($u['email']))->unique('tempat')->count(),
            'pesanan' => count(Penyimpanan::pesanan($u['email'])),
            'rencana' => count(Penyimpanan::rencana($u['email'])),
            'ulasan' => count(array_filter(Penyimpanan::ulasan(), fn ($x) => $x['email'] === $u['email'])),
        ]);
    }

    public function paspor(Request $request)
    {
        $u = $this->pengguna($request);
        $checkin = Penyimpanan::checkin($u['email']);
        $stempel = collect($checkin)->sortBy('waktu')->unique('tempat')->values()->all();
        $poin = Penyimpanan::poin($u['email']);
        $awalMinggu = now('Asia/Jakarta')->startOfWeek()->toDateString();
        $ibadahMingguIni = collect($checkin)->filter(fn ($c) => $c['waktu'] >= $awalMinggu && (Katalog::get($c['tempat'])['kategori'] ?? '') === 'religi')->unique('tempat')->count();

        return view('akun.paspor', [
            'u' => $u,
            'stempel' => $stempel,
            'jumlahTempat' => count(Katalog::tempat()),
            'poin' => $poin,
            'gelar' => Katalog::gelar($poin['total']),
            'tantangan' => ['judul' => 'Kunjungi 3 rumah ibadah berbeda minggu ini', 'kini' => min(3, $ibadahMingguIni), 'target' => 3],
            'voucher' => array_filter(Penyimpanan::voucher(), fn ($v) => $v['stok'] > 0),
            'belumDikunjungi' => array_slice(array_values(array_filter(Katalog::tempat(), fn ($t) => ! collect($stempel)->contains('tempat', $t['slug']))), 0, 3),
        ]);
    }

    public function checkin(Request $request, string $slug)
    {
        $t = Katalog::get($slug);
        abort_unless($t && $t['jenis'] === 'tempat', 404);
        $data = $request->validate(['lat' => ['required', 'numeric', 'between:-90,90'], 'lng' => ['required', 'numeric', 'between:-180,180'], 'akurasi' => ['nullable', 'numeric']], [
            'lat.required' => 'Lokasimu belum terbaca. Izinkan akses lokasi lalu coba lagi.',
        ]);
        $u = $this->pengguna($request);
        $jarak = Katalog::jarakMeter(['lat' => (float) $data['lat'], 'lng' => (float) $data['lng']], $t);

        if ($jarak > $t['radius_checkin']) {
            return redirect()->route('tempat', $slug)->with('checkin_gagal', [
                'jarak' => $jarak,
                'radius' => $t['radius_checkin'],
            ]);
        }
        if (Penyimpanan::sudahCheckinHariIni($u['email'], $slug)) {
            return redirect()->route('tempat', $slug)->with('checkin_gagal', ['sudah' => true]);
        }

        $sebelum = Penyimpanan::poin($u['email']);
        $c = Penyimpanan::catatCheckin($u['email'], $u['nama'], $slug, $jarak);
        $sesudah = Penyimpanan::poin($u['email']);
        $request->session()->flash('badge_baru', array_values(array_udiff($sesudah['badge'], $sebelum['badge'], fn ($a, $b) => strcmp($a['nama'], $b['nama']))));

        return redirect()->route('checkin.hasil', $c['id']);
    }

    public function checkinHasil(Request $request, string $id)
    {
        $u = $this->pengguna($request);
        $c = collect(Penyimpanan::checkin($u['email']))->firstWhere('id', $id);
        abort_unless($c, 404);

        return view('akun.checkin', [
            'c' => $c,
            't' => Katalog::get($c['tempat']),
            'poin' => Penyimpanan::poin($u['email']),
            'badgeBaru' => $request->session()->get('badge_baru', []),
        ]);
    }

    public function tukar(Request $request, string $id)
    {
        $u = $this->pengguna($request);
        $v = Penyimpanan::voucher()[$id] ?? null;
        abort_unless($v, 404);
        $poin = Penyimpanan::poin($u['email'])['total'];

        if ($v['stok'] < 1) {
            return back()->with('galat', 'Voucher '.$v['nama'].' sudah habis.');
        }
        if ($poin < $v['poin']) {
            return back()->with('galat', 'Poinmu '.$poin.', voucher ini butuh '.$v['poin'].' poin.');
        }
        $p = Penyimpanan::tukar($u['email'], $v);

        return redirect()->route('voucher')->with('pesan', 'Voucher '.$p['nama'].' sudah jadi milikmu. Kodenya '.$p['kode'].'.');
    }

    public function voucher(Request $request)
    {
        return view('akun.voucher', ['penukaran' => Penyimpanan::penukaran($this->pengguna($request)['email'])]);
    }

    public function peringkat(Request $request)
    {
        $awal = now('Asia/Jakarta')->startOfWeek();
        $papan = collect(Penyimpanan::checkin())
            ->filter(fn ($c) => $c['waktu'] >= $awal->toIso8601String())
            ->groupBy('email')
            ->map(fn ($cs, $email) => ['email' => $email, 'nama' => $cs->first()['nama'] ?? Str::before($email, '@'), 'poin' => $cs->count() * 10, 'stempel' => $cs->unique('tempat')->count()])
            ->sortByDesc('poin')->values()->all();

        return view('akun.peringkat', ['papan' => $papan, 'u' => $this->pengguna($request), 'awal' => $awal, 'akhir' => $awal->copy()->endOfWeek()]);
    }

    public function ulasanSaya(Request $request)
    {
        $email = $this->pengguna($request)['email'];

        return view('akun.ulasan', ['ulasan' => array_filter(Penyimpanan::ulasan(), fn ($u) => $u['email'] === $email)]);
    }

    public function tulisUlasan(Request $request, string $slug)
    {
        abort_unless(Katalog::get($slug), 404);
        $data = $request->validate(['bintang' => ['required', 'integer', 'between:1,5'], 'teks' => ['required', 'string', 'min:10', 'max:600']], [
            'bintang.required' => 'Pilih jumlah bintang.',
            'teks.min' => 'Tulis minimal 10 karakter supaya ulasanmu berguna bagi orang lain.',
        ]);
        $u = $this->pengguna($request);
        Penyimpanan::tulisUlasan($data + ['tempat' => $slug, 'email' => $u['email'], 'nama' => $u['nama']]);

        return redirect()->to(route('tempat', $slug).'#ulasan')->with('pesan', 'Terima kasih. Ulasanmu tayang setelah diperiksa kurator.');
    }

    public function rencanaSaya(Request $request)
    {
        return view('akun.rencana', ['rencana' => Penyimpanan::rencana($this->pengguna($request)['email'])]);
    }

    public function simpanRencana(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:80'],
            'minat' => ['required', 'array'], 'minat.*' => ['in:'.implode(',', array_keys(Katalog::KATEGORI))],
            'durasi' => ['required', 'integer', 'in:2,4,6,8'],
            'moda' => ['required', 'array'], 'moda.*' => ['in:jalan,becak'],
            'mulai' => ['required', 'string'],
        ]);
        $r = Penyimpanan::simpanRencana($data + ['email' => $this->pengguna($request)['email'], 'durasi' => (int) $data['durasi']]);

        return redirect()->route('rencana.bagikan', $r['kode'])->with('pesan', 'Rencana tersimpan. Bagikan tautan ini ke teman seperjalanan.');
    }
}
