<?php

namespace Shetabit\Visitor\Tests\Fixtures;

use Shetabit\Visitor\Contracts\GeoIpResolver;

/**
 * A GeoIP resolver that answers with a fixed location, and counts how often it
 * was built.
 */
class FakeResolver implements GeoIpResolver
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(string $ip): array|null
    {
        return [
            'ip' => $ip,
            'country_code' => 'IR',
            'country_name' => 'Iran',
            'region_name' => 'Tehran',
            'city_name' => 'Tehran',
            'latitude' => '35.6892',
            'longitude' => '51.389',
        ];
    }
}
