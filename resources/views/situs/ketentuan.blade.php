@extends('layouts.situs')
@section('judul', 'Ketentuan & privasi')

@section('tanpa-kaki', '1')

@section('isi')
<div class="alat">
    <div class="alat__judul"><div><h1>Ketentuan & privasi</h1><p>Berlaku untuk pembelian tiket dan akun MedanVisit.</p></div></div>
    <div class="ketentuan p-panel">
        <div class="pesan ketentuan__catatan" role="note">MedanVisit masih prototipe tugas besar. Pembayaran belum tersambung ke payment gateway, dan harga tiket bertanda “contoh” belum dicek ke pengelola tempat.</div>

        <section class="ketentuan__bagian" aria-labelledby="k-tiket">
            <h2 id="k-tiket">Pembelian tiket</h2>
            <ol>
                <li>E-tiket berlaku untuk satu kali masuk pada tanggal yang dipilih, untuk jumlah dan jenis tiket yang tertera.</li>
                <li>Pembayaran harus selesai dalam 15 menit setelah pesanan dibuat. Lewat dari itu, pesanan kedaluwarsa dan tiket tidak terbit.</li>
                <li>Tiket yang sudah dibayar tidak dapat dikembalikan. Pesanan yang belum dibayar bisa dibatalkan kapan saja dari halaman pesanan.</li>
                <li>Tunjukkan kode QR di loket. Petugas memindai kode itu dan menandainya sudah dipakai.</li>
                <li>Jam buka dan harga mengikuti pengelola tempat. Bila tempat tutup pada tanggal kunjungan, hubungi administrator@medanvisit.neoverse.my.id.</li>
            </ol>
        </section>

        <section class="ketentuan__bagian" aria-labelledby="k-akun">
            <h2 id="k-akun">Data akun</h2>
            <ul>
                <li>Nama dan email dipakai untuk ulasan dan pengiriman e-tiket.</li>
                <li>Nomor HP hanya dipakai untuk pesanan tiket.</li>
                <li>Ulasan tayang setelah diperiksa kurator, dengan nama yang kamu isi.</li>
                <li>Kata kunci pencarian yang tidak menemukan hasil dicatat tanpa identitas, supaya kurator tahu tempat apa yang perlu ditambahkan.</li>
            </ul>
        </section>
    </div>
</div>
@endsection
