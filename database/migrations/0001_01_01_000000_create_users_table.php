<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // akun pengguna disimpan di database aplikasi (DB 1)
    protected $connection = 'katalog';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection($this->connection)->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('peran', 12)->default('wisatawan');
            $table->date('tanggal_lahir')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('no_hp', 20)->nullable();
            $table->string('foto_profil')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::connection($this->connection)->create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::connection($this->connection)->getConnection()->statement(
            "ALTER TABLE users ADD CONSTRAINT ck_users_peran CHECK (peran IN ('wisatawan', 'admin', 'superadmin'))"
        );

        $connection = Schema::connection($this->connection)->getConnection();
        $connection->statement('ALTER TABLE users ENABLE ROW LEVEL SECURITY');
        $connection->statement('CREATE POLICY "server can insert users" ON users FOR INSERT TO anon, authenticated WITH CHECK (true)');
        $connection->statement('CREATE POLICY "server can read users by email" ON users FOR SELECT TO anon, authenticated USING (true)');
        $connection->statement('CREATE POLICY "server can update users" ON users FOR UPDATE TO anon, authenticated USING (true) WITH CHECK (true)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('users');
        Schema::connection($this->connection)->dropIfExists('password_reset_tokens');
        Schema::connection($this->connection)->dropIfExists('sessions');
    }
};
