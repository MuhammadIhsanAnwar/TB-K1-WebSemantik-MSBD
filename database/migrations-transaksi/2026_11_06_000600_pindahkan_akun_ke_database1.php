<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const USER_COLUMNS = ['usaha_mitra', 'pesanan', 'checkin', 'riwayat_poin', 'ulasan', 'penukaran', 'rencana'];

    public function up(): void
    {
        $akun = DB::connection('katalog');
        $transaksi = DB::connection('transaksi');

        if (! Schema::connection('katalog')->hasTable('users')) {
            Schema::connection('katalog')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('peran', 12)->default('wisatawan');
                $table->string('no_hp', 20)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            if (! Schema::connection('katalog')->hasColumn('users', 'peran')) {
                Schema::connection('katalog')->table('users', fn (Blueprint $table) => $table->string('peran', 12)->default('wisatawan'));
            }
            if (! Schema::connection('katalog')->hasColumn('users', 'no_hp')) {
                Schema::connection('katalog')->table('users', fn (Blueprint $table) => $table->string('no_hp', 20)->nullable());
            }
        }

        $akun->statement(
            "ALTER TABLE users DROP CONSTRAINT IF EXISTS ck_users_peran;
             ALTER TABLE users ADD CONSTRAINT ck_users_peran CHECK (peran IN ('wisatawan', 'mitra', 'kurator'))"
        );

        if (! Schema::connection('transaksi')->hasTable('users')) {
            return;
        }

        $idMap = [];
        foreach ($transaksi->table('users')->get() as $user) {
            $bestaand = $akun->table('users')->where('email', $user->email)->first();
            if ($bestaand) {
                $idMap[(int) $user->id] = (int) $bestaand->id;
                continue;
            }

            $id = $akun->table('users')->insertGetId([
                'name' => $user->name,
                'email' => $user->email,
                'peran' => property_exists($user, 'peran') ? $user->peran : 'wisatawan',
                'no_hp' => property_exists($user, 'no_hp') ? $user->no_hp : null,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ], 'id');
            $idMap[(int) $user->id] = (int) $id;
        }

        $constraints = $transaksi->select(
            "SELECT c.relname AS table_name, con.conname AS constraint_name
             FROM pg_constraint con
             JOIN pg_class c ON c.oid = con.conrelid
             WHERE con.confrelid = 'public.users'::regclass"
        );
        foreach ($constraints as $constraint) {
            $transaksi->statement(sprintf(
                'ALTER TABLE "%s" DROP CONSTRAINT "%s"',
                str_replace('"', '""', $constraint->table_name),
                str_replace('"', '""', $constraint->constraint_name)
            ));
        }

        foreach (self::USER_COLUMNS as $table) {
            foreach ($idMap as $oldId => $newId) {
                if ($oldId !== $newId) {
                    $transaksi->table($table)->where('id_pengguna', $oldId)->update(['id_pengguna' => $newId]);
                }
            }
        }

        $transaksi->statement('DROP TABLE users');
    }

    public function down(): void
    {
        throw new RuntimeException('Akun tidak dikembalikan ke Database 2 secara otomatis.');
    }
};
