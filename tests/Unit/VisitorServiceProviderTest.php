<?php

namespace Shetabit\Visitor\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Shetabit\Visitor\Facade\Visitor as VisitorFacade;
use Shetabit\Visitor\Provider\VisitorServiceProvider;
use Shetabit\Visitor\Resolvers\GeoIp\MaxMindResolver;
use Shetabit\Visitor\Tests\TestCase;
use Shetabit\Visitor\Visitor;

final class VisitorServiceProviderTest extends TestCase
{
    public function testItBindsTheVisitorToTheContainer() : void
    {
        $this->assertInstanceOf(Visitor::class, $this->app->make(VisitorFacade::SERVICE_NAME));
    }

    public function testTheVisitorIsSharedForTheLengthOfARequest() : void
    {
        $visitor = $this->app->make(VisitorFacade::SERVICE_NAME);

        $this->assertSame($visitor, $this->app->make(VisitorFacade::SERVICE_NAME));

        // It is bound `scoped`, so a long running worker builds a new one for
        // every request instead of answering with the data of the first one.
        $this->app->forgetScopedInstances();

        $this->assertNotSame($visitor, $this->app->make(VisitorFacade::SERVICE_NAME));
    }

    public function testTheVisitorCanBeResolvedByItsClassName() : void
    {
        $this->assertSame(
            $this->app->make(VisitorFacade::SERVICE_NAME),
            $this->app->make(Visitor::class)
        );
    }

    public function testTheFacadeAndTheHelperResolveTheVisitor() : void
    {
        $this->assertSame(VisitorFacade::SERVICE_NAME, VisitorFacade::getFacadeAccessor());
        $this->assertSame($this->app->make(VisitorFacade::SERVICE_NAME), VisitorFacade::getFacadeRoot());
        $this->assertSame($this->app->make(VisitorFacade::SERVICE_NAME), visitor());
    }

    public function testItAddsAVisitorMacroToTheRequest() : void
    {
        $this->assertTrue(Request::hasMacro('visitor'));
        // @phpstan-ignore method.notFound (the macro is added by the provider)
        $this->assertSame(visitor(), $this->app->make('request')->visitor());
    }

    public function testItMergesTheConfigurationOfThePackage() : void
    {
        $this->assertSame('jenssegers', config('visitor.default'));
        $this->assertSame('shetabit_visits', config('visitor.table_name'));
        $this->assertSame(['login', 'register'], config('visitor.except'));
        $this->assertFalse(config('visitor.geoip'));
        $this->assertSame('stevebauman', config('visitor.resolver'));
        $this->assertArrayHasKey('jenssegers', config('visitor.drivers'));
        $this->assertArrayHasKey('null', config('visitor.resolvers'));
    }

    public function testTheMaxMindResolverReadsTheDatabaseTheConfigurationNames() : void
    {
        config()->set('visitor.maxmind.database', '/nowhere/GeoLite2-City.mmdb');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('/nowhere/GeoLite2-City.mmdb');

        app(MaxMindResolver::class);
    }

    public function testItPublishesTheConfigurationFile() : void
    {
        $expected = [dirname(__DIR__, 2).'/config/visitor.php' => config_path('visitor.php')];

        $this->assertSame($expected, ServiceProvider::pathsToPublish(VisitorServiceProvider::class, 'config'));
        $this->assertSame(
            $expected,
            ServiceProvider::pathsToPublish(VisitorServiceProvider::class, 'visitor-config')
        );
    }

    public function testItPublishesTheMigrations() : void
    {
        $expected = [dirname(__DIR__, 2).'/database/migrations' => database_path('migrations')];

        $this->assertSame($expected, ServiceProvider::pathsToPublish(VisitorServiceProvider::class, 'migrations'));
        $this->assertSame(
            $expected,
            ServiceProvider::pathsToPublish(VisitorServiceProvider::class, 'visitor-migrations')
        );
    }

    public function testTheMigrationsRunInTheOrderTheyHaveToRunIn() : void
    {
        $migrations = glob(dirname(__DIR__, 2).'/database/migrations/*.php') ?: [];

        // Both used to be published under the very same timestamp, which put
        // the column that is added to the table before the table itself.
        $this->assertCount(4, $migrations);
        $this->assertStringContainsString('create_visits_table', $migrations[0]);
        $this->assertStringContainsString('add_geo_raw_to_visits_table', $migrations[1]);
        $this->assertStringContainsString('add_route_name_to_visits_table', $migrations[2]);
        $this->assertStringContainsString('add_extra_attributes_to_visits_table', $migrations[3]);
    }

    public function testTheExtraAttributesColumnIsAddedOnceAndCanBeRolledBack() : void
    {
        $migrations = dirname(__DIR__, 2).'/database/migrations';
        $migration = require $migrations.'/2026_10_25_000001_add_extra_attributes_to_visits_table.php';
        $table = (string) config('visitor.table_name');

        $this->assertTrue(Schema::hasColumn($table, 'extra_attributes'));

        // A table that already has the column is left alone
        $migration->up();
        $this->assertTrue(Schema::hasColumn($table, 'extra_attributes'));

        $migration->down();
        $this->assertFalse(Schema::hasColumn($table, 'extra_attributes'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn($table, 'extra_attributes'));
    }

    public function testTheRouteNameColumnIsAddedOnceAndCanBeRolledBack() : void
    {
        $migrations = dirname(__DIR__, 2).'/database/migrations';
        $migration = require $migrations.'/2026_10_25_000000_add_route_name_to_visits_table.php';
        $table = (string) config('visitor.table_name');

        $this->assertTrue(Schema::hasColumn($table, 'route_name'));

        // A table that already has the column is left alone
        $migration->up();
        $this->assertTrue(Schema::hasColumn($table, 'route_name'));

        $migration->down();
        $this->assertFalse(Schema::hasColumn($table, 'route_name'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn($table, 'route_name'));
    }
}
