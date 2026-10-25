<?php

namespace Shetabit\Visitor;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ReflectionClass;
use Shetabit\Visitor\Contracts\GeoIpResolver;
use Shetabit\Visitor\Contracts\UserAgentParser;
use Shetabit\Visitor\Exceptions\DriverNotFoundException;
use Shetabit\Visitor\Exceptions\ResolverNotFoundException;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Support\VisitModel;

class Visitor implements UserAgentParser, GeoIpResolver
{
    /**
     * Paths that are not logged.
     *
     * @var array<int, string>
     */
    protected array $except;

    /**
     * Name of the current user agent parser.
     */
    protected string $driver = '';

    /**
     * The user agent parser that is doing the work.
     */
    protected UserAgentParser|null $driverInstance = null;

    /**
     * Name of the current GeoIP resolver.
     */
    protected string $resolver = '';

    /**
     * The GeoIP resolver that is doing the work.
     */
    protected GeoIpResolver|null $resolverInstance = null;

    /**
     * Visitor (user) instance.
     */
    protected Model|null $visitor = null;

    /**
     * Visitor constructor.
     *
     * @param array<string, mixed> $config
     *
     * @throws Exception
     */
    public function __construct(protected Request $request, protected array $config)
    {
        $this->except = $config['except'] ?? [];
        $this->via($this->config['default'] ?? '', $this->config['resolver'] ?? '');
        $this->setVisitor($request->user());
    }

    /**
     * Change the driver and the resolver on the fly.
     *
     * @throws Exception
     */
    public function via(string $driver, string $resolver): static
    {
        $this->driver = $driver;
        $this->validateDriver();
        $this->driverInstance = null;

        $this->resolver = $resolver;
        $this->validateResolver();
        $this->resolverInstance = null;

        return $this;
    }

    /**
     * Retrieve request's data
     *
     * @return array<string, mixed>
     */
    public function request() : array
    {
        return $this->request->all();
    }

    /**
     * Retrieve user's ip.
     */
    public function ip() : string|null
    {
        return $this->request->ip();
    }

    /**
     * Retrieve request's url
     */
    public function url() : string
    {
        return $this->request->fullUrl();
    }

    /**
     * Retrieve request's referer
     */
    public function referer() : string|null
    {
        return $this->request->headers->get('referer');
    }

    /**
     * Retrieve request's method.
     */
    public function method() : string
    {
        return $this->request->getMethod();
    }

    /**
     * Retrieve http headers.
     *
     * @return array<string, array<int, string|null>>
     */
    public function httpHeaders() : array
    {
        return $this->request->headers->all();
    }

    /**
     * Retrieve agent.
     */
    public function userAgent() : string
    {
        return $this->request->userAgent() ?? '';
    }

    /**
     * Retrieve device's name.
     *
     * @throws Exception
     */
    public function device() : string
    {
        return $this->getDriverInstance()->device();
    }

    /**
     * Retrieve platform's name.
     *
     * @throws Exception
     */
    public function platform() : string
    {
        return $this->getDriverInstance()->platform();
    }

    /**
     * Retrieve browser's name.
     *
     * @throws Exception
     */
    public function browser() : string
    {
        return $this->getDriverInstance()->browser();
    }

    /**
     * Retrieve languages.
     *
     * @return array<int, string>
     *
     * @throws Exception
     */
    public function languages() : array
    {
        return $this->getDriverInstance()->languages();
    }

    /**
     * Resolve the given IP into a location, when the enrichment is enabled.
     *
     * @return array<string, mixed>|null
     *
     * @throws Exception
     */
    public function resolve(string $ip): array|null
    {
        if (!($this->config['geoip'] ?? false)) {
            return null;
        }

        return $this->getResolverInstance()->resolve($ip);
    }

    /**
     * Resolve the location of the current visitor.
     *
     * @return array<string, mixed>|null
     *
     * @throws Exception
     */
    public function geolocation(): array|null
    {
        $ip = $this->ip();

        return $ip === null ? null : $this->resolve($ip);
    }

    /**
     * Set visitor (user)
     */
    public function setVisitor(Model|null $user): static
    {
        $this->visitor = $user;

        return $this;
    }

    /**
     * Retrieve visitor (user)
     */
    public function getVisitor() : Model|null
    {
        return $this->visitor;
    }

