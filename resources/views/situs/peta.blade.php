@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $warnaKat = ['sejarah' => '#e3a51c', 'religi' => '#6a4c9c', 'kuliner' => '#c9541e', 'museum' => '#2f6690', 'ruang-publik' => '#2e7d4f', 'belanja' => '#9b6b3d', 'rekreasi-air' => '#2f8a8a'];
    $json = collect($tempat)->map(fn ($t) => [
        'slug' => $t['slug'],
        'nama' => $t['nama'],
        'kecamatan' => $t['kecamatan'],
        'lat' => $t['lat'],
        'lng' => $t['lng'],
        'kategori' => $t['kategori'],
        'url' => route('tempat', $t['slug']),
        'harga' => Katalog::rupiah(Katalog::hargaTermurah($t)),
        'hargaAngka' => Katalog::hargaTermurah($t) ?? 0,
        'tiket' => ! empty($t['tiket']) ? route('tiket.pilih', $t['slug']) : null,
        'buka' => Katalog::jadwal($t),
        'rating' => $t['rating'] ?? 0,
        'jumlahUlasan' => $t['jumlahUlasan'] ?? 0,
        'jumlahKunjungan' => $t['jumlahKunjungan'] ?? 0,
        'jumlahSuka' => $t['jumlahSuka'] ?? 0,
    ])->values();
    $perKategori = collect($tempat)->countBy('kategori');
@endphp
@section('judul', 'Peta wisata')
@section('tanpa-kaki', '1')

