<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB 1 (koneksi "katalog"): menautkan akun admin ke satu tempat wisata.
 *
 * tempat_slug berisi slug resource RDF di Database 3 (contoh: "istana-maimun").
 * Kolom ini nullable: wisatawan dan superadmin tidak punya tempat.
 * UNIQUE: satu tempat hanya punya satu akun admin. PostgreSQL mengizinkan
 * banyak nilai NULL pada kolom UNIQUE, jadi akun tanpa tempat tidak bentrok.
 */
return new class extends Migration
{
    protected $connection = 'katalog';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasColumn('users', 'tempat_slug')) {
            $schema->table('users', function (Blueprint $table) {
                $table->string('tempat_slug', 120)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasColumn('users', 'tempat_slug')) {
            $schema->table('users', function (Blueprint $table) {
                $table->dropUnique('users_tempat_slug_unique');
                $table->dropColumn('tempat_slug');
            });
        }
    }
};
