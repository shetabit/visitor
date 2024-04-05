<?php

namespace Shetabit\Visitor\Drivers;

use UAParser\Parser;
use Illuminate\Http\Request;
use Shetabit\Visitor\Contracts\UserAgentParser;

class UAParser implements UserAgentParser
{
    /**
     * Request container.
     */
    protected Request $request;

    /**
     * Agent parser.
     */
    protected \UAParser\Result\Client $parser;

    /**
     * UAParser constructor.
     *
     * @param Request $request
     *
     * @throws \UAParser\Exception\FileNotFoundException
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->parser = $this->initParser();
    }

    /**
     * Retrieve device's name.
     */
    public function device() : string
    {
        return $this->parser->device->family;
    }

    /**
     * Retrieve platform's name.
     */
    public function platform() : string
    {
        return $this->parser->os->family;
    }

    /**
     * Retrieve browser's name.
     */
    public function browser() : string
    {
        return $this->parser->ua->family;
    }

    /**
     * Retrieve languages.
     */
    public function languages() : array
    {
        $languages = [];

        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $languages[] = $lang;
        }

        return $languages;
    }

    /**
     * Initialize userAgent parser.
     *
     * @throws \UAParser\Exception\FileNotFoundException
     */
    protected function initParser(): \UAParser\Result\Client
    {
        return Parser::create()->parse($this->request->userAgent());
    }
}
