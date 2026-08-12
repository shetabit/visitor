<?php

namespace Shetabit\Visitor\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Shetabit\Visitor\Facade\Visitor;
use Shetabit\Visitor\Provider\VisitorServiceProvider;
use Shetabit\Visitor\Tests\Fixtures\Post;
use Shetabit\Visitor\Tests\Fixtures\User;

abstract class TestCase extends BaseTestCase
{
    /**
     * The user agent of a desktop Firefox, used where the parser has to find something.
     */
    protected const string FIREFOX_USER_AGENT =
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:135.0) Gecko/20100101 Firefox/135.0';

    /**
     * The user agent of an iPhone.
     */
    protected const string IPHONE_USER_AGENT =
        'Mozilla/5.0 (iPhone; CPU iPhone OS 18_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko)'
        .' Version/18.3 Mobile/15E148 Safari/604.1';

    /**
     * The user agent of a crawler.
     */
    protected const string CRAWLER_USER_AGENT =
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    /**
     * Get the package's service providers.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app) : array
    {
        return [VisitorServiceProvider::class];
    }

    /**
     * Get the package's facade aliases.
     *
     * @param  Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app) : array
    {
        return [
            'Visitor' => Visitor::class,
        ];
    }

    /**
     * Define the environment the tests run in.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app) : void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Run the migrations of the package and the ones of the test models.
     */
    protected function defineDatabaseMigrations() : void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
    }

    /**
     * Replace the request the visitor works with.
     *
     * The visitor is a singleton that is handed the request while it is being
     * built, so it has to be forgotten together with the request.
     *
     * @param array<string, string> $server headers, PHP flavored (`HTTP_USER_AGENT`)
     * @param array<string, mixed>  $query
     */
    protected function swapRequest(
        string $uri = 'http://localhost/products',
        string $method = 'GET',
        array $server = [],
        array $query = [],
    ) : Request {
        $request = Request::create($uri, $method, $query, [], [], $server);

        $this->app->instance('request', $request);
        $this->app->instance(Request::class, $request);
        $this->app->forgetInstance(Visitor::SERVICE_NAME);

        return $request;
    }

    /**
     * A saved user.
     */
    protected function createUser(string $name = 'Mahdi') : User
    {
        return User::query()->create(['name' => $name]);
    }

    /**
     * A saved post.
     */
    protected function createPost(string $title = 'A post') : Post
    {
        return Post::query()->create(['title' => $title]);
    }
}
