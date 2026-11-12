<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Membuat satu akun admin untuk tiap tempat wisata (69 tempat).
 *
 * Aman dijalankan berulang: akun dicari lewat email (firstOrNew), jadi tidak
 * pernah ada akun ganda, dan password akun yang sudah ada TIDAK di-reset.
 *
 *   php artisan db:seed --class=AdminTempatSeeder
 *
 * Daftar tempat dibaca dari database/rdf/medanvisit-data.ttl (sumber yang sama
 * dengan Database 3), jadi seeder tidak perlu koneksi ke AllegroGraph.
 */
class AdminTempatSeeder extends Seeder
{
    private const DOMAIN = 'medanvisit.neoverse.my.id';

    /** Kelas RDF yang termasuk mv:TempatWisata (lihat medanvisit-ontologi.ttl). */
    private const KELAS_TEMPAT = [
        'SitusSejarah', 'Museum', 'RumahIbadah', 'TempatKuliner',
        'RuangPublik', 'PusatBelanja', 'RekreasiAir',
    ];

    public function run(): void
    {
        $sandi = (string) env('SEED_ADMIN_TEMPAT_PASSWORD', 'admin123');
        $tempat = $this->daftarTempat();

        $baru = 0;
        foreach ($tempat as $slug => $nama) {
            $user = User::query()->firstOrNew([
                'email' => $slug.'@'.self::DOMAIN,
            ]);

            if (! $user->exists) {
                $user->password = Hash::make($sandi);
                $user->email_verified_at = now();
                $baru++;
            }

            $user->name = 'Admin '.$nama;
            $user->peran = 'admin';
            $user->tempat_slug = $slug;
            $user->save();
        }

        $this->command?->info(count($tempat)." tempat diproses, {$baru} akun baru dibuat.");
    }

    /** @return array<string,string> slug => nama tempat */
    private function daftarTempat(): array
    {
        $berkas = database_path('rdf/medanvisit-data.ttl');
        $isi = is_file($berkas) ? file_get_contents($berkas) : false;
        if ($isi === false) {
            throw new RuntimeException("Berkas RDF tidak ditemukan: {$berkas}");
        }

        $pola = '/^res:([a-z0-9-]+)\s+a\s+mv:(?:'.implode('|', self::KELAS_TEMPAT).')\s*;\s*rdfs:label\s+"([^"]+)"/m';
        preg_match_all($pola, $isi, $cocok, PREG_SET_ORDER);

        $tempat = [];
        foreach ($cocok as $baris) {
            $tempat[$baris[1]] = $baris[2];
        }

        if ($tempat === []) {
            throw new RuntimeException('Tidak ada tempat wisata yang terbaca dari berkas RDF.');
        }

        return $tempat;
    }
}
