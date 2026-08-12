<?php

namespace Shetabit\Visitor\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Shetabit\Visitor\Middlewares\LogVisits;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Tests\TestCase;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\LocationServiceProvider;
use Stevebauman\Location\Position;

/**
 * The GeoIP enrichment, over requests that go through the whole framework.
 */
final class GeoIpEnrichmentTest extends TestCase
{
    public function testItAddsTheLocationOfTheVisitorToTheVisit() : void
    {
        config()->set('visitor.geoip', true);

        Location::fake(['127.0.0.1' => $this->position()]);

        $this->get('/products')->assertOk();

        $this->assertSame(
            [
                'ip' => '127.0.0.1',
                'country_code' => 'FR',
                'country_name' => 'France',
                'region_name' => 'Île-de-France',
                'city_name' => 'Paris',
                'latitude' => '48.8566',
                'longitude' => '2.3522',
            ],
            Visit::query()->sole()->geo_raw
        );
    }

    public function testItLeavesTheLocationEmptyWhenTheResolverFindsNothing() : void
    {
        config()->set('visitor.geoip', true);

        Location::fake(['24.24.24.24' => $this->position()]);

        $this->get('/products')->assertOk();

        $this->assertNull(Visit::query()->sole()->geo_raw);
    }

    public function testItAsksNoResolverWhileTheEnrichmentIsDisabled() : void
    {
        // The enrichment is disabled by the configuration that ships with the
        // package, so the location of a visitor stays unasked for even when a
        // resolver would find one.
        Location::fake(['127.0.0.1' => $this->position()]);

        $this->get('/products')->assertOk();

        $this->assertNull(Visit::query()->sole()->geo_raw);
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app) : array
    {
        return [...parent::getPackageProviders($app), LocationServiceProvider::class];
    }

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router) : void
    {
        $router->middleware(LogVisits::class)->get('/products', fn (): string => 'products');
    }

    private function position() : Position
    {
        $position = new Position();

        $position->countryCode = 'FR';
        $position->countryName = 'France';
        $position->regionName = 'Île-de-France';
        $position->cityName = 'Paris';
        $position->latitude = '48.8566';
        $position->longitude = '2.3522';

        return $position;
    }
}