    /**
     * Create a visit log.
     *
     * Answers with `null` for a request the `except` configuration excludes.
     *
     * @param array<string, mixed> $attributes columns of the visit model of the configuration to fill beside the ones
     *                                         the package fills, overriding them; only the fillable ones are kept
     *
     * @throws Exception
     */
    public function visit(Model|null $model = null, array $attributes = []): Visit|null
    {
        foreach ($this->except as $path) {
            if ($this->request->is($path)) {
                return null;
            }
        }

        $data = [...$this->prepareLog(), ...$attributes];

        $visit = $model !== null && method_exists($model, 'visitLogs')
            ? $model->visitLogs()->create($data)
            : VisitModel::name()::query()->create($data);

        return $visit instanceof Visit ? $visit : null;
    }

    /**
     * Retrieve online visitors.
     *
     * @param class-string<Model> $model a model that uses the `Visitor` trait
     *
     * @return Collection<int, Model>
     */
    public function onlineVisitors(string $model, int $seconds = 180): Collection
    {
        /** @var Builder<Model> $query */
        $query = app($model)->online($seconds);

        return $query->get();
    }

    /**
     * Determine if given visitor or current one is online.
     */
    public function isOnline(Model|null $visitor = null, int $seconds = 180): bool
    {
        $visitor ??= $this->getVisitor();

        if ($visitor === null) {
            return false;
        }

        return VisitModel::name()::query()
            ->whereHasMorph('visitor', $visitor::class, function (Builder $query) use ($visitor): void {
                $query->whereKey($visitor->getKey());
            })
            ->where('created_at', '>=', now()->subSeconds($seconds))
            ->exists();
    }

    /**
     * Prepare log's data.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    protected function prepareLog() : array
    {
        $log = [
            'method' => $this->method(),
            'request' => $this->request(),
            'url' => $this->url(),
            'referer' => $this->referer(),
            'languages' => $this->languages(),
            'useragent' => $this->userAgent(),
            'headers' => $this->httpHeaders(),
            'device' => $this->device(),
            'platform' => $this->platform(),
            'browser' => $this->browser(),
            'ip' => $this->ip(),
            'visitor_id' => $this->getVisitor()?->getKey(),
            'visitor_type' => $this->getVisitor()?->getMorphClass(),
        ];

        if (!empty($this->config['geoip'])) {
            $log['geo_raw'] = $this->geolocation();
        }

        return $log;
    }

    /**
     * Retrieve current driver instance or generate new one.
     *
     * @throws Exception
     */
    protected function getDriverInstance() : UserAgentParser
    {
        return $this->driverInstance ??= $this->getFreshDriverInstance();
    }

    /**
     * Get new driver instance
     *
     * @throws Exception
     */
    protected function getFreshDriverInstance() : UserAgentParser
    {
        $this->validateDriver();

        return app($this->driverClass());
    }

    /**
     * Validate driver.
     *
     * @throws Exception
     */
    protected function validateDriver() : void
    {
        if ($this->driver === '') {
            throw new DriverNotFoundException('Driver not selected or default driver does not exist.');
        }

        $driverClass = $this->driverClass();

        if ($driverClass === null || !class_exists($driverClass)) {
            throw new DriverNotFoundException('Driver not found in config file. Try updating the package.');
        }

        if (!new ReflectionClass($driverClass)->implementsInterface(UserAgentParser::class)) {
            throw new Exception("Driver must be an instance of Contracts\UserAgentParser.");
        }
    }

    /**
     * Retrieve current resolver instance or generate new one.
     *
     * @throws Exception
     */
    protected function getResolverInstance() : GeoIpResolver
    {
        return $this->resolverInstance ??= $this->getFreshResolverInstance();
    }

    /**
     * Get new resolver instance
     *
     * @throws Exception
     */
    protected function getFreshResolverInstance() : GeoIpResolver
    {
        $this->validateResolver();

        return app($this->resolverClass());
    }

    /**
     * Validate resolver.
     *
     * @throws Exception
     */
    protected function validateResolver() : void
    {
        if ($this->resolver === '') {
            throw new ResolverNotFoundException('Resolver not selected or default resolver does not exist.');
        }

        $resolverClass = $this->resolverClass();

        if ($resolverClass === null || !class_exists($resolverClass)) {
            throw new ResolverNotFoundException('Resolver not found in config file. Try updating the package.');
        }

        if (!new ReflectionClass($resolverClass)->implementsInterface(GeoIpResolver::class)) {
            throw new Exception("Resolver must be an instance of Contracts\GeoIpResolver.");
        }
    }

    /**
     * The class of the driver that is selected.
     *
     * @return class-string|null
     */
    private function driverClass() : string|null
    {
        return $this->config['drivers'][$this->driver] ?? null;
    }

    /**
     * The class of the resolver that is selected.
     *
     * @return class-string|null
     */
    private function resolverClass() : string|null
    {
        return $this->config['resolvers'][$this->resolver] ?? null;
    }
}
