<?php

namespace Shetabit\Visitor\Tests\Feature;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Shetabit\Visitor\Middlewares\LogVisits;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Tests\Fixtures\Post;
use Shetabit\Visitor\Tests\TestCase;

/**
 * The middleware, through requests that go through the whole framework.
 */
final class LogVisitsMiddlewareTest extends TestCase
{
    public function testItLogsTheVisitOfAPage() : void
    {
        $this->withHeaders([
            'User-Agent' => self::FIREFOX_USER_AGENT,
            'Accept-Language' => 'fa-IR,fa;q=0.9',
            'Referer' => 'https://example.com/from',
        ])->get('/products?page=2')->assertOk();

        $visit = Visit::query()->sole();

        $this->assertSame('GET', $visit->method);
        $this->assertSame('http://localhost/products?page=2', $visit->url);
        $this->assertSame(['page' => '2'], $visit->request);
        $this->assertSame('https://example.com/from', $visit->referer);
        $this->assertSame(self::FIREFOX_USER_AGENT, $visit->useragent);
        $this->assertSame(['fa-ir', 'fa'], $visit->languages);
        $this->assertSame('Firefox', $visit->browser);
        $this->assertSame('OS X', $visit->platform);
        $this->assertSame('Macintosh', $visit->device);
        $this->assertSame('127.0.0.1', $visit->ip);
        $this->assertSame([self::FIREFOX_USER_AGENT], $visit->headers['user-agent'] ?? null);
        $this->assertNull($visit->visitor_id);
        $this->assertNull($visit->visitable_id);
    }

    public function testItLogsTheVisitOfAPhone() : void
    {
        $this->withHeaders(['User-Agent' => self::IPHONE_USER_AGENT])->get('/products')->assertOk();

        $visit = Visit::query()->sole();

        $this->assertSame('iPhone', $visit->device);
        $this->assertSame('Safari', $visit->browser);
    }

    public function testItLogsTheVisitOfAModelTheRouterBound() : void
    {
        $post = $this->createPost('A bound post');

        $this->get("/posts/{$post->getKey()}")->assertOk();

        $visit = Visit::query()->sole();

        $this->assertTrue($post->is($visit->visitable));
        $this->assertSame(1, $post->visitLogs()->count());
    }

    public function testItLogsOneVisitPerRequest() : void
    {
        $post = $this->createPost();

        $this->get("/posts/{$post->getKey()}")->assertOk();
        $this->get('/products')->assertOk();

        $this->assertSame(2, Visit::query()->count());
    }

    public function testItLogsTheUserThatIsSignedIn() : void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get('/products')->assertOk();

        $visit = Visit::query()->sole();

        $this->assertTrue($user->is($visit->visitor));
    }

    public function testItLogsNoVisitOfAnExceptedPath() : void
    {
        $this->get('/login')->assertOk();

        $this->assertSame(0, Visit::query()->count());
    }

    public function testItLogsAVisitOfARouteThatIsNotThere() : void
    {
        // A request that matches no route has no route to read parameters off,
        // which used to end in a call to `parameters()` on null.
        $this->get('/a-page-that-does-not-exist')->assertNotFound();

        $this->assertSame(0, Visit::query()->count());
    }

    public function testTheVisitorOfTheRequestIsTheOneOfTheContainer() : void
    {
        $this->withHeaders(['User-Agent' => self::FIREFOX_USER_AGENT])
            ->get('/visitor-of-the-request')
            ->assertOk()
            ->assertExactJson([
                'macro' => true,
                'url' => 'http://localhost/visitor-of-the-request',
                'browser' => 'Firefox',
            ]);
    }

    /**
     * The routes the requests of this test go to.
     *
     * `SubstituteBindings` is what turns a route parameter into a model, and it
     * has to run before the middleware of this package, exactly like it does in
     * the `web` group of an application.
     *
     * @param  Router  $router
     */
    protected function defineRoutes($router) : void
    {
        $router->middleware([SubstituteBindings::class, LogVisits::class])->group(function (Router $router): void {
            $router->get('/products', fn (): string => 'products');
            $router->get('/login', fn (): string => 'login');
            $router->get('/posts/{post}', fn (Post $post): string => $post->title);
        });

        $router->get('/visitor-of-the-request', function (): array {
            // @phpstan-ignore method.notFound (the macro is added by the provider)
            $visitor = request()->visitor();

            return [
                'macro' => $visitor === visitor(),
                'url' => $visitor->url(),
                'browser' => $visitor->browser(),
            ];
        });
    }
}
