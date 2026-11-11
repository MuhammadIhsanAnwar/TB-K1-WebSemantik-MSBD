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
        if (! $schema->hasColumn('users', 'foto_profil')) {
            $schema->table('users', function (Blueprint $table) {
                $table->string('foto_profil')->nullable();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasColumn('users', 'foto_profil')) {
            $schema->table('users', function (Blueprint $table) {
                $table->dropColumn('foto_profil');
            });
        }
    }
};
