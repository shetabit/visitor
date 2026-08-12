<?php

namespace Shetabit\Visitor\Tests\Unit;

use Illuminate\Foundation\Application;
use Shetabit\Visitor\Resolvers\GeoIp\NullResolver;
use Shetabit\Visitor\Resolvers\GeoIp\SteveBaumanResolver;
use Shetabit\Visitor\Tests\TestCase;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\LocationServiceProvider;
use Stevebauman\Location\Position;

final class ResolversTest extends TestCase
{
    public function testTheNullResolverResolvesNothing() : void
    {
        $this->assertNull(new NullResolver()->resolve('24.24.24.24'));
    }

    public function testTheSteveBaumanResolverAnswersWithThePositionOfTheIp() : void
    {
        Location::fake(['24.24.24.24' => $this->position()]);

        $this->assertSame(
            [
                'ip' => '24.24.24.24',
                'country_code' => 'FR',
                'country_name' => 'France',
                'region_name' => 'Île-de-France',
                'city_name' => 'Paris',
                'latitude' => '48.8566',
                'longitude' => '2.3522',
            ],
            new SteveBaumanResolver()->resolve('24.24.24.24')
        );
    }

    public function testTheSteveBaumanResolverAnswersWithNothingForAnIpItCanNotPlace() : void
    {
        Location::fake(['24.24.24.24' => $this->position()]);

        $this->assertNull(new SteveBaumanResolver()->resolve('127.0.0.1'));
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app) : array
    {
        return [...parent::getPackageProviders($app), LocationServiceProvider::class];
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
