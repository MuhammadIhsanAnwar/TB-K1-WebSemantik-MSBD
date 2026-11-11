<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeder database transaksi dijalankan terpisah setelah migration DB 2 diaktifkan.
     * Data wisata dikirim ke Database 3 melalui perintah rdf:kirim, bukan melalui seeder PostgreSQL.
     */
    public function run(): void
    {
        $this->command?->info('Tidak ada seeder PostgreSQL untuk data wisata. Gunakan php artisan rdf:kirim untuk Database 3.');
    }
}
