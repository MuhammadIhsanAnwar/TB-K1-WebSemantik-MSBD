<?php

namespace Tests\Unit;

use App\Support\Katalog;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class KatalogRoutingTest extends TestCase
{
    private function panggilFungsiPrivate(
        string $fungsi,
        array $argumen
    ): mixed {
        $method = new ReflectionMethod(Katalog::class, $fungsi);

        return $method->invokeArgs(null, $argumen);
    }

    public function test_valhalla_returns_walking_distance_and_duration(): void
    {
        config([
            'services.routing.valhalla_url' => 'https://valhalla.test',
            'services.routing.timeout' => 15,
        ]);

        Http::fake([
            '*valhalla.test/route' => Http::response([
                'routes' => [[
                    'distance' => 939.149,
                    'duration' => 670.613,
                ]],
            ], 200),
        ]);

        $hasil = $this->panggilFungsiPrivate('ambilRuteApi', [
            ['lat' => 3.5897, 'lng' => 98.6738],
            ['lat' => 3.5952, 'lng' => 98.6722],
            'jalan',
        ]);

        $this->assertSame(939, $hasil['meter']);
        $this->assertSame(11, $hasil['menit']);

        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://valhalla.test/route'
            && $request['costing'] === 'pedestrian'
        );
    }

    public function test_osrm_returns_vehicle_distance_and_duration(): void
    {
        config([
            'services.routing.osrm_url' => 'https://osrm.test',
            'services.routing.timeout' => 15,
        ]);

        Http::fake([
            '*osrm.test/route/v1/driving/*' => Http::response([
                'routes' => [[
                    'distance' => 1600.4,
                    'duration' => 116.2,
                ]],
            ], 200),
        ]);

        $hasil = $this->panggilFungsiPrivate('ambilRuteApi', [
            ['lat' => 3.5897, 'lng' => 98.6738],
            ['lat' => 3.5952, 'lng' => 98.6722],
            'becak',
        ]);

        $this->assertSame(1600, $hasil['meter']);
        $this->assertSame(2, $hasil['menit']);

        Http::assertSent(fn (Request $request) =>
            str_contains($request->url(), 'osrm.test/route/v1/driving/')
        );
    }

    public function test_failed_routing_api_returns_null(): void
    {
        config([
            'services.routing.osrm_url' => 'https://osrm.test',
        ]);

        Http::fake([
            '*osrm.test/*' => Http::response([], 500),
        ]);

        $hasil = $this->panggilFungsiPrivate('ambilRuteApi', [
            ['lat' => 3.5897, 'lng' => 98.6738],
            ['lat' => 3.5952, 'lng' => 98.6722],
            'becak',
        ]);

        $this->assertNull($hasil);
    }

    public function test_becak_cost_uses_actual_route_distance(): void
    {
        config([
            'services.routing.osrm_url' => 'https://osrm.test',
        ]);

        Http::fake([
            '*osrm.test/*' => Http::response([
                'routes' => [[
                    'distance' => 1600.4,
                    'duration' => 116.2,
                ]],
            ], 200),
        ]);

        $hasil = $this->panggilFungsiPrivate('perpindahanDenganRute', [
            ['lat' => 3.5897, 'lng' => 98.6738],
            ['lat' => 3.5952, 'lng' => 98.6722],
            ['becak'],
        ]);

        $this->assertSame('Becak motor', $hasil['moda']);
        $this->assertSame(1600, $hasil['meter']);
        $this->assertSame(2, $hasil['menit']);
        $this->assertSame(15000, $hasil['biaya']);
    }
}
