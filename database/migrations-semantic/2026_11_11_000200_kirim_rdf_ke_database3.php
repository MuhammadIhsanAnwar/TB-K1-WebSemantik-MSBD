<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Http;

return new class extends Migration
{
    protected $connection = 'katalog';

    private const FILES = [
        'medanvisit-ontologi.ttl',
        'medanvisit-data.ttl',
    ];

    public function up(): void
    {
        $endpoint = (string) config('services.sparql.endpoint');
        $username = config('services.sparql.username');
        $password = config('services.sparql.password');

        if ($endpoint === '' || $username === null || $password === null) {
            throw new RuntimeException('Konfigurasi Database 3 belum lengkap untuk migration RDF.');
        }

        $http = Http::timeout((int) config('services.sparql.timeout', 30))
            ->withBasicAuth($username, $password)
            ->accept('application/sparql-results+json');

        foreach (self::FILES as $file) {
            $path = database_path('rdf/'.$file);
            if (! is_file($path)) {
                throw new RuntimeException("Berkas RDF tidak ditemukan: $path");
            }

            $update = $this->ubahMenjadiInsertData((string) file_get_contents($path));
            $response = $http->asForm()->post($endpoint, ['update' => $update]);
            if (! $response->successful()) {
                throw new RuntimeException(sprintf(
                    'Database 3 menolak migration RDF %s (HTTP %d): %s',
                    $file,
                    $response->status(),
                    str($response->body())->limit(400),
                ));
            }
        }
    }

    public function down(): void
    {
        $endpoint = (string) config('services.sparql.endpoint');
        $username = config('services.sparql.username');
        $password = config('services.sparql.password');

        if ($endpoint === '' || $username === null || $password === null) {
            throw new RuntimeException('Konfigurasi Database 3 belum lengkap untuk rollback RDF.');
        }

        $response = Http::timeout((int) config('services.sparql.timeout', 30))
            ->withBasicAuth($username, $password)
            ->asForm()
            ->post($endpoint, ['update' => 'CLEAR DEFAULT']);

        if (! $response->successful()) {
            throw new RuntimeException('Database 3 menolak rollback RDF (HTTP '.$response->status().').');
        }
    }

    private function ubahMenjadiInsertData(string $turtle): string
    {
        $prefix = [];
        $body = preg_replace_callback(
            '/^\s*@prefix\s+([A-Za-z][\w-]*):\s*<([^>]+)>\s*\.\s*$/mi',
            static function (array $match) use (&$prefix): string {
                $prefix[] = 'PREFIX '.$match[1].': <'.$match[2].'>';

                return '';
            },
            $turtle,
        );

        if ($body === null || $prefix === []) {
            throw new RuntimeException('Berkas RDF tidak memiliki deklarasi prefix yang valid.');
        }

        return implode("\n", $prefix)."\nINSERT DATA {\n".$body."\n}";
    }
};
