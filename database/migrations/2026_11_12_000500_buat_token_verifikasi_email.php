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
        $schema->create('email_verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        $connection = $schema->getConnection();
        $connection->statement('ALTER TABLE email_verification_tokens ENABLE ROW LEVEL SECURITY');
        $connection->statement('CREATE POLICY "server can manage verification tokens" ON email_verification_tokens FOR ALL TO anon, authenticated USING (true) WITH CHECK (true)');
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('email_verification_tokens');
    }
};
