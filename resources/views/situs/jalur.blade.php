@extends('layouts.situs')
@php
    use App\Support\Katalog;
    $fmtMenit = fn ($m) => intdiv($m, 60) ? intdiv($m, 60).' jam '.($m % 60).' mnt' : $m.' mnt';
@endphp
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
                    <ol class="mv-jalur-singgah" style="margin-top:14px">
                        @foreach ($j['singgah'] as $no => $s)
                            <li><b class="nums">{{ $no + 1 }}.</b> <span>{{ Katalog::get($s)['nama'] ?? $s }}</span></li>
                        @endforeach
                    </ol>
                </span>
                <dl class="mv-jalur-spek nums">
                    <div><dd>{{ count($j['singgah']) }} tempat</dd><dt>Perhentian</dt></div>
                    <div><dd>{{ number_format($j['meter'] / 1000, 1, ',', '.') }} km</dd><dt>Jarak rute</dt></div>
                    <div><dd>{{ $fmtMenit($j['menit']) }}</dd><dt>Durasi</dt></div>
                </dl>
            </a>
        @endforeach
    </div>
</div>
@endsection
