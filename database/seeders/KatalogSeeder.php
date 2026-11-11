<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
/**
 * @deprecated Data wisata tidak lagi disimpan di PostgreSQL. Gunakan `php artisan rdf:kirim`.
 */
class KatalogSeeder extends Seeder
{
    private function baca(string $nama): array
    {
        return json_decode(file_get_contents(database_path("data/$nama.json")), true, flags: JSON_THROW_ON_ERROR);
    }

    public function run(): void
    {
        $this->command?->warn('KatalogSeeder dilewati: data wisata harus dikirim ke Database 3 dengan php artisan rdf:kirim.');
    }
}
