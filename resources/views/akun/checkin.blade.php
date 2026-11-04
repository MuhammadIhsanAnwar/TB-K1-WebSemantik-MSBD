@extends('layouts.situs')
@section('judul', 'Stempel didapat')
@section('tanpa-kepala', '1')
@section('tanpa-kaki', '1')

@section('isi')
<div class="sukses">
    <div class="sukses__isi">
        <x-stempel :tempat="$t" :tanggal="\Illuminate\Support\Carbon::parse($c['waktu'])->format('d.m.Y')" :besar="true" />
        <h1>Stempel didapat</h1>
        <p>Kamu berada {{ number_format($c['jarak_m'], 0, ',', '.') }} m dari {{ $t['nama'] }}.</p>
        <div class="sukses__poin"><span>+10 poin</span><span>{{ $poin['total'] }} poin sekarang</span></div>
        @foreach ($badgeBaru as $b)
            <div class="sukses__badge"><span class="kecil" style="font-weight:700">Badge baru</span><b>{{ $b['nama'] }}</b><span>{{ $b['keterangan'] }}</span></div>
        @endforeach
        <div class="sukses__aksi">
            <a class="tombol" href="{{ route('paspor') }}">Lihat paspor</a>
            <a class="tombol tombol--garis" style="color:var(--on-green);box-shadow:inset 0 0 0 2px var(--on-green-muted)" href="{{ route('tempat', $t['slug']) }}">Kembali ke {{ $t['nama'] }}</a>
        </div>
    </div>
</div>
@endsection
