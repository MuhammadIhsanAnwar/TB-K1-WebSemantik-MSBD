<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'katalog';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->table('users', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('users', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable();
            }
            if (! $schema->hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique();
            }
        });

        $connection = $schema->getConnection();
        $connection->statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS ck_users_peran");
        $connection->statement("UPDATE users SET peran = 'admin' WHERE peran = 'kurator'");
        $connection->statement("UPDATE users SET peran = 'wisatawan' WHERE peran = 'mitra' OR peran IS NULL");
        $connection->statement("ALTER TABLE users ADD CONSTRAINT ck_users_peran CHECK (peran IN ('wisatawan', 'admin', 'superadmin'))");
        $connection->statement('ALTER TABLE users ENABLE ROW LEVEL SECURITY');
        $connection->statement('DROP POLICY IF EXISTS "server can insert users" ON users');
        $connection->statement('DROP POLICY IF EXISTS "server can read users by email" ON users');
        $connection->statement('DROP POLICY IF EXISTS "server can update users" ON users');
        $connection->statement('CREATE POLICY "server can insert users" ON users FOR INSERT TO anon, authenticated WITH CHECK (true)');
        $connection->statement('CREATE POLICY "server can read users by email" ON users FOR SELECT TO anon, authenticated USING (true)');
        $connection->statement('CREATE POLICY "server can update users" ON users FOR UPDATE TO anon, authenticated USING (true) WITH CHECK (true)');
    }

    public function down(): void
    {
        $connection = Schema::connection($this->connection)->getConnection();
        $connection->statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS ck_users_peran");
        $connection->statement("UPDATE users SET peran = 'kurator' WHERE peran = 'admin'");
        $connection->statement("ALTER TABLE users ADD CONSTRAINT ck_users_peran CHECK (peran IN ('wisatawan', 'mitra', 'kurator'))");
        $connection->statement('DROP POLICY IF EXISTS "server can insert users" ON users');
        $connection->statement('DROP POLICY IF EXISTS "server can read users by email" ON users');
        $connection->statement('DROP POLICY IF EXISTS "server can update users" ON users');
        Schema::connection($this->connection)->table('users', function (Blueprint $table) {
            $table->dropUnique('users_google_id_unique');
            $table->dropColumn(['tanggal_lahir', 'google_id']);
        });
    }
};
