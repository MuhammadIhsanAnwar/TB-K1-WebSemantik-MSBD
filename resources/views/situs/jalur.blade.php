@extends('layouts.situs')
@php $fmtMenit = fn ($m) => intdiv($m, 60) ? intdiv($m, 60).' jam '.($m % 60).' menit' : $m.' menit'; @endphp
@section('judul', 'Jalur tematik')
@section('foto-kaki', 'istana-maimun-1900.jpg')

@section('isi')
<header class="kepala-halaman kepala-halaman--besar">
    <h1>Jalur tematik</h1>
    <p>Rute jalan kaki dan becak motor yang disusun dari hubungan di graf. Jarak dan waktu dihitung dari letak setiap tempat, termasuk waktu singgah.</p>
</header>
<div class="isi-halaman">
    <div class="jalur-indeks">
        @foreach ($jalur as $j)
            <a href="{{ route('jalur.detail', $j['slug']) }}">
                <x-foto :berkas="$j['foto']" :alt="$j['nama']" :kecil="true" :segera="true" />
                <span>
                    <h2>{{ $j['nama'] }}</h2>
                    <p style="margin-top:12px">{{ $j['ringkas'] }}</p>
                </span>
                <span class="angka">
                    <b>{{ count($j['singgah']) }} tempat</b>
                    {{ number_format($j['meter'] / 1000, 1, ',', '.') }} km, {{ $fmtMenit($j['menit']) }}
                </span>
            </a>
        @endforeach
    </div>
</div>
@endsection
