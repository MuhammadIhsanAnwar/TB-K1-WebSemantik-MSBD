<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class KirimRdf extends Command
{
    protected $signature = 'rdf:kirim {--replace : Hapus seluruh graph Database 3 sebelum mengirim data}';

    protected $description = 'Kirim data wisata RDF ke Database 3 SPARQL';

    public function handle(): int
    {
        $endpoint = (string) config('services.sparql.statements_endpoint');
        $username = config('services.sparql.username');
        $password = config('services.sparql.password');
        $berkas = database_path('rdf/medanvisit-data.ttl');

        if ($endpoint === '' || $username === null || $password === null) {
            $this->error('Konfigurasi Database 3 belum lengkap.');

            return self::FAILURE;
        }

        if (! is_file($berkas)) {
            $this->line('File RDF belum ada, menjalankan rdf:ekspor...');
            if ($this->call('rdf:ekspor') !== self::SUCCESS || ! is_file($berkas)) {
                $this->error('Ekspor RDF gagal.');

                return self::FAILURE;
            }
        }

        $http = Http::timeout((int) config('services.sparql.timeout', 30))
            ->withBasicAuth($username, $password);

        try {
            if ($this->option('replace')) {
                $hapus = $http->delete($endpoint);
                if (! $hapus->successful()) {
                    throw new RuntimeException('Database 3 menolak penghapusan graph lama (HTTP '.$hapus->status().').');
                }
            }

            $jawab = $http->withBody((string) file_get_contents($berkas), 'text/turtle')->post($endpoint);
            if (! $jawab->successful()) {
                throw new RuntimeException('Database 3 menolak RDF (HTTP '.$jawab->status().'): '.str($jawab->body())->limit(240));
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        Cache::forget('database3.catalog.triples.v2');
        $this->info('Data wisata berhasil dikirim ke Database 3.');

        return self::SUCCESS;
    }
}
