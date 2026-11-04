@extends('layouts.situs')
@section('judul', 'Peringkat minggu ini')
@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul">
        <div><h1>Peringkat minggu ini</h1><p>{{ $awal->locale('id')->translatedFormat('j M') }} sampai {{ $akhir->locale('id')->translatedFormat('j M Y') }} · direset setiap Senin</p></div>
    </div>
    @include('partials.subnav-akun')
    @if ($papan)
        @if (count($papan) >= 3)
            <div class="podium" aria-hidden="true">
                @foreach ([1, 0, 2] as $i)
                    <div><span class="kecil">{{ $i + 1 }}.</span><b>{{ $papan[$i]['poin'] }}</b>{{ $papan[$i]['nama'] }}</div>
                @endforeach
            </div>
        @endif
        <div class="p-panel">
            <table class="p-tabel">
                <caption class="sr-only">Peringkat check-in minggu ini</caption>
                <thead><tr><th>Peringkat</th><th>Nama</th><th class="n">Stempel</th><th class="n">Poin</th></tr></thead>
                <tbody>
                    @foreach ($papan as $i => $b)
                        <tr @class(['baris-saya' => $b['email'] === $u['email']])>
                            <td>{{ $i + 1 }}</td><td>{{ $b['nama'] }}{{ $b['email'] === $u['email'] ? ' (kamu)' : '' }}</td><td class="n">{{ $b['stempel'] }}</td><td class="n">{{ $b['poin'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="kosong"><p>Belum ada check-in minggu ini. Check-in pertama langsung menempatkanmu di peringkat 1.</p><a class="tombol" href="{{ route('peta') }}">Cari tempat terdekat</a></div>
    @endif
</div>
@endsection
