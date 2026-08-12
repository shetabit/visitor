<?php

namespace Shetabit\Visitor\Tests\Unit;

use BadMethodCallException;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Shetabit\Visitor\Agent;

final class AgentTest extends TestCase
{
    private const string FIREFOX =
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:135.0) Gecko/20100101 Firefox/135.0';

    private const string IPHONE =
        'Mozilla/5.0 (iPhone; CPU iPhone OS 18_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko)'
        .' Version/18.3 Mobile/15E148 Safari/604.1';

    private const string GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    public function testItFindsTheBrowserThePlatformAndTheDevice() : void
    {
        $agent = $this->agent(self::FIREFOX);

        $this->assertSame('Firefox', $agent->browser());
        $this->assertSame('OS X', $agent->platform());
        $this->assertSame('Macintosh', $agent->device());
    }

    public function testItFindsTheDeviceOfAPhone() : void
    {
        $agent = $this->agent(self::IPHONE);

        $this->assertSame('iPhone', $agent->device());
        $this->assertSame('Safari', $agent->browser());

        // The rules of this class are matched before the ones of the parent, so
        // the "like Mac OS X" of an iPhone wins over the iOS rule. That is the
        // behaviour of the library this parser was ported from.
        $this->assertSame('OS X', $agent->platform());
    }

    public function testItAnswersWithFalseForAUserAgentItCanNotPlace() : void
    {
        $agent = $this->agent('a-user-agent-of-nothing-known');

        $this->assertFalse($agent->browser());
        $this->assertFalse($agent->platform());
        $this->assertFalse($agent->device());
    }

    public function testEveryUserAgentGetsADeviceType() : void
    {
        // The "other" of `deviceType()` can not be reached: a user agent that is
        // neither a phone, a tablet nor a robot is a desktop by definition.
        $this->assertContains(
            $this->agent('curl/8.7.1')->deviceType(),
            ['desktop', 'phone', 'tablet', 'robot']
        );
    }

    public function testItRecognizesADesktopAPhoneAndACrawler() : void
    {
        $this->assertSame('desktop', $this->agent(self::FIREFOX)->deviceType());
        $this->assertSame('phone', $this->agent(self::IPHONE)->deviceType());
        $this->assertSame('robot', $this->agent(self::GOOGLEBOT)->deviceType());
    }

    public function testItRecognizesACrawler() : void
    {
        $crawler = $this->agent(self::GOOGLEBOT);

        $this->assertTrue($crawler->isRobot());
        $this->assertSame('Googlebot', $crawler->robot());

        $firefox = $this->agent(self::FIREFOX);

        $this->assertFalse($firefox->isRobot());
        $this->assertFalse($firefox->robot());
    }

    public function testTheCrawlerDetectorIsCreatedOnTheFlyAndThenShared() : void
    {
        // It is kept in a static property that used to have no default, which
        // made every robot check fail with an "accessed before initialization".
        new ReflectionProperty(Agent::class, 'crawlerDetect')->setValue(null, null);

        $detector = $this->agent(self::FIREFOX)->getCrawlerDetect();

        $this->assertInstanceOf(CrawlerDetect::class, $detector);
        $this->assertSame($detector, $this->agent(self::IPHONE)->getCrawlerDetect());
    }

    public function testItRecognizesATablet() : void
    {
        $agent = $this->agent(
            'Mozilla/5.0 (iPad; CPU OS 18_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko)'
            .' Version/18.3 Mobile/15E148 Safari/604.1'
        );

        $this->assertTrue($agent->isTablet());
        $this->assertFalse($agent->isPhone());
        $this->assertSame('tablet', $agent->deviceType());
        $this->assertSame('iPad', $agent->device());
    }

    public function testItTrustsTheCloudFrontHeaderOfADesktopViewer() : void
    {
        $agent = new Agent();
        $agent->setHttpHeaders([
            'HTTP_USER_AGENT' => 'Amazon CloudFront',
            'HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER' => 'true',
        ]);

        $this->assertTrue($agent->isDesktop());
    }

    public function testItReadsTheLanguagesOfTheHeaders() : void
    {
        $agent = new Agent();
        $agent->setHttpHeaders(['HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.5']);

        $this->assertSame(['en-us', 'en'], $agent->languages());
        $this->assertSame(['fa-ir'], $agent->languages('fa-IR'));
    }

    public function testItAnswersWithNoLanguagesWithoutAnAcceptLanguageHeader() : void
    {
        $this->assertSame([], $this->agent(self::FIREFOX)->languages());
    }

    public function testItResolvesAValueOnlyOnce() : void
    {
        $agent = $this->agent(self::FIREFOX);

        $this->assertSame('Firefox', $agent->browser());

        // The cache key used to carry random bytes, which made every lookup a
        // miss and the store grow with every call.
        $store = new ReflectionProperty(Agent::class, 'store')->getValue($agent);

        $this->assertIsArray($store);
        $this->assertCount(1, $store);

        $agent->browser();

        $this->assertCount(1, new ReflectionProperty(Agent::class, 'store')->getValue($agent));
    }

    public function testTheCacheOfAnAgentDoesNotAnswerForAnotherUserAgent() : void
    {
        $agent = $this->agent(self::FIREFOX);

        $this->assertSame('Firefox', $agent->browser());

        $agent->setUserAgent(self::IPHONE);

        $this->assertSame('Safari', $agent->browser());
    }

    public function testItAnswersTheIsQuestionsOfTheParser() : void
    {
        $this->assertTrue($this->agent(self::IPHONE)->isiPhone());
        $this->assertFalse($this->agent(self::FIREFOX)->isiPhone());
    }

    public function testItRefusesAMethodThatIsNotAnIsQuestion() : void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('No such method exists: hasIphone');

        // @phpstan-ignore method.notFound (the parser answers `is*()` through `__call()`)
        $this->agent(self::FIREFOX)->hasIphone();
    }

    public function testTheRulesOfThisClassAreKeptOutOfTheOnesOfTheParent() : void
    {
        $agent = $this->agent(self::FIREFOX);

        $this->assertSame(['Macintosh' => 'Macintosh'], Agent::getDesktopDevices());

        // The parent answers isMobile() by matching against every rule of
        // getRules(), so the desktop rules have to stay out of it.
        $this->assertArrayNotHasKey('Macintosh', $agent->getRules());
        $this->assertArrayHasKey('Macintosh', $agent->getAdditionalRules());
    }

    public function testADesktopIsNotAMobileDevice() : void
    {
        $agent = $this->agent(self::FIREFOX);

        $this->assertFalse($agent->isMobile());
        $this->assertFalse($agent->isTablet());
        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->isPhone());
    }

    public function testItAnswersTheIsQuestionsOfTheRulesOfThisClass() : void
    {
        $mac = $this->agent(self::FIREFOX);

        // @phpstan-ignore method.notFound (the parser answers `is*()` through `__call()`)
        $this->assertTrue($mac->isMacintosh());
        $this->assertTrue($mac->isFirefox());
        // @phpstan-ignore method.notFound
        $this->assertFalse($mac->isWindows());
    }

    private function agent(string $userAgent) : Agent
    {
        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return $agent;
    }
}
