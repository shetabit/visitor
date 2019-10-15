<?php

namespace Shetabit\Visitor\Drivers;

use UAParser\Parser;
use Illuminate\Http\Request;
use Shetabit\Visitor\Contracts\UserAgentParser;

class UAParser implements UserAgentParser
{
    /**
     * Request container.
     *
     * @var Request
     */
    protected $request;

    /**
     * Agent parser.
     *
     * @var \UAParser\Result\Client
     */
    protected $parser;

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
     *
     * @return string
     */
    public function device() : string
    {
        return $this->parser->device->family;
    }

    /**
     * Retrieve platform's name.
     *
     * @return string
     */
    public function platform() : string
    {
        return $this->parser->os->family;
    }

    /**
     * Retrieve browser's name.
     *
     * @return string
     */
    public function browser() : string
    {
        return $this->parser->ua->family;
    }

    /**
     * Retrieve languages.
     *
     * @return array
     */
    public function languages() : array
    {
        $languages = [];

        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            array_push($languages, $lang);
        }

        return $languages;
    }

    /**
     * Initialize userAgent parser.
     *
     * @return \UAParser\Result\Client
     *
     * @throws \UAParser\Exception\FileNotFoundException
     */
    protected function initParser()
    {
        $parser = Parser::create();

        $result = $parser->parse($this->userAgent());

        return $result;
    }
}
