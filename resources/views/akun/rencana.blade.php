@extends('layouts.situs')
@section('judul', 'Rencana saya')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul"><div><h1>Rencana saya</h1><p>Rencana yang sudah disimpan, bisa dibuka dan dibagikan lewat tautan.</p></div><a class="p-btn p-btn--utama" href="{{ route('rencana') }}">Susun rencana baru</a></div>
    @include('partials.subnav-akun')
    @if ($rencana)
        <div class="p-panel">
            <table class="p-tabel jadi-daftar">
                <thead><tr><th>Rencana</th><th>Minat</th><th class="n">Durasi</th></tr></thead>
                <tbody>
                    @foreach ($rencana as $r)
                        <tr>
                            <td><a class="nama" href="{{ route('rencana.bagikan', $r['kode']) }}">{{ $r['judul'] }}</a><span class="sub">Mulai dari {{ \App\Support\Katalog::get($r['mulai'])['nama'] }}, disimpan {{ \Illuminate\Support\Carbon::parse($r['waktu'])->locale('id')->translatedFormat('j M Y') }}</span></td>
                            <td class="sekunder">{{ collect($r['minat'])->map(fn ($m) => \App\Support\Katalog::KATEGORI[$m])->implode(', ') }}</td>
                            <td class="n">{{ $r['durasi'] }} jam</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="kosong"><p>Belum ada rencana tersimpan. Susun rute, lalu simpan supaya bisa dibagikan.</p><a class="tombol" href="{{ route('rencana') }}">Susun rute</a></div>
    @endif
</div>
@endsection
