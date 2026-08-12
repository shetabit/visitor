<?php

namespace Shetabit\Visitor\Tests\Unit;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Shetabit\Visitor\Drivers\JenssegersAgent;
use Shetabit\Visitor\Drivers\UAParser;

final class DriversTest extends TestCase
{
    private const string FIREFOX =
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:135.0) Gecko/20100101 Firefox/135.0';

    public function testTheJenssegersDriverReadsTheUserAgentOfTheRequest() : void
    {
        $driver = new JenssegersAgent($this->request(self::FIREFOX));

        $this->assertSame('Macintosh', $driver->device());
        $this->assertSame('OS X', $driver->platform());
        $this->assertSame('Firefox', $driver->browser());
    }

    public function testTheJenssegersDriverReadsTheLanguagesOfTheRequest() : void
    {
        // The driver used to hand the header bag of the request to the parser
        // cast to an array, which the parser could not read anything out of.
        $driver = new JenssegersAgent($this->request(self::FIREFOX, 'en-US,en;q=0.5'));

        $this->assertSame(['en-us', 'en'], $driver->languages());
    }

    public function testTheJenssegersDriverAnswersWithEmptyStringsForARequestWithoutAUserAgent() : void
    {
        $driver = new JenssegersAgent($this->requestWithoutHeaders());

        $this->assertSame('', $driver->device());
        $this->assertSame('', $driver->platform());
        $this->assertSame('', $driver->browser());
        $this->assertSame([], $driver->languages());
    }

    public function testTheUaParserDriverReadsTheUserAgentOfTheRequest() : void
    {
        $driver = new UAParser($this->request(self::FIREFOX));

        $this->assertSame('Mac', $driver->device());
        $this->assertSame('Mac OS X', $driver->platform());
        $this->assertSame('Firefox', $driver->browser());
    }

    public function testTheUaParserDriverReadsTheLanguagesOfTheRequest() : void
    {
        // The languages used to be read off `$_SERVER` instead of the request,
        // and only the first two characters of the header were kept.
        $driver = new UAParser($this->request(self::FIREFOX, 'fa-IR,fa;q=0.9,en;q=0.8'));

        $this->assertSame(['fa-ir', 'fa', 'en'], $driver->languages());
    }

    public function testTheUaParserDriverHandlesARequestWithoutAUserAgent() : void
    {
        // The parser only accepts a string, so a request without a user agent
        // used to end in a TypeError.
        $driver = new UAParser($this->requestWithoutHeaders());

        $this->assertSame('Other', $driver->device());
        $this->assertSame('Other', $driver->platform());
        $this->assertSame('Other', $driver->browser());
        $this->assertSame([], $driver->languages());
    }

    /**
     * A request without the user agent and the languages `Request::create()`
     * fills in by default.
     */
    private function requestWithoutHeaders() : Request
    {
        $request = Request::create('http://localhost');

        $request->server->remove('HTTP_USER_AGENT');
        $request->server->remove('HTTP_ACCEPT_LANGUAGE');
        $request->headers->remove('user-agent');
        $request->headers->remove('accept-language');

        return $request;
    }

    private function request(string $userAgent, string|null $acceptLanguage = null) : Request
    {
        $server = ['HTTP_USER_AGENT' => $userAgent];

        if ($acceptLanguage !== null) {
            $server['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguage;
        }

        return Request::create('http://localhost/products', 'GET', [], [], [], $server);
    }
}
