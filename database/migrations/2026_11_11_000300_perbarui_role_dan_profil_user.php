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
    }

    public function down(): void
    {
        $connection = Schema::connection($this->connection)->getConnection();
        $connection->statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS ck_users_peran");
        $connection->statement("UPDATE users SET peran = 'kurator' WHERE peran = 'admin'");
        $connection->statement("ALTER TABLE users ADD CONSTRAINT ck_users_peran CHECK (peran IN ('wisatawan', 'mitra', 'kurator'))");
        Schema::connection($this->connection)->table('users', function (Blueprint $table) {
            $table->dropUnique('users_google_id_unique');
            $table->dropColumn(['tanggal_lahir', 'google_id']);
        });
    }
};
