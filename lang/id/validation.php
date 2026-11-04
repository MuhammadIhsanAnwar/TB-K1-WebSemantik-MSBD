<?php

// Pesan validasi bahasa Indonesia (APP_LOCALE=id). Pesan khusus per formulir ada di controller.
return [
    'accepted' => 'Centang :attribute untuk melanjutkan.',
    'after' => ':Attribute harus setelah :date.',
    'after_or_equal' => ':Attribute tidak boleh sebelum :date.',
    'array' => ':Attribute harus berupa daftar.',
    'before_or_equal' => ':Attribute tidak boleh setelah :date.',
    'between' => [
        'numeric' => ':Attribute harus antara :min dan :max.',
        'string' => ':Attribute harus :min sampai :max karakter.',
    ],
    'confirmed' => 'Konfirmasi :attribute tidak sama.',
    'date' => ':Attribute bukan tanggal yang benar.',
    'different' => ':Attribute dan :other harus berbeda.',
    'email' => 'Alamat email belum benar.',
    'in' => 'Pilihan :attribute tidak tersedia.',
    'integer' => ':Attribute harus bilangan bulat.',
    'max' => [
        'numeric' => ':Attribute paling besar :max.',
        'string' => ':Attribute paling panjang :max karakter.',
        'array' => ':Attribute paling banyak :max pilihan.',
    ],
    'min' => [
        'numeric' => ':Attribute paling kecil :min.',
        'string' => ':Attribute minimal :min karakter.',
        'array' => 'Pilih minimal :min :attribute.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'regex' => 'Format :attribute belum benar.',
    'required' => 'Isi :attribute.',
    'string' => ':Attribute harus berupa teks.',

    'attributes' => [
        'email' => 'email', 'password' => 'kata sandi', 'nama' => 'nama', 'judul' => 'judul', 'isi' => 'isi cerita',
        'ringkas' => 'ringkasan', 'alamat' => 'alamat', 'jam' => 'jam buka', 'lat' => 'lintang', 'lng' => 'bujur',
        'poin' => 'poin', 'stok' => 'stok', 'berlaku' => 'tanggal berlaku', 'kode' => 'kode', 'sumber' => 'sumber',
        'subjek' => 'subjek', 'objek' => 'objek', 'predikat' => 'predikat', 'tanggal' => 'tanggal kunjungan',
        'hp' => 'nomor HP', 'metode' => 'cara bayar', 'setuju' => 'persetujuan', 'bintang' => 'bintang', 'teks' => 'ulasan',
        'minat' => 'minat', 'durasi' => 'lama waktu', 'moda' => 'cara berpindah', 'mulai' => 'tempat mulai',
        'query' => 'query', 'pesan' => 'pertanyaan', 'peran' => 'peran',
    ],
];
