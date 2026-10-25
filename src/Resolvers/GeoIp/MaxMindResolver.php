<?php

namespace Shetabit\Visitor\Resolvers\GeoIp;

use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\ProviderInterface;
use Shetabit\Visitor\Contracts\GeoIpResolver;

class MaxMindResolver implements GeoIpResolver
{
    /**
     * @param ProviderInterface $provider a GeoIP2 or GeoLite2 City database reader, or MaxMind's web service client
     */
    public function __construct(private readonly ProviderInterface $provider)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(string $ip): array|null
    {
        try {
            $city = $this->provider->city($ip);
        } catch (AddressNotFoundException) {
            return null;
        }

        return [
            'ip'           => $ip,
            'country_code' => $city->country->isoCode,
            'country_name' => $city->country->name,
            'region_name'  => $city->mostSpecificSubdivision->name,
            'city_name'    => $city->city->name,
            'latitude'     => $city->location->latitude,
            'longitude'    => $city->location->longitude,
        ];
    }
}
