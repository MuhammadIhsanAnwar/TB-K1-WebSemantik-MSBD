<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Isi kedua database MedanVisit: DB 1 katalog dari database/data/*.json, lalu DB 2 transaksi uji coba.
     */
    public function run(): void
    {
        $this->call([KatalogSeeder::class, TransaksiSeeder::class]);
    }
}
