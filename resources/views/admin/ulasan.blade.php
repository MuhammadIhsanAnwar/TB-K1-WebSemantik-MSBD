@extends('layouts.panel')
@php use App\Support\Katalog; $label = ['menunggu' => 'Menunggu', 'tayang' => 'Tayang', 'ditolak' => 'Ditolak']; @endphp
@section('judul', 'Moderasi ulasan')

@section('isi')
<div class="p-judul"><div><h1>Moderasi ulasan</h1><p class="sub">Ulasan wisatawan tayang di halaman tempat setelah disetujui.</p></div></div>
<nav class="p-tabs" aria-label="Status ulasan">
    @foreach ($label as $k => $l)<a href="{{ route('admin.ulasan', ['tab' => $k]) }}" @if ($tab === $k) aria-current="true" @endif>{{ $l }}<span class="n">{{ $jumlah[$k] ?? 0 }}</span></a>@endforeach
</nav>
@if ($ulasan)
    <div class="p-baris">
        @foreach ($ulasan as $ul)
            <div style="align-items:flex-start">
                <span class="dua" style="flex:1">
                    <b>{{ Katalog::get($ul['tempat'])['nama'] }} · <x-bintang :n="$ul['bintang']" /></b>
                    <span style="font-size:14px;color:var(--p-text);margin-top:6px">{{ $ul['teks'] }}</span>
                    <span>{{ $ul['nama'] }} · {{ $ul['email'] }} · {{ \Illuminate\Support\Carbon::parse($ul['waktu'])->locale('id')->translatedFormat('j M Y, H.i') }}</span>
                </span>
                @if ($tab === 'menunggu')
                    <span style="display:flex;gap:8px;flex:none">
                        <form method="post" action="{{ route('admin.ulasan.moderasi', $ul['id']) }}">@csrf<input type="hidden" name="keputusan" value="tayang"><button class="p-btn p-btn--utama" type="submit">Tayangkan</button></form>
                        <form method="post" action="{{ route('admin.ulasan.moderasi', $ul['id']) }}">@csrf<input type="hidden" name="keputusan" value="ditolak"><button class="p-btn p-btn--bahaya" type="submit">Tolak</button></form>
                    </span>
                @else
                    <span class="p-pill {{ $tab === 'tayang' ? 'pos' : 'neg' }}">{{ $label[$tab] }}</span>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div class="p-kosong">{{ ['menunggu' => 'Tidak ada ulasan yang menunggu. Ulasan baru muncul di sini setelah wisatawan menulisnya.', 'tayang' => 'Belum ada ulasan yang ditayangkan.', 'ditolak' => 'Belum ada ulasan yang ditolak.'][$tab] }}</div>
@endif
@endsection
