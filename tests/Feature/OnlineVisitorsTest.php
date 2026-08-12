<?php

namespace Shetabit\Visitor\Tests\Feature;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Shetabit\Visitor\Middlewares\LogVisits;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Tests\Fixtures\User;
use Shetabit\Visitor\Tests\TestCase;

/**
 * Recognizing online users, over requests that go through the whole framework.
 */
final class OnlineVisitorsTest extends TestCase
{
    public function testAUserThatVisitedAPageIsOnline() : void
    {
        $user = $this->createUser('online');

        $this->actingAs($user)->get('/products')->assertOk();

        $this->assertTrue(visitor()->isOnline($user));
        $this->assertTrue($user->isOnline());
        $this->assertSame(['online'], User::online()->pluck('name')->all());
        $this->assertSame(['online'], visitor()->onlineVisitors(User::class)->pluck('name')->all());
    }

    public function testAUserThatVisitedNothingIsNotOnline() : void
    {
        $user = $this->createUser();

        $this->get('/products')->assertOk();

        $this->assertFalse(visitor()->isOnline($user));
        $this->assertFalse($user->isOnline());
        $this->assertSame([], User::online()->pluck('name')->all());
    }

    public function testAUserDropsOffTheOnlineListAfterTheWindowPassed() : void
    {
        $user = $this->createUser('gone');

        $this->travelTo(now()->subMinutes(5));
        $this->actingAs($user)->get('/products')->assertOk();
        $this->travelBack();

        $this->assertFalse($user->isOnline());
        $this->assertSame([], User::online()->pluck('name')->all());

        // Still there in a window wide enough to hold the visit.
        $this->assertTrue($user->isOnline(600));
        $this->assertSame(['gone'], User::online(600)->pluck('name')->all());
    }

    public function testEveryVisitorOfTheLastSecondsIsListedOnce() : void
    {
        $first = $this->createUser('first');
        $second = $this->createUser('second');

        $this->visitAs($first);
        $this->visitAs($first);
        $this->visitAs($second);

        $this->assertSame(3, Visit::query()->count());
        $this->assertSame(['first', 'second'], User::online()->pluck('name')->all());
    }

    /**
     * Let the given user visit a page, as a request of its own.
     *
     * The visitor is bound for the length of one request, which a worker that
     * handles more than one of them throws away in between — here by hand,
     * since every request of a test is handled by the same application.
     */
    private function visitAs(User $user) : void
    {
        $this->actingAs($user)->get('/products')->assertOk();

        $this->app->forgetScopedInstances();
    }

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router) : void
    {
        $router->middleware([SubstituteBindings::class, LogVisits::class])->group(function (Router $router): void {
            $router->get('/products', fn (): string => 'products');
        });
    }
}
