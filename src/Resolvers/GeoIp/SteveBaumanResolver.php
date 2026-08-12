<?php

namespace Shetabit\Visitor\Resolvers\GeoIp;

use Shetabit\Visitor\Contracts\GeoIpResolver;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

class SteveBaumanResolver implements GeoIpResolver
{
    /**
     * @return array<string, mixed>|null
     */
    public function resolve(string $ip): array|null
    {
        $position = Location::get($ip);

        if (!$position instanceof Position) {
            return null;
        }

        return [
            'ip'           => $position->ip,
            'country_code' => $position->countryCode,
            'country_name' => $position->countryName,
            'region_name'  => $position->regionName,
            'city_name'    => $position->cityName,
            'latitude'     => $position->latitude,
            'longitude'    => $position->longitude,
        ];
    }
}
