<?php

namespace Shetabit\Visitor\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Shetabit\Visitor\Middlewares\LogVisits;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Tests\Fixtures\CustomVisit;
use Shetabit\Visitor\Tests\TestCase;

/**
 * The visits kept in a database of their own, the tenant database of a multi-tenant application for instance, while the
 * models of the application stay in the default one.
 */
final class VisitsConnectionTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app) : void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('visitor.connection', 'tenant');
    }

    public function testTheMigrationsCreateTheTableInTheConnectionOfTheConfiguration() : void
    {
        $table = (string) config('visitor.table_name');

        $this->assertTrue(Schema::connection('tenant')->hasTable($table));
        $this->assertTrue(
            Schema::connection('tenant')->hasColumns($table, ['geo_raw', 'route_name', 'extra_attributes'])
        );
        $this->assertFalse(Schema::connection('testing')->hasTable($table));
    }

    public function testAVisitIsKeptInTheConnectionOfTheConfiguration() : void
    {
        $visit = visitor()->visit(null, ['mobile' => '09120000000']);

        $this->assertSame('tenant', $visit?->getConnectionName());
        $this->assertSame(1, DB::connection('tenant')->table((string) config('visitor.table_name'))->count());
        $this->assertSame(['mobile' => '09120000000'], Visit::query()->sole()->extra_attributes);
    }

    public function testTheVisitsOfAModelOfTheDefaultConnectionAreKeptInTheConnectionOfTheConfiguration() : void
    {
        $post = $this->createPost();

        $post->createVisitLog();

        $this->assertSame('testing', $post->getConnectionName());
        $this->assertSame('tenant', $post->visitLogs()->sole()->getConnectionName());
        $this->assertSame(1, $post->visitLogs()->count());
    }

    public function testTheMiddlewareLogsTheVisitsInTheConnectionOfTheConfiguration() : void
    {
        $this->get('/products')->assertOk();

        $this->assertSame(1, DB::connection('tenant')->table((string) config('visitor.table_name'))->count());
    }

    public function testAVisitModelOfTheApplicationIsKeptInTheConnectionOfTheConfiguration() : void
    {
        config()->set('visitor.model', CustomVisit::class);

        $this->assertSame('tenant', visitor()->visit()?->getConnectionName());
    }

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router) : void
    {
        $router->middleware(LogVisits::class)->get('/products', fn (): string => 'products');
    }
}
