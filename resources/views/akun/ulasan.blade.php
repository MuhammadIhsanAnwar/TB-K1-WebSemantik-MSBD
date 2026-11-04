@extends('layouts.situs')
@section('judul', 'Ulasan saya')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul"><div><h1>Ulasan saya</h1><p>Ulasan tayang setelah diperiksa kurator.</p></div></div>
    @include('partials.subnav-akun')
    @if ($ulasan)
        <div class="p-panel">
            <table class="p-tabel jadi-daftar">
                <thead><tr><th>Tempat</th><th>Ulasan</th><th>Bintang</th><th class="n">Status</th></tr></thead>
                <tbody>
                    @foreach ($ulasan as $ul)
                        @php $t = \App\Support\Katalog::get($ul['tempat']); @endphp
                        <tr>
                            <td><a class="nama" href="{{ route('tempat', $t['slug']) }}">{{ $t['nama'] }}</a><span class="sub">{{ \Illuminate\Support\Carbon::parse($ul['waktu'])->locale('id')->translatedFormat('j M Y') }}</span></td>
                            <td class="sekunder">{{ \Illuminate\Support\Str::limit($ul['teks'], 90) }}</td>
                            <td class="sekunder"><x-bintang :n="$ul['bintang']" /></td>
                            <td class="n"><span class="p-pill {{ ['tayang' => 'pos', 'ditolak' => 'neg', 'menunggu' => 'warn'][$ul['status']] }}">{{ ['tayang' => 'Tayang', 'ditolak' => 'Ditolak', 'menunggu' => 'Menunggu'][$ul['status']] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="kosong"><p>Belum ada ulasan. Setelah berkunjung, tulis ulasan di halaman tempat itu.</p><a class="tombol" href="{{ route('peta') }}">Cari tempat</a></div>
    @endif
</div>
@endsection
