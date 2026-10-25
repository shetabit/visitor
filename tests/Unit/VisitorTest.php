<?php

namespace Shetabit\Visitor\Tests\Unit;

use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Route;
use Shetabit\Visitor\Exceptions\DriverNotFoundException;
use Shetabit\Visitor\Exceptions\ResolverNotFoundException;
use Shetabit\Visitor\Facade\Visitor as VisitorFacade;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Tests\Fixtures\FakeParser;
use Shetabit\Visitor\Tests\Fixtures\FakeResolver;
use Shetabit\Visitor\Tests\Fixtures\NotAParser;
use Shetabit\Visitor\Tests\Fixtures\NotAResolver;
use Shetabit\Visitor\Tests\Fixtures\Page;
use Shetabit\Visitor\Tests\TestCase;
use Shetabit\Visitor\Visitor;

final class VisitorTest extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        // Every test works with a request of `/products` unless it swaps in
        // one of its own.
        $this->swapRequest();

        FakeParser::$instances = 0;
        FakeResolver::$instances = 0;
    }

    public function testItReadsTheDataOfTheRequest() : void
    {
        $this->swapRequest(
            'http://localhost/products?page=2',
            'POST',
            ['HTTP_USER_AGENT' => self::FIREFOX_USER_AGENT, 'HTTP_REFERER' => 'https://example.com/from'],
            ['page' => '2'],
        );

        $visitor = $this->visitor();

        $this->assertSame('POST', $visitor->method());
        $this->assertSame('http://localhost/products?page=2', $visitor->url());
        $this->assertSame(['page' => '2'], $visitor->request());
        $this->assertSame('https://example.com/from', $visitor->referer());
        $this->assertSame(self::FIREFOX_USER_AGENT, $visitor->userAgent());
        $this->assertSame('127.0.0.1', $visitor->ip());
        $this->assertArrayHasKey('user-agent', $visitor->httpHeaders());
    }

    public function testItReadsTheRouteTheRequestMatchedAndItsName() : void
    {
        $route = new Route('GET', '/products', fn (): string => 'products')->name('products.index');
        $this->swapRequest()->setRouteResolver(fn (): Route => $route);

        $this->assertSame($route, $this->visitor()->route());
        $this->assertSame('products.index', $this->visitor()->routeName());
    }

    public function testItReadsNoRouteOfARequestThatMatchedNoRoute() : void
    {
        $this->assertNull($this->visitor()->route());
        $this->assertNull($this->visitor()->routeName());
    }

    public function testItReadsNoRouteOfARouteResolverThatHandsNoRouteOver() : void
    {
        $this->swapRequest()->setRouteResolver(fn (): string => 'not a route');

        $this->assertNull($this->visitor()->route());
        $this->assertNull($this->visitor()->routeName());
    }

    public function testItReadsNoRouteNameOfARouteWithoutOne() : void
    {
        $route = new Route('GET', '/products', fn (): string => 'products');
        $this->swapRequest()->setRouteResolver(fn (): Route => $route);

        $this->assertSame($route, $this->visitor()->route());
        $this->assertNull($this->visitor()->routeName());
    }

    public function testItReadsTheRefererOfTheRequestAndNotOfTheSuperGlobals() : void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/a-referer-of-the-super-globals';

        try {
            $this->swapRequest();

            $this->assertNull($this->visitor()->referer());
        } finally {
            unset($_SERVER['HTTP_REFERER']);
        }
    }

    public function testItHandsTheUserAgentQuestionsToTheDriver() : void
    {
        $visitor = $this->visitor();

        $this->assertSame('a-device', $visitor->device());
        $this->assertSame('a-platform', $visitor->platform());
        $this->assertSame('a-browser', $visitor->browser());
        $this->assertSame(['a-language'], $visitor->languages());
    }

    public function testItBuildsTheDriverOnceAndThenKeepsIt() : void
    {
        $visitor = $this->visitor();

        $visitor->device();
        $visitor->platform();
        $visitor->browser();
        $visitor->languages();

        // Every one of them used to build a driver of its own, which parses the
        // user agent again — for the UAParser driver that reads a regex file.
        $this->assertSame(1, FakeParser::$instances);
    }

    public function testTheDriverAndTheResolverCanBeChangedOnTheFly() : void
    {
        $this->swapRequest(server: ['HTTP_USER_AGENT' => self::FIREFOX_USER_AGENT]);

        $visitor = $this->visitor();

        $this->assertSame('a-device', $visitor->device());

        // Switching the driver has to throw the one that answered before away.
        $visitor->via('jenssegers', 'null');

        $this->assertSame('Macintosh', $visitor->device());
        $this->assertNull($visitor->geolocation());
    }

    public function testItRefusesADriverThatIsNotConfigured() : void
    {
        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver not found in config file. Try updating the package.');

        $this->visitor()->via('a-driver-of-nothing', 'fake');
    }

    public function testItRefusesADriverWithoutAName() : void
    {
        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver not selected or default driver does not exist.');

        $this->visitor()->via('', 'fake');
    }

    public function testItRefusesADriverThatDoesNotImplementTheContract() : void
    {
        config()->set('visitor.drivers.not-a-parser', NotAParser::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Driver must be an instance of Contracts\UserAgentParser.');

        $this->visitor()->via('not-a-parser', 'fake');
    }

    public function testItRefusesAResolverThatIsNotConfigured() : void
    {
        // The exception used to be thrown without being imported, which ended
        // in a "class not found" instead.
        $this->expectException(ResolverNotFoundException::class);
        $this->expectExceptionMessage('Resolver not found in config file. Try updating the package.');

        $this->visitor()->via('fake', 'a-resolver-of-nothing');
    }

    public function testItRefusesAResolverWithoutAName() : void
    {
        $this->expectException(ResolverNotFoundException::class);
        $this->expectExceptionMessage('Resolver not selected or default resolver does not exist.');

        $this->visitor()->via('fake', '');
    }

    public function testItRefusesAResolverThatDoesNotImplementTheContract() : void
    {
        config()->set('visitor.resolvers.not-a-resolver', NotAResolver::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Resolver must be an instance of Contracts\GeoIpResolver.');

        $this->visitor()->via('fake', 'not-a-resolver');
    }

    public function testItResolvesNoLocationWhileTheEnrichmentIsDisabled() : void
    {
        $visitor = $this->visitor();

        $this->assertNull($visitor->resolve('24.24.24.24'));
        $this->assertNull($visitor->geolocation());
        $this->assertSame(0, FakeResolver::$instances);
    }

    public function testItResolvesTheLocationOfTheVisitorWhileTheEnrichmentIsEnabled() : void
    {
        config()->set('visitor.geoip', true);

        $location = $this->visitor()->geolocation();

        $this->assertSame('127.0.0.1', $location['ip'] ?? null);
        $this->assertSame('IR', $location['country_code'] ?? null);
    }

    public function testItBuildsTheResolverOnceAndThenKeepsIt() : void
    {
        config()->set('visitor.geoip', true);

        $visitor = $this->visitor();

        $visitor->resolve('24.24.24.24');
        $visitor->resolve('24.24.24.25');

        $this->assertSame(1, FakeResolver::$instances);
    }

    public function testItLogsAVisit() : void
    {
        $visit = $this->visitor()->visit();

        $this->assertInstanceOf(Visit::class, $visit);
        $this->assertSame('GET', $visit->method);
        $this->assertSame('http://localhost/products', $visit->url);
        $this->assertNull($visit->route_name);
        $this->assertSame('a-device', $visit->device);
        $this->assertSame('a-platform', $visit->platform);
        $this->assertSame('a-browser', $visit->browser);
        $this->assertSame(['a-language'], $visit->languages);
        $this->assertSame('127.0.0.1', $visit->ip);
        $this->assertNull($visit->geo_raw);
        $this->assertNull($visit->extra_attributes);
        $this->assertSame(1, Visit::query()->count());
    }

    public function testAVisitKeepsTheExtraAttributesOfTheApplication() : void
    {
        $visit = $this->visitor()->visit(null, ['name' => 'Mahdi', 'mobile' => '09120000000']);

        $this->assertInstanceOf(Visit::class, $visit);
        $stored = Visit::query()->whereKey($visit->getKey())->sole();

        $this->assertSame(['name' => 'Mahdi', 'mobile' => '09120000000'], $stored->extra_attributes);
    }

    public function testTheExtraAttributesDoNotTakeThePlaceOfTheColumnsOfTheVisit() : void
    {
        $visit = $this->visitor()->visit(null, ['url' => 'https://example.com/another-page']);

        $this->assertInstanceOf(Visit::class, $visit);
        $this->assertSame('http://localhost/products', $visit->url);
        $this->assertSame(['url' => 'https://example.com/another-page'], $visit->extra_attributes);
    }

    public function testTheVisitsCanBeFilteredByTheirExtraAttributes() : void
    {
        $this->visitor()->visit(null, ['mobile' => '09120000000']);
        $this->visitor()->visit(null, ['mobile' => '09350000000']);
        $this->visitor()->visit();

        $this->assertSame(1, Visit::query()->where('extra_attributes->mobile', '09120000000')->count());
    }

    public function testItAddsTheLocationToAVisitWhileTheEnrichmentIsEnabled() : void
    {
        config()->set('visitor.geoip', true);

        $visit = $this->visitor()->visit();

        $this->assertSame('IR', $visit?->geo_raw['country_code'] ?? null);
    }

    public function testItLogsNoVisitOfAnExceptedPath() : void
    {
        config()->set('visitor.except', ['products', 'login']);

        $this->assertNull($this->visitor()->visit());
        $this->assertSame(0, Visit::query()->count());
    }

    public function testItLogsTheVisitorOfAVisit() : void
    {
        $user = $this->createUser();

        $visit = $this->visitor()->setVisitor($user)->visit();

        $this->assertSame($user->getKey(), $visit?->visitor_id);
        $this->assertSame($user->getMorphClass(), $visit?->visitor_type);
    }

    public function testTheVisitorCanBeReadAndReplaced() : void
    {
        $visitor = $this->visitor();
        $user = $this->createUser();

        $this->assertNull($visitor->getVisitor());
        $this->assertSame($visitor, $visitor->setVisitor($user));
        $this->assertSame($user, $visitor->getVisitor());
        $this->assertNull($visitor->setVisitor(null)->getVisitor());
    }

    public function testItAttachesAVisitToAVisitableModel() : void
    {
        $post = $this->createPost();

        $visit = $this->visitor()->visit($post);

        $this->assertSame(1, $post->visitLogs()->count());
        $this->assertSame($post->getKey(), $visit?->visitable_id);
        $this->assertSame($post->getMorphClass(), $visit?->visitable_type);
    }

    public function testItLogsAVisitOfAModelWithoutTheVisitableTrait() : void
    {
        $page = Page::query()->create(['title' => 'A page']);

        $visit = $this->visitor()->visit($page);

        $this->assertInstanceOf(Visit::class, $visit);
        $this->assertNull($visit->visitable_id);
    }

    public function testItDeterminesWhetherAVisitorIsOnline() : void
    {
        $user = $this->createUser();
        $visitor = $this->visitor();

        $this->assertFalse($visitor->isOnline($user));

        $visitor->setVisitor($user)->visit();

        $this->assertTrue($visitor->isOnline($user));
        $this->assertTrue($visitor->isOnline());
    }

    public function testAVisitorWithoutAVisitOfTheLastSecondsIsNotOnline() : void
    {
        $user = $this->createUser();
        $visitor = $this->visitor()->setVisitor($user);

        $this->travelTo(now()->subMinutes(10));
        $visitor->visit();
        $this->travelBack();

        // The window used to be compared with `whereDate()`, which only looks
        // at the date and answered "online" for every visit of the same day.
        $this->assertFalse($visitor->isOnline($user));
        $this->assertTrue($visitor->isOnline($user, 3600));
    }

    public function testItAnswersThatNobodyIsOnlineWithoutAVisitor() : void
    {
        $this->assertFalse($this->visitor()->isOnline());
    }

    public function testItRetrievesTheOnlineVisitors() : void
    {
        $online = $this->createUser('online');
        $offline = $this->createUser('offline');

        $this->visitor()->setVisitor($online)->visit();

        $this->travelTo(now()->subMinutes(10));
        $this->visitor()->setVisitor($offline)->visit();
        $this->travelBack();

        $visitors = $this->visitor()->onlineVisitors($online::class);

        $this->assertSame(['online'], $visitors->pluck('name')->all());

        // The seconds used to be ignored, so the window was always 180 seconds.
        $this->assertSame(
            ['online', 'offline'],
            $this->visitor()->onlineVisitors($online::class, 3600)->pluck('name')->all()
        );
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app) : void
    {
        parent::defineEnvironment($app);

        $app['config']->set('visitor.default', 'fake');
        $app['config']->set('visitor.drivers.fake', FakeParser::class);
        $app['config']->set('visitor.resolver', 'fake');
        $app['config']->set('visitor.resolvers.fake', FakeResolver::class);
        $app['config']->set('visitor.except', []);
    }

    private function visitor() : Visitor
    {
        return $this->app->make(VisitorFacade::SERVICE_NAME);
    }
}
