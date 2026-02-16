<?php

namespace Shetabit\Visitor\Resolvers\GeoIp;

use Shetabit\Visitor\Contracts\GeoIpResolver;
use Stevebauman\Location\Facades\Location;

class SteveBaumanResolver implements GeoIpResolver
{
    public function resolve(string $ip): ?array
    {
        $position = Location::get($ip);

        if (!$position) {
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
