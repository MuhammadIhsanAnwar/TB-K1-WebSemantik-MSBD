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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
        return view('akun.masuk', [
            'kembali' => $this->aman($request->query('kembali')),
            'tempatSorotan' => $this->tempatSorotan(),
        ]);
    }

    public function prosesMasuk(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc'],
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
            'foto_profil' => $user['foto_profil'] ?? null,
            'tempat_slug' => $user['tempat_slug'] ?? null,
        ]);
    }

    public function daftar(Request $request)
    {
        return view('akun.daftar', [
            'kembali' => $this->aman($request->query('kembali')),
            'tempatSorotan' => $this->tempatSorotan(),
        ]);
    }

    private function tempatSorotan(): array
    {
        $tempat = Katalog::tempat();
        $berfoto = array_filter($tempat, fn ($item) => ! empty($item['foto']));

        return array_slice(array_values($berfoto ?: $tempat), 0, 3);
    }

    public function prosesDaftar(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email:rfc'],
            'no_hp' => ['required', 'digits_between:10,15', 'regex:/^08[0-9]+$/'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'],
            'setuju' => ['accepted'],
        ], [
            'nama.required' => 'Isi nama yang akan tampil di akun.',
            'email.required' => 'Isi alamat email.',
            'email.email' => 'Alamat email belum benar.',
            'no_hp.required' => 'Isi nomor HP.',
            'no_hp.digits_between' => 'Nomor HP harus terdiri dari 10 sampai 15 angka.',
            'no_hp.regex' => 'Nomor HP harus berupa angka dan diawali 08.',
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
            'no_hp' => $data['no_hp'] ?? null,
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
        $request->session()->regenerate();
        $this->simpanSesiPengguna($request, $user);

        if (! empty($user['google_id'])) {
            return redirect()->route('akun.pengaturan')
                ->with('pesan', 'Email berhasil dikonfirmasi. Lengkapi tanggal lahir dan nomor HP akun Google kamu.');
        }

        return redirect()->route('beranda')->with('pesan', 'Email berhasil dikonfirmasi. Selamat datang di MedanVisit.');
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
                'email_verified_at' => null,
                'peran' => 'wisatawan',
                'password' => Hash::make(Str::random(40)),
            ]);
        } elseif (($user['google_id'] ?? null) !== $profile['sub']) {
            $user = $api->updateUser($user['id'], [
                'google_id' => $profile['sub'],
            ]);
        }
        if (empty($user['email_verified_at'])) {
            $this->kirimVerifikasi($user);

            return redirect()->route('verifikasi', ['email' => $user['email']])
                ->with('pesan', 'Periksa email untuk mengonfirmasi akun Google sebelum masuk.');
        }
        $request->session()->regenerate();
        $this->simpanSesiPengguna($request, $user);

        if ($this->profilBelumLengkap($user)) {
            return redirect()->route('akun.pengaturan')
                ->with('pesan', 'Lengkapi nomor HP dan tanggal lahir untuk menyelesaikan akun Google.');
        }

        return redirect($kembali ?? $this->tujuan($user['peran']))->with('pesan', 'Login Google berhasil.');
    }

    public function pengaturan(Request $request)
    {
        $u = $this->pengguna($request);
        $user = (new SupabaseApi)->findUserByEmail($u['email']);
        abort_unless($user, 404, 'Akun tidak ditemukan.');

        return view('akun.pengaturan', ['user' => $user]);
    }

    public function simpanPengaturan(Request $request)
    {
        $u = $this->pengguna($request);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'no_hp' => ['required', 'digits_between:10,15', 'regex:/^08[0-9]+$/'],
            'foto_profil' => ['nullable', 'image', 'max:2048'],
            'password' => ['nullable', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'],
        ], [
            'nama.required' => 'Isi nama.',
            'tanggal_lahir.required' => 'Isi tanggal lahir.',
            'no_hp.required' => 'Isi nomor HP.',
            'no_hp.digits_between' => 'Nomor HP harus terdiri dari 10 sampai 15 angka.',
            'no_hp.regex' => 'Nomor HP harus berupa angka dan diawali 08.',
            'foto_profil.image' => 'Foto profil harus berupa gambar.',
            'foto_profil.max' => 'Foto profil maksimal 2 MB.',
            'password.regex' => 'Password minimal 8 karakter dan harus memiliki huruf kecil, huruf kapital, angka, dan simbol.',
            'password.confirmed' => 'Ulangi kata sandi yang sama.',
        ]);

        $api = new SupabaseApi;
        $user = $api->findUserByEmail($u['email']);
        abort_unless($user, 404, 'Akun tidak ditemukan.');
        $attributes = [
            'name' => $data['nama'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'no_hp' => $data['no_hp'],
        ];
        if (! empty($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }
        $fotoBaru = null;
        if ($request->hasFile('foto_profil')) {
            $direktori = public_path('storage/profil_picture');
            File::ensureDirectoryExists($direktori);

            $namaFile = Str::slug($data['nama']) ?: 'pengguna';
            $idAkun = Str::slug((string) ($user['id'] ?? '')) ?: substr(hash('sha256', strtolower($user['email'])), 0, 12);
            $namaFile .= '-'.$idAkun.'-'.Str::random(8).'.'.$request->file('foto_profil')->extension();
            $request->file('foto_profil')->move($direktori, $namaFile);
            $fotoBaru = 'profil_picture/'.$namaFile;
            $attributes['foto_profil'] = $fotoBaru;
        }

        try {
            $updated = $api->updateUser($user['id'], $attributes);
        } catch (\Throwable $exception) {
            if ($fotoBaru !== null) {
                File::delete(public_path('storage/'.$fotoBaru));
            }

            throw $exception;
        }

        if ($fotoBaru !== null && ! empty($user['foto_profil']) && $user['foto_profil'] !== $fotoBaru) {
            $fotoLama = basename($user['foto_profil']);
            if (str_starts_with($user['foto_profil'], 'profil_picture/')) {
                File::delete(public_path('storage/profil_picture/'.$fotoLama));
            } else {
                Storage::disk('public')->delete($user['foto_profil']);
            }
        }
        $this->simpanSesiPengguna($request, $updated);

        return redirect()->route('akun.pengaturan')->with('pesan', 'Pengaturan akun berhasil disimpan.');
    }

    private function profilBelumLengkap(array $user): bool
    {
        return empty($user['no_hp']) || empty($user['tanggal_lahir']);
    }

    public function akun(Request $request)
    {
        $u = $this->pengguna($request);

        return view('akun.akun', [
            'u' => $u,
            'pesanan' => count(Penyimpanan::pesanan((int) $u['id'], aman: true)),
            'rencana' => count(Penyimpanan::rencana((int) $u['id'], aman: true)),
            'ulasan' => count(Penyimpanan::ulasan(idPengguna: (int) $u['id'], aman: true)),
        ]);
    }

    public function ulasanSaya(Request $request)
    {
        return view('akun.ulasan', ['ulasan' => Penyimpanan::ulasan(idPengguna: (int) $this->pengguna($request)['id'])]);
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
