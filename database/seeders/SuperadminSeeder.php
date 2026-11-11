<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_SUPERADMIN_PASSWORD');
        if (! is_string($password) || $password === '') {
            throw new RuntimeException('SEED_SUPERADMIN_PASSWORD harus diisi di .env sebelum menjalankan seeder.');
        }

        $user = User::query()->firstOrNew([
            'email' => 'administrator@medanvisit.neoverse.my.id',
        ]);
        $user->name = 'Administrator MedanVisit';
        $user->peran = 'superadmin';
        $user->email_verified_at = now();
        $user->password = Hash::make($password);
        $user->save();
    }
}
