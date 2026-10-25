<?php

namespace Shetabit\Visitor\Resolvers\GeoIp;

use Closure;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\ProviderInterface;
use Shetabit\Visitor\Contracts\GeoIpResolver;
use Throwable;

class MaxMindResolver implements GeoIpResolver
{
    /**
     * The database reader, or the web service client, once it is opened.
     */
    private ProviderInterface|null $opened = null;

    /**
     * @param ProviderInterface|(Closure(): ProviderInterface) $provider a City database reader or MaxMind's web
     *        service client, or a closure that opens one at the first lookup
     */
    public function __construct(private readonly ProviderInterface|Closure $provider)
    {
    }

    /**
     * Resolve the given IP into its location.
     *
     * A database that cannot be opened or read, or a web service that cannot answer, is reported instead of thrown:
     * it costs the visit its location, not the request the visit is logged in.
     *
     * @return array<string, mixed>|null
     */
    public function resolve(string $ip): array|null
    {
        try {
            $city = $this->provider()->city($ip);
        } catch (AddressNotFoundException) {
            return null;
        } catch (Throwable $exception) {
            report($exception);

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

    /**
     * The provider, opened at the first lookup and kept open after it. One that fails to open is opened again at the
     * next lookup, so that a database put in place later is picked up.
     */
    private function provider(): ProviderInterface
    {
        return $this->opened ??= $this->provider instanceof Closure ? ($this->provider)() : $this->provider;
    }
}
