<?php

namespace Shetabit\Visitor\Tests\Unit;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\OutOfQueriesException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Exceptions;
use InvalidArgumentException;
use Shetabit\Visitor\Resolvers\GeoIp\MaxMindResolver;
use Shetabit\Visitor\Resolvers\GeoIp\NullResolver;
use Shetabit\Visitor\Resolvers\GeoIp\SteveBaumanResolver;
use Shetabit\Visitor\Tests\Fixtures\FakeGeoIp2Provider;
use Shetabit\Visitor\Tests\TestCase;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\LocationServiceProvider;
use Stevebauman\Location\Position;
use Throwable;

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

    public function testTheMaxMindResolverAnswersWithTheCityOfTheIp() : void
    {
        $resolver = $this->maxMindResolver(['24.24.24.24' => FakeGeoIp2Provider::paris('24.24.24.24')]);

        $this->assertSame(
            [
                'ip' => '24.24.24.24',
                'country_code' => 'FR',
                'country_name' => 'France',
                'region_name' => 'Île-de-France',
                'city_name' => 'Paris',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
            $resolver->resolve('24.24.24.24')
        );
    }

    public function testTheMaxMindResolverAnswersWithNothingForAnIpTheDatabaseDoesNotHold() : void
    {
        Exceptions::fake();

        $resolver = $this->maxMindResolver(['24.24.24.24' => FakeGeoIp2Provider::paris('24.24.24.24')]);

        $this->assertNull($resolver->resolve('127.0.0.1'));

        Exceptions::assertNothingReported();
    }

    public function testTheMaxMindResolverLeavesOutWhatTheDatabaseDoesNotKnowOfTheIp() : void
    {
        $resolver = $this->maxMindResolver([
            '24.24.24.24' => ['country' => ['iso_code' => 'FR', 'names' => ['en' => 'France']]],
        ]);

        $this->assertSame(
            [
                'ip' => '24.24.24.24',
                'country_code' => 'FR',
                'country_name' => 'France',
                'region_name' => null,
                'city_name' => null,
                'latitude' => null,
                'longitude' => null,
            ],
            $resolver->resolve('24.24.24.24')
        );
    }

    public function testTheMaxMindResolverReportsALookupThatFailsAndAnswersWithNothing() : void
    {
        Exceptions::fake();

        $resolver = $this->maxMindResolver([
            '24.24.24.24' => new OutOfQueriesException('The license key is out of queries.'),
        ]);

        $this->assertNull($resolver->resolve('24.24.24.24'));

        Exceptions::assertReported(OutOfQueriesException::class);
    }

    public function testTheMaxMindResolverOpensTheDatabaseAtTheFirstLookupAndKeepsItOpen() : void
    {
        $opened = 0;
        $resolver = new MaxMindResolver(static function () use (&$opened): FakeGeoIp2Provider {
            $opened++;

            return new FakeGeoIp2Provider(['24.24.24.24' => FakeGeoIp2Provider::paris('24.24.24.24')]);
        });

        $this->assertSame(0, $opened);

        $paris = $resolver->resolve('24.24.24.24');
        $resolver->resolve('127.0.0.1');

        $this->assertSame('Paris', $paris['city_name'] ?? null);
        $this->assertSame(1, $opened);
    }

    public function testTheMaxMindResolverReportsADatabaseItCanNotOpenAndAnswersWithNothing() : void
    {
        Exceptions::fake();

        $resolver = new MaxMindResolver(static fn (): Reader => new Reader('/nowhere/GeoLite2-City.mmdb'));

        $this->assertNull($resolver->resolve('24.24.24.24'));

        Exceptions::assertReported(
            static fn (InvalidArgumentException $exception): bool => str_contains(
                $exception->getMessage(),
                '/nowhere/GeoLite2-City.mmdb'
            )
        );
    }

    public function testTheMaxMindResolverOpensADatabaseThatFailedToOpenAgainAtTheNextLookup() : void
    {
        Exceptions::fake();

        $attempts = 0;
        $resolver = new MaxMindResolver(static function () use (&$attempts): FakeGeoIp2Provider {
            if (++$attempts === 1) {
                throw new InvalidArgumentException('The database is not in place yet.');
            }

            return new FakeGeoIp2Provider(['24.24.24.24' => FakeGeoIp2Provider::paris('24.24.24.24')]);
        });

        $whileItIsMissing = $resolver->resolve('24.24.24.24');
        $onceItIsInPlace = $resolver->resolve('24.24.24.24');

        $this->assertNull($whileItIsMissing);
        $this->assertSame('Paris', $onceItIsInPlace['city_name'] ?? null);
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

    /**
     * @param array<string, array<string, mixed>|Throwable> $records the raw City record of each IP the database holds,
     *        or what looking it up fails with
     */
    private function maxMindResolver(array $records) : MaxMindResolver
    {
        return new MaxMindResolver(new FakeGeoIp2Provider($records));
    }
}
