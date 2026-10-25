<?php

namespace Shetabit\Visitor\Tests\Fixtures;

use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;
use GeoIp2\ProviderInterface;

/**
 * A MaxMind provider that knows the IPs it was given, as a City database
 * would answer for them.
 */
class FakeGeoIp2Provider implements ProviderInterface
{
    /**
     * @param array<string, array<string, mixed>> $records the raw City record of each IP
     */
    public function __construct(private readonly array $records)
    {
    }

    public function country(string $ipAddress): Country
    {
        return $this->city($ipAddress);
    }

    public function city(string $ipAddress): City
    {
        if (!isset($this->records[$ipAddress])) {
            throw new AddressNotFoundException("The address {$ipAddress} is not in the database.");
        }

        return new City($this->records[$ipAddress]);
    }

    /**
     * The raw record a City database holds for Paris.
     *
     * @return array<string, mixed>
     */
    public static function paris(string $ip): array
    {
        return [
            'country' => ['iso_code' => 'FR', 'names' => ['en' => 'France']],
            'subdivisions' => [['iso_code' => 'IDF', 'names' => ['en' => 'Île-de-France']]],
            'city' => ['names' => ['en' => 'Paris']],
            'location' => ['latitude' => 48.8566, 'longitude' => 2.3522],
            'traits' => ['ip_address' => $ip],
        ];
    }
}
