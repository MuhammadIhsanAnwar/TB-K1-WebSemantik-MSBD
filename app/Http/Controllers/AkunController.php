<?php

namespace App\Http\Controllers;

use App\Support\Katalog;
use App\Support\Penyimpanan;
use App\Support\SupabaseApi;
use App\Mail\VerifikasiEmail;
use App\Mail\ResetPasswordEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AkunController extends Controller
{
    private function pengguna(Request $r): array
    {
        return $r->session()->get('pengguna', []);
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
            'password' => ['required'],
        ], [
            'email.required' => 'Isi alamat email.',
            'email.email' => 'Alamat email belum benar.',
            'password.required' => 'Isi kata sandi.',
        ]);

        $user = (new SupabaseApi)->findUserByEmail($data['email']);
        if (! $user || ! Hash::check($data['password'], $user['password'])) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])->withInput($request->only('email'));
        }
        if (empty($user['email_verified_at'])) {
            $this->kirimVerifikasi($user);
            return redirect()->route('verifikasi', ['email' => $user['email']])
                ->with('pesan', 'Email belum dikonfirmasi. Kami mengirimkan link verifikasi baru.');
        }

        $request->session()->regenerate();
        $this->simpanSesiPengguna($request, $user);

        return redirect($this->aman($request->input('kembali')) ?? $this->tujuan($user['peran']))
            ->with('pesan', 'Halo, '.$user['name'].'.');
    }

    public function lupaPassword()
    {
        return view('akun.lupa-password');
    }

    public function kirimLinkPassword(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $api = new SupabaseApi;
        $user = $api->findUserByEmail($data['email']);

        if ($user && empty($user['google_id'])) {
            $token = Str::random(64);
            $api->createPasswordResetToken([
                'email' => strtolower($user['email']),
                'token' => hash('sha256', $token),
                'created_at' => now()->toDateTimeString(),
            ]);
            Mail::to($user['email'])->send(new ResetPasswordEmail(
                route('password.reset', ['token' => $token, 'email' => $user['email']])
            ));
        }

        return redirect()->route('password.request')->with(
            'pesan',
            'Jika email tersebut terdaftar sebagai akun manual, tautan reset password sudah dikirim.'
        );
    }

    public function formulirResetPassword(Request $request, string $token)
    {
        $email = $request->query('email');
        abort_unless(is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL), 404);

        $record = (new SupabaseApi)->findPasswordResetToken(hash('sha256', $token));
        abort_unless(
            $record
                && strcasecmp($record['email'], $email) === 0
                && now()->lessThan(\Carbon\Carbon::parse($record['created_at'])->addMinutes(60)),
            410,
            'Tautan reset password sudah kedaluwarsa.'
        );

        return view('akun.reset-password', ['token' => $token, 'email' => $email]);
    }

    public function ubahPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'],
        ], [
            'password.regex' => 'Password minimal 8 karakter dan harus memiliki huruf kecil, huruf kapital, angka, dan simbol.',
            'password.confirmed' => 'Ulangi kata sandi yang sama.',
        ]);

        $api = new SupabaseApi;
        $record = $api->findPasswordResetToken(hash('sha256', $data['token']));
        abort_unless(
            $record
                && strcasecmp($record['email'], $data['email']) === 0
                && now()->lessThan(\Carbon\Carbon::parse($record['created_at'])->addMinutes(60)),
            410,
            'Tautan reset password sudah kedaluwarsa.'
        );

        $user = $api->findUserByEmail($data['email']);
        abort_unless($user && empty($user['google_id']), 404, 'Akun manual tidak ditemukan.');
        $api->updateUser($user['id'], ['password' => Hash::make($data['password'])]);
        $api->deletePasswordResetToken($user['email']);

        return redirect()->route('masuk')->with('pesan', 'Password berhasil diubah. Silakan masuk.');
    }

    private function tujuan(string $peran): string
    {
        return match ($peran) {
            'admin', 'superadmin' => route('admin.ringkasan'),
            default => route('akun'),
        };
    }

    private function simpanSesiPengguna(Request $request, array $user): void
    {
        $request->session()->put('pengguna', [
            'id' => $user['id'] ?? null,
            'email' => $user['email'],
            'nama' => $user['name'],
            'peran' => $user['peran'],
        ]);
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
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'],
            'setuju' => ['accepted'],
        ], [
            'nama.required' => 'Isi nama yang akan tampil di paspor.',
            'email.required' => 'Isi alamat email.',
            'email.email' => 'Alamat email belum benar.',
            'tanggal_lahir.required' => 'Isi tanggal lahir.',
            'password.regex' => 'Password minimal 8 karakter dan harus memiliki huruf kecil, huruf kapital, angka, dan simbol.',
            'password.confirmed' => 'Ulangi kata sandi yang sama.',
            'setuju.accepted' => 'Setujui ketentuan untuk membuat akun.',
        ]);

        $api = new SupabaseApi;
        if ($api->findUserByEmail($data['email'])) {
            return back()->withErrors(['email' => 'Email tersebut sudah terdaftar.'])->withInput();
        }
        $user = $api->createUser([
            'name' => $data['nama'],
            'email' => strtolower($data['email']),
            'tanggal_lahir' => $data['tanggal_lahir'],
            'password' => Hash::make($data['password']),
            'peran' => 'wisatawan',
            'email_verified_at' => null,
        ]);
        $request->session()->forget('pengguna');
        $this->kirimVerifikasi($user);

        return redirect()->route('verifikasi', ['email' => $user['email']])
            ->with('pesan', 'Akun dibuat. Periksa email untuk mengonfirmasi akun.');
    }

    public function keluar(Request $request)
    {
        $request->session()->forget('pengguna');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda');
    }

    public function verifikasi(Request $request)
    {
        return view('akun.verifikasi', [
            'email' => $request->query('email'),
            'pesan' => 'Periksa kotak masuk emailmu dan klik link konfirmasi untuk mengaktifkan akun.',
        ]);
    }

    public function konfirmasiEmail(Request $request, string $token)
    {
        $record = (new SupabaseApi)->findVerificationToken(hash('sha256', $token));
        abort_unless($record && now()->lessThan(\Carbon\Carbon::parse($record['expires_at'])), 410, 'Link verifikasi sudah kedaluwarsa.');

        $api = new SupabaseApi;
        $user = $api->findUserByEmail($record['email']);
        abort_unless($user, 404, 'Akun tidak ditemukan.');
        $user = $api->updateUser($user['id'], ['email_verified_at' => now()->toDateTimeString()]);
        $api->deleteVerificationToken($record['id']);
        $request->session()->forget('pengguna');

        return redirect()->route('masuk')->with('pesan', 'Email berhasil dikonfirmasi. Silakan masuk.');
    }

    public function kirimUlangVerifikasi(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = (new SupabaseApi)->findUserByEmail($data['email']);
        if ($user && empty($user['email_verified_at'])) {
            $this->kirimVerifikasi($user);
        }

        return redirect()->route('verifikasi', ['email' => $data['email']])
            ->with('pesan', 'Jika akun ditemukan dan belum terverifikasi, link baru sudah dikirim.');
    }

    private function kirimVerifikasi(array $user): void
    {
        $token = Str::random(64);
        (new SupabaseApi)->createVerificationToken([
            'email' => $user['email'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(24)->toDateTimeString(),
        ]);
        Mail::to($user['email'])->send(new VerifikasiEmail(route('verifikasi.konfirmasi', ['token' => $token])));
    }

    public function google(Request $request)
    {
        $config = config('services.google');
        abort_unless($config['client_id'] && $config['client_secret'] && $config['redirect'], 503, 'Login Google belum dikonfigurasi.');
        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);
        $request->session()->put('google_oauth_kembali', $this->aman($request->query('kembali')));
        $query = http_build_query([
            'client_id' => $config['client_id'], 'redirect_uri' => $config['redirect'],
            'response_type' => 'code', 'scope' => 'openid email profile',
            'state' => $state, 'access_type' => 'online', 'prompt' => 'select_account',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function googleCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('masuk')->withErrors(['email' => 'Login Google dibatalkan.']);
        }

        $expectedState = $request->session()->pull('google_oauth_state');
        $kembali = $request->session()->pull('google_oauth_kembali');
        abort_unless($request->filled('code') && $request->filled('state') && is_string($expectedState) && hash_equals($expectedState, $request->input('state')), 419);
        $config = config('services.google');
        $token = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $request->string('code')->toString(), 'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'], 'redirect_uri' => $config['redirect'],
            'grant_type' => 'authorization_code',
        ])->throw()->json();
        $profile = Http::withToken($token['access_token'])->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
        abort_unless(($profile['email_verified'] ?? false) && ! empty($profile['email']) && ! empty($profile['sub']), 422, 'Akun Google belum terverifikasi.');
        $api = new SupabaseApi;
        $user = $api->findUserByEmail($profile['email']);
        if (! $user) {
            $user = $api->createUser([
                'name' => $profile['name'] ?? Str::before($profile['email'], '@'),
                'email' => strtolower($profile['email']),
                'google_id' => $profile['sub'],
                'email_verified_at' => now()->toDateTimeString(),
                'peran' => 'wisatawan',
                'password' => Hash::make(Str::random(40)),
            ]);
        } elseif (($user['google_id'] ?? null) !== $profile['sub']) {
            $user = $api->updateUser($user['id'], [
                'google_id' => $profile['sub'],
                'email_verified_at' => now()->toDateTimeString(),
            ]);
        }
        $request->session()->regenerate();
        $this->simpanSesiPengguna($request, $user);

        return redirect($kembali ?? $this->tujuan($user->peran))->with('pesan', 'Login Google berhasil.');
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