@section('isi')
<div class="mv-peta-ruang">
    <aside class="mv-peta-samping" data-lembar-peta data-tinggi="daftar" aria-label="Saringan dan daftar tempat wisata">
        <div class="mv-peta-kepala">
            <div class="mv-peta-kepala__baris">
                <h1>Peta wisata</h1>
                <div class="mv-peta-toggle-lembar" role="group" aria-label="Tinggi daftar ponsel">
                    <button type="button" data-tinggi-peta="intip" aria-pressed="false">Peta</button>
                    <button type="button" data-tinggi-peta="daftar" aria-pressed="true">Daftar</button>
                    <button type="button" data-tinggi-peta="penuh" aria-pressed="false">Penuh</button>
                </div>
            </div>

            <div class="mv-peta-cari">
                <div>
                    <label class="sr-only" for="cari-di-peta">Cari nama tempat atau kecamatan</label>
                    <input id="cari-di-peta" type="search" placeholder="Cari tempat atau kecamatan…" autocomplete="off" data-peta-cari style="width:100%">
                </div>
                <div>
                    <label class="sr-only" for="urut-peta">Urutkan tempat</label>
                    <select id="urut-peta" data-peta-urut>
                        <option value="bawaan">Urutan utama</option>
                        <option value="terdekat">Terdekat</option>
                        <option value="termurah">Biaya terendah</option>
                        <option value="rating">Rating tertinggi</option>
                        <option value="kunjungan">Paling banyak dikunjungi</option>
                        <option value="disukai">Paling disukai</option>
                    </select>
                </div>
            </div>

            <div class="mv-peta-chip" role="group" aria-label="Kategori tempat">
                @foreach (Katalog::KATEGORI as $k => $label)
                    @continue(! $perKategori->get($k))
                   <button type="button" data-saring-kategori="{{ $k }}" aria-pressed="true">
                    <span class="mv-titik-warna" style="--warna-kat: {{ $warnaKat[$k] ?? '#e3a51c' }}" aria-hidden="true"></span>
                    <span>{{ $label }}</span>
                </button>
                @endforeach
            </div>

            <div class="mv-peta-chip" role="group" aria-label="Saringan cepat">
                <button type="button" data-saring="buka" aria-pressed="false">Buka sekarang</button>
                <button type="button" data-saring="tiket" aria-pressed="false">Bisa pesan tiket</button>
                <button type="button" data-lokasi-saya aria-pressed="false">Dekat lokasimu</button>
            </div>
        </div>

        <div class="mv-peta-status">
            <span data-peta-hitung role="status">{{ count($tempat) }} dari {{ count($tempat) }} tempat</span>
            <span data-pesan-lokasi role="status" aria-live="polite" style="font-weight:500;color:var(--mv-redup)"></span>
        </div>

        <div class="mv-peta-daftar" data-peta-daftar>
            @foreach ($tempat as $t)
                @php $berkas = $t['foto'] ?: 'peta-medan-1930-kota-lama.jpg'; @endphp
                <article class="mv-peta-kartu" data-slug="{{ $t['slug'] }}">
                    <button type="button" data-fokus-peta="{{ $t['slug'] }}" aria-label="Tunjukkan {{ $t['nama'] }} di peta" style="padding:0;border:0;background:none">
                        <x-foto :berkas="$berkas" alt="" :kecil="true" />
                    </button>
                    <div>
                        <a href="{{ route('tempat', $t['slug']) }}" style="text-decoration:none">
                            <b>{{ $t['nama'] }}</b>
                        </a>
                        <span class="mv-peta-kartu__meta" style="display:flex;justify-content:space-between;gap:8px"><span>{{ Katalog::KATEGORI[$t['kategori']] }}</span><b class="nums" style="color:var(--mv-hijau)"><x-harga :tempat="$t" /></b></span>
                        <span class="mv-peta-kartu__meta">{{ $t['kecamatan'] }}, {{ $t['jam'] }}</span>
                        <div class="mv-peta-kartu__meta" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:5px">
                        <span style="display:inline-flex;align-items:center;gap:4px;white-space:nowrap">
                        <span style="color:#e6a23c;letter-spacing:1px">★</span>
                        <b>{{ ($t['jumlahUlasan'] ?? 0) > 0 ? number_format($t['rating'], 1, ',', '.') : '-' }}</b>
                        @if(($t['jumlahUlasan'] ?? 0) > 0)
                            <span>({{ $t['jumlahUlasan'] }} ulasan)</span>
                        @else
                            <span>Belum ada rating</span>
                        @endif
                    </span>
                        <span>
                            <span aria-hidden="true">👁</span>
                            {{ number_format($t['jumlahKunjungan'] ?? 0, 0, ',', '.') }} kunjungan
                        </span>
                    </div>
                        <span class="mv-peta-kartu__jarak nums" data-jarak></span>
                        <form method="POST" action="{{ route('tempat.sukai', $t['slug']) }}">
                            @csrf
                            <button
                                type="submit"
                                style="border:1px solid var(--mv-kertas-2);background:var(--mv-kertas);padding:5px 10px;border-radius:8px;cursor:pointer;color:#d9485f"
                            >
                                ❤️ {{ number_format($t['jumlahSuka'] ?? 0, 0, ',', '.') }} suka
                            </button>
                        </form>
                        <div style="display:flex;gap:10px;align-items:center;margin-top:6px">
                            <button type="button" data-fokus-peta="{{ $t['slug'] }}" style="padding:2px 8px;min-height:30px;border:1px solid var(--mv-kertas-2);background:var(--mv-kertas);color:var(--mv-hijau);font:600 0.75rem/1 var(--font-text)">Lihat di peta</button>
                            <a href="{{ route('tempat', $t['slug']) }}" style="font:600 0.75rem/1 var(--font-text);color:var(--mv-hijau);padding:6px 0">Buka halaman</a>
                        </div>
                    </div>
                </article>
            @endforeach

            <div class="kosong" data-peta-kosong hidden style="padding:24px 16px;text-align:center">
                <p style="margin-inline:auto">Tidak ada tempat yang cocok dengan saringan ini.</p>
                <button class="mv-btn mv-btn--hijau mv-btn--kecil" type="button" data-hapus-saring style="margin-top:12px">Atur ulang saringan</button>
            </div>
        </div>
    </aside>

    <div class="mv-peta-kanvas" data-peta data-peta-mode="alat" data-tempat="{{ json_encode($json) }}" tabindex="0" aria-label="Peta interaktif tempat wisata Kota Medan">
        <p class="peta-diam" style="padding:24px">Peta sedang dimuat. Daftar tempat di panel samping tetap dapat digunakan sepenuhnya.</p>
    </div>
</div>
@endsection
