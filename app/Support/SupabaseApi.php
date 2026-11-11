<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseApi
{
    public function __construct(private readonly string $database = 'katalog') {}

    public function findUserByEmail(string $email): ?array
    {
        return $this->getRows('users', ['email' => 'eq.'.strtolower($email)])[0] ?? null;
    }

    public function ping(string $table): void
    {
        $this->request()
            ->get($this->url($table), ['select' => '*', 'limit' => 1])
            ->throw();
    }

    public function createUser(array $user): array
    {
        $rows = $this->request()
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post($this->url('users'), $user)
            ->throw()
            ->json();

        return $rows[0] ?? throw new RuntimeException('Supabase tidak mengembalikan akun yang dibuat.');
    }

    public function updateUser(int|string $id, array $attributes): array
    {
        $rows = $this->request()
            ->withHeaders(['Prefer' => 'return=representation'])
            ->patch($this->url('users').'?'.http_build_query(['id' => 'eq.'.$id]), $attributes)
            ->throw()
            ->json();

        return $rows[0] ?? throw new RuntimeException('Supabase tidak menemukan akun untuk diperbarui.');
    }

    public function createVerificationToken(array $token): array
    {
        return $this->insert('email_verification_tokens', $token);
    }

    public function findVerificationToken(string $hash): ?array
    {
        return $this->getRows('email_verification_tokens', ['token_hash' => 'eq.'.$hash])[0] ?? null;
    }

    public function deleteVerificationToken(int|string $id): void
    {
        $this->request()
            ->delete($this->url('email_verification_tokens').'?'.http_build_query(['id' => 'eq.'.$id]))
            ->throw();
    }

    private function getRows(string $table, array $filters = []): array
    {
        return $this->request()
            ->get($this->url($table), array_merge(['select' => '*'], $filters))
            ->throw()
            ->json();
    }

    private function insert(string $table, array $attributes): array
    {
        $rows = $this->request()
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post($this->url($table), $attributes)
            ->throw()
            ->json();

        return $rows[0] ?? throw new RuntimeException('Supabase tidak mengembalikan data yang dibuat.');
    }

    private function request(): PendingRequest
    {
        $config = config('services.supabase.'.$this->database);
        abort_unless($config['url'] && $config['secret_key'], 503, 'Konfigurasi Supabase belum lengkap.');

        return Http::acceptJson()
            ->withHeaders(['apikey' => $config['publishable_key']])
            ->timeout(15);
    }

    private function url(string $table): string
    {
        return rtrim(config('services.supabase.'.$this->database.'.url'), '/').'/rest/v1/'.$table;
    }
}
