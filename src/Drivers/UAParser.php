<?php

namespace Shetabit\Visitor\Drivers;

use Illuminate\Http\Request;
use Shetabit\Visitor\Contracts\UserAgentParser;
use Shetabit\Visitor\Support\AcceptLanguage;
use UAParser\Exception\FileNotFoundException;
use UAParser\Parser;
use UAParser\Result\Client;

class UAParser implements UserAgentParser
{
    /**
     * The parsed user agent.
     */
    protected Client $parser;

    /**
     * UAParser constructor.
     *
     * @throws FileNotFoundException
     */
    public function __construct(protected Request $request)
    {
        $this->parser = $this->initParser();
    }

    /**
     * Retrieve device's name.
     */
    public function device() : string
    {
        return (string) $this->parser->device->family;
    }

    /**
     * Retrieve platform's name.
     */
    public function platform() : string
    {
        return (string) $this->parser->os->family;
    }

    /**
     * Retrieve browser's name.
     */
    public function browser() : string
    {
        return (string) $this->parser->ua->family;
    }

    /**
     * Retrieve languages.
     *
     * @return array<int, string>
     */
    public function languages() : array
    {
        return AcceptLanguage::parse($this->request->headers->get('accept-language'));
    }

    /**
     * Initialize userAgent parser.
     *
     * @throws FileNotFoundException
     */
    protected function initParser(): Client
    {
        return Parser::create()->parse($this->request->userAgent() ?? '');
    }
}
