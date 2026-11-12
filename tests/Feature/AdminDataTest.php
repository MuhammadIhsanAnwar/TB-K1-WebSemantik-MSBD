<?php

namespace Tests\Feature;

use App\Support\Database3CatalogWriter;
use App\Support\Katalog;
use App\Support\Kunjungan;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::setDefaultDriver('array');
        Cache::store('array')->flush();
        Katalog::forgetCache();

        parent::tearDown();
    }

    public function test_visit_counts_are_aggregated_without_recording_visitor_identity(): void
    {
        Cache::setDefaultDriver('array');
        Cache::store('array')->flush();

        Kunjungan::catat('beranda');
        Kunjungan::catat('tempat', 'istana-maimun');
        $summary = Kunjungan::ringkasan(1);

        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['rute']['beranda']);
        $this->assertSame(1, $summary['tempat']['istana-maimun']);
    }

    public function test_public_page_visits_are_counted_by_the_middleware(): void
    {
        Cache::setDefaultDriver('array');
        Cache::store('array')->flush();
        Http::fake(['*' => Http::response([
            'head' => ['vars' => ['s', 'p', 'o']],
            'results' => ['bindings' => []],
        ], 200)]);

        $this->get('/ketentuan')->assertOk();

        $summary = Kunjungan::ringkasan(1);
        $this->assertSame(1, $summary['total']);
        $this->assertSame(1, $summary['rute']['ketentuan']);
    }

    public function test_superadmin_can_open_account_management_but_admin_cannot(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $this->withSession(['pengguna' => [
            'id' => 1,
            'nama' => 'Super Admin',
            'email' => 'superadmin@example.test',
            'peran' => 'superadmin',
        ]])->get('/admin/akun')->assertOk()->assertSee('Kelola akun');

        $this->withSession(['pengguna' => [
            'id' => 2,
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'peran' => 'admin',
        ]])->get('/admin/akun')->assertForbidden();
    }

    public function test_superadmin_insight_and_place_creation_pages_render(): void
    {
        Cache::setDefaultDriver('array');
        Cache::store('array')->flush();
        config(['services.sparql.endpoint' => 'https://sparql.test/query']);
        Http::fake(['*' => fn (Request $request) => str_contains($request->url(), '/rest/v1/users')
            ? Http::response([], 200)
            : Http::response(['head' => ['vars' => ['s', 'p', 'o']], 'results' => ['bindings' => []]], 200)]);
        $session = ['pengguna' => [
            'id' => 1,
            'nama' => 'Super Admin',
            'email' => 'superadmin@example.test',
            'peran' => 'superadmin',
        ]];

        $this->withSession($session)->get('/admin/insight')->assertOk()->assertSee('Insight');
        $this->withSession($session)->get('/admin/tempat/baru')->assertOk()->assertSee('Tambah tempat wisata');
    }

    public function test_place_update_and_delete_use_the_configured_sparql_endpoint(): void
    {
        config(['services.sparql.endpoint' => 'https://sparql.test/repositories/medanvisit']);
        Http::fake(['sparql.test/*' => Http::response('', 204)]);
        $data = [
            'nama' => 'Tempat Baru',
            'ringkas' => 'Deskripsi diperbarui.',
            'kategori' => 'museum',
            'lat' => '3.58',
            'lng' => '98.67',
            'alamat' => 'Jalan Baru',
            'jam' => 'Setiap hari, 08.00–17.00',
            'sumber' => 'https://example.test/sumber',
            'foto' => '',
            'kecamatan' => '',
        ];

        Database3CatalogWriter::update('tempat-baru', $data, []);
        Database3CatalogWriter::delete('tempat-baru');

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) =>
            str_contains(urldecode($request->body()), 'INSERT')
            && str_contains(urldecode($request->body()), 'DELETE')
        );
        Http::assertSent(fn (Request $request) =>
            str_contains(urldecode($request->body()), 'rdf:Statement')
            && str_contains(urldecode($request->body()), 'res:tempat-baru')
        );
    }

    public function test_place_creation_is_sent_as_a_sparql_update(): void
    {
        config(['services.sparql.endpoint' => 'https://sparql.test/repositories/medanvisit']);
        Http::fake(['sparql.test/*' => Http::response('', 204)]);

        Database3CatalogWriter::create('tempat-baru', [
            'nama' => 'Tempat Baru',
            'ringkas' => 'Tempat wisata baru.',
            'kategori' => 'museum',
            'lat' => '3.58',
            'lng' => '98.67',
            'alamat' => 'Jalan Baru',
            'jam' => 'Setiap hari, 08.00–17.00',
            'sumber' => 'https://example.test/sumber',
            'foto' => '',
            'kecamatan' => '',
        ], []);

        Http::assertSent(fn (Request $request) =>
            str_contains(urldecode($request->body()), 'INSERT DATA')
            && str_contains(urldecode($request->body()), 'res:tempat-baru')
            && str_contains(urldecode($request->body()), 'mv:Museum')
        );
    }
}
