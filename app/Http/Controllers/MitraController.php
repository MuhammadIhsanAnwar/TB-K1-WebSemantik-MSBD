<?php

namespace App\Http\Controllers;

use App\Support\Penyimpanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MitraController extends Controller
{
    private function email(Request $r): string
    {
        return $r->session()->get('pengguna.email');
    }

    private function usaha(Request $r): array
    {
        // kolom opsional yang tidak dikirim tidak tersimpan, jadi selalu digabung dengan nilai bawaan
        return array_merge(
            ['nama' => '', 'jenis' => 'kuliner', 'alamat' => '', 'jam' => '', 'deskripsi' => '', 'menu' => []],
            Cache::get('medanvisit.usaha.'.$this->email($r), []),
        );
    }

    private function voucherSaya(Request $r): array
    {
        return array_filter(Penyimpanan::voucher(), fn ($v) => $v['email'] === $this->email($r));
    }

    private function bagi(Request $r, array $tambahan = []): array
    {
        return $tambahan + ['usaha' => $this->usaha($r), 'u' => $r->session()->get('pengguna')];
    }

    public function listing(Request $request)
    {
        return view('mitra.listing', $this->bagi($request));
    }

    public function simpanListing(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:80'],
            'jenis' => ['required', 'in:kuliner,oleh-oleh,kerajinan,penginapan'],
            'alamat' => ['required', 'string', 'max:160'],
            'jam' => ['required', 'string', 'max:80'],
            'deskripsi' => ['nullable', 'string', 'max:400'],
            'menu' => ['array'], 'menu.*.nama' => ['nullable', 'string', 'max:60'], 'menu.*.harga' => ['nullable', 'integer', 'min:0'],
        ], ['nama.required' => 'Isi nama usaha.', 'alamat.required' => 'Isi alamat usaha.', 'jam.required' => 'Isi jam buka, misalnya "Setiap hari, 08.00–21.00".']);
        $data['menu'] = array_values(array_filter($data['menu'] ?? [], fn ($m) => ! empty($m['nama'])));
        $data['deskripsi'] ??= '';
        Cache::forever('medanvisit.usaha.'.$this->email($request), $data);

        return back()->with('pesan', 'Listing tersimpan.');
    }

    public function voucher(Request $request)
    {
        $penukaran = collect(Penyimpanan::penukaran());

        return view('mitra.voucher', $this->bagi($request, [
            'voucher' => array_map(fn ($v) => $v + ['ditukar' => $penukaran->where('voucher', $v['id'])->count()], $this->voucherSaya($request)),
        ]));
    }

    public function buatVoucher(Request $request)
    {
        $usaha = $this->usaha($request);
        if ($usaha['nama'] === '') {
            return back()->with('galat', 'Lengkapi listing usaha dulu, supaya wisatawan tahu voucher ini dari mana.');
        }
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'poin' => ['required', 'integer', 'between:10,500'],
            'stok' => ['required', 'integer', 'between:1,500'],
            'berlaku' => ['required', 'date', 'after:today'],
        ], ['nama.required' => 'Tulis isi voucher, misalnya "Diskon 10% bika ambon".', 'berlaku.after' => 'Tanggal berlaku harus setelah hari ini.']);
        Penyimpanan::buatVoucher($data + ['email' => $this->email($request), 'usaha' => $usaha['nama'], 'stok_awal' => (int) $data['stok']]);

        return back()->with('pesan', 'Voucher '.$data['nama'].' sudah bisa ditukar wisatawan.');
    }

    public function penukaran(Request $request)
    {
        $ids = array_keys($this->voucherSaya($request));

        return view('mitra.penukaran', $this->bagi($request, [
            'riwayat' => array_filter(Penyimpanan::penukaran(), fn ($p) => in_array($p['voucher'], $ids, true)),
        ]));
    }

    public function verifikasi(Request $request)
    {
        $data = $request->validate(['kode' => ['required', 'string', 'max:20']], ['kode.required' => 'Masukkan kode voucher dari layar wisatawan.']);
        $p = Penyimpanan::pakaiPenukaran($data['kode']);
        $milik = $p && array_key_exists($p['voucher'], $this->voucherSaya($request));

        return back()->with('hasil', match (true) {
            ! $p || ! $milik => ['status' => 'tidak', 'judul' => 'Kode tidak dikenal', 'teks' => 'Kode '.strtoupper($data['kode']).' bukan voucher usahamu.'],
            $p['sudah_dipakai'] => ['status' => 'dipakai', 'judul' => 'Sudah dipakai', 'teks' => 'Voucher '.$p['nama'].' ini sudah pernah ditukar.'],
            default => ['status' => 'valid', 'judul' => 'Valid', 'teks' => $p['nama'].', berikan potongannya sekarang.'],
        });
    }

    public function statistik(Request $request)
    {
        $ids = array_keys($this->voucherSaya($request));
        $tukar = collect(Penyimpanan::penukaran())->filter(fn ($p) => in_array($p['voucher'], $ids, true));
        $hari = collect(range(29, 0))->mapWithKeys(fn ($i) => [now('Asia/Jakarta')->subDays($i)->toDateString() => 0])->all();
        foreach ($tukar as $t) {
            $d = substr($t['waktu'], 0, 10);
            if (isset($hari[$d])) {
                $hari[$d]++;
            }
        }

        return view('mitra.statistik', $this->bagi($request, [
            'perHari' => $hari,
            'ditukar' => $tukar->count(),
            'dipakai' => $tukar->where('dipakai', true)->count(),
            'voucher' => count($ids),
        ]));
    }
}
