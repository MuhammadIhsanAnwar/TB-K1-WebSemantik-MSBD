<?php

namespace App\Http\Controllers;

use App\Support\Katalog;
use App\Support\Penyimpanan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TiketController extends Controller
{
    public const METODE = [
        'qris' => 'QRIS',
        'va' => 'Transfer virtual account bank',
        'ewallet' => 'Dompet digital',
        'kartu' => 'Kartu debit atau kredit',
    ];

    private function tempatBertiket(string $slug): array
    {
        $t = Katalog::get($slug);
        abort_unless($t && ! empty($t['tiket']), 404);

        return $t;
    }

    private function milik(Request $request, string $kode): array
    {
        $p = Penyimpanan::cariPesanan($kode);
        abort_unless($p && $p['email'] === $request->session()->get('pengguna.email'), 404);
        if ($p['status'] === 'menunggu' && now()->greaterThan($p['batas_bayar'])) {
            $p = Penyimpanan::ubahPesanan($kode, ['status' => 'kedaluwarsa']);
        }

        return $p;
    }

    public function pilih(Request $request, string $slug)
    {
        $t = $this->tempatBertiket($slug);
        $lama = $request->session()->get('pilihan');

        return view('tiket.pilih', ['t' => $t, 'lama' => ($lama['tempat'] ?? null) === $slug ? $lama : null]);
    }

    public function simpanPilihan(Request $request, string $slug)
    {
        $t = $this->tempatBertiket($slug);
        $aturan = ['tanggal' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(60)->toDateString()]];
        foreach (array_keys($t['tiket']) as $i => $jenis) {
            $aturan["jumlah.$i"] = ['required', 'integer', 'between:0,10'];
        }
        $data = $request->validate($aturan, [
            'tanggal.required' => 'Pilih tanggal kunjungan.',
            'tanggal.after_or_equal' => 'Tanggal kunjungan tidak boleh sebelum hari ini.',
            'tanggal.before_or_equal' => 'Pemesanan dibuka paling jauh 60 hari ke depan.',
        ]);

        $item = [];
        $i = 0;
        foreach ($t['tiket'] as $jenis => $harga) {
            $n = (int) $data['jumlah'][$i++];
            if ($n > 0) {
                $item[] = ['jenis' => $jenis, 'harga' => $harga, 'jumlah' => $n, 'subtotal' => $harga * $n];
            }
        }
        if (! $item) {
            return back()->withInput()->withErrors(['jumlah' => 'Pilih minimal satu tiket.']);
        }

        $request->session()->put('pilihan', ['tempat' => $slug, 'tanggal' => $data['tanggal'], 'item' => $item]);

        return redirect()->route('checkout');
    }

    public function checkout(Request $request)
    {
        $pilihan = $request->session()->get('pilihan');
        if (! $pilihan) {
            return redirect()->route('beranda');
        }

        return view('tiket.checkout', [
            'pilihan' => $pilihan,
            't' => Katalog::get($pilihan['tempat']),
            'total' => array_sum(array_column($pilihan['item'], 'subtotal')),
            'u' => $request->session()->get('pengguna'),
            'metode' => self::METODE,
        ]);
    }

    public function bayar(Request $request)
    {
        $pilihan = $request->session()->get('pilihan');
        abort_unless($pilihan, 419);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email'],
            'hp' => ['required', 'regex:/^(\+62|0)8[0-9]{7,11}$/'],
            'metode' => ['required', 'in:'.implode(',', array_keys(self::METODE))],
            'setuju' => ['accepted'],
        ], [
            'nama.required' => 'Isi nama pemesan.',
            'email.email' => 'Alamat email belum benar. E-tiket dikirim ke sini.',
            'hp.regex' => 'Nomor HP diawali 08 atau +62, contoh 081234567890.',
            'metode.required' => 'Pilih cara bayar.',
            'setuju.accepted' => 'Setujui ketentuan pembelian untuk melanjutkan.',
        ]);

        $p = Penyimpanan::buatPesanan([
            'email' => $request->session()->get('pengguna.email'),
            'pemesan' => ['nama' => $data['nama'], 'email' => $data['email'], 'hp' => $data['hp']],
            'tempat' => $pilihan['tempat'],
            'tanggal' => $pilihan['tanggal'],
            'item' => $pilihan['item'],
            'total' => array_sum(array_column($pilihan['item'], 'subtotal')),
            'metode' => $data['metode'],
        ]);
        $request->session()->forget('pilihan');

        return redirect()->route('pesanan', $p['kode']);
    }

    public function status(Request $request, string $kode)
    {
        $p = $this->milik($request, $kode);

        return view('tiket.status', ['p' => $p, 't' => Katalog::get($p['tempat']), 'metode' => self::METODE]);
    }

    /** Prototipe: menggantikan notifikasi payment gateway (webhook Midtrans) yang belum tersambung. */
    public function simulasi(Request $request, string $kode)
    {
        $p = $this->milik($request, $kode);
        if ($p['status'] === 'menunggu') {
            Penyimpanan::ubahPesanan($kode, ['status' => 'lunas', 'dibayar' => now('Asia/Jakarta')->toIso8601String()]);
        }

        return redirect()->route('pesanan', $kode);
    }

    public function batal(Request $request, string $kode)
    {
        $p = $this->milik($request, $kode);
        if ($p['status'] === 'menunggu') {
            Penyimpanan::ubahPesanan($kode, ['status' => 'dibatalkan']);
        }

        return redirect()->route('tiket.saya', ['tab' => 'dibatalkan'])->with('pesan', 'Pesanan '.$kode.' dibatalkan.');
    }

    public function daftar(Request $request)
    {
        $tab = $request->query('tab', 'aktif');
        $semua = collect(Penyimpanan::pesanan($request->session()->get('pengguna.email')))->map(function ($p) {
            if ($p['status'] === 'menunggu' && now()->greaterThan($p['batas_bayar'])) {
                $p = Penyimpanan::ubahPesanan($p['kode'], ['status' => 'kedaluwarsa']);
            }

            return $p;
        });
        $kelompok = [
            'aktif' => $semua->filter(fn ($p) => in_array($p['status'], ['menunggu', 'lunas'], true) && empty($p['dipakai']) && $p['tanggal'] >= now('Asia/Jakarta')->toDateString()),
            'selesai' => $semua->filter(fn ($p) => $p['status'] === 'lunas' && (! empty($p['dipakai']) || $p['tanggal'] < now('Asia/Jakarta')->toDateString())),
            'dibatalkan' => $semua->filter(fn ($p) => in_array($p['status'], ['dibatalkan', 'kedaluwarsa'], true)),
        ];

        return view('tiket.daftar', ['tab' => array_key_exists($tab, $kelompok) ? $tab : 'aktif', 'kelompok' => $kelompok]);
    }

    public function eTiket(Request $request, string $kode)
    {
        $p = $this->milik($request, $kode);
        abort_unless($p['status'] === 'lunas', 404);
        $t = Katalog::get($p['tempat']);
        $status = ! empty($p['dipakai']) ? 'dipakai' : ($p['tanggal'] < now('Asia/Jakarta')->toDateString() ? 'kedaluwarsa' : 'aktif');

        return view('tiket.etiket', ['p' => $p, 't' => $t, 'status' => $status, 'tanggal' => Carbon::parse($p['tanggal'])->locale('id')]);
    }
}
