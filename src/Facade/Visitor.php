<?php

namespace Shetabit\Visitor\Facade;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Facade;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Visitor as VisitorManager;

/**
 * The visitor of the current request, as a Laravel facade.
 *
 * @method static VisitorManager via(string $driver, string $resolver)
 * @method static array<string, mixed> request()
 * @method static string|null ip()
 * @method static string url()
 * @method static Route|null route()
 * @method static string|null routeName()
 * @method static string|null referer()
 * @method static string method()
 * @method static array<string, array<int, string|null>> httpHeaders()
 * @method static string userAgent()
 * @method static string device()
 * @method static string platform()
 * @method static string browser()
 * @method static array<int, string> languages()
 * @method static array<string, mixed>|null resolve(string $ip)
 * @method static array<string, mixed>|null geolocation()
 * @method static VisitorManager setVisitor(Model|null $user)
 * @method static Model|null getVisitor()
 * @method static Visit|null visit(Model|null $model = null)
 * @method static Collection<int, Model> onlineVisitors(string $model, int $seconds = 180)
 * @method static bool isOnline(Model|null $visitor = null, int $seconds = 180)
 *
 * @see VisitorManager
 */
class Visitor extends Facade
{
    /**
     * The name the visitor is bound to in the service container.
     */
    public const string SERVICE_NAME = 'shetabit-visitor';

    /**
     * Get the registered name of the component.
     */
    public static function getFacadeAccessor(): string
    {
        return self::SERVICE_NAME;
    }
}
