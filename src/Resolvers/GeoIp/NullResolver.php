<?php

namespace Shetabit\Visitor\Resolvers\GeoIp;

use Shetabit\Visitor\Contracts\GeoIpResolver;

class NullResolver implements GeoIpResolver
{
    /**
     * @return array<string, mixed>|null
     */
    public function resolve(string $ip): array|null
    {
        return null;
    }
}
