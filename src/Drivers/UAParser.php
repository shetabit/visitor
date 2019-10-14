<?php

namespace Shetabit\Visitor\Drivers;

use UAParser\Parser;
use Illuminate\Http\Request;
use Shetabit\Visitor\Contracts\Driver;

class UAParser implements Driver
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
     * Retrieve request's data
     *
     * @return array
     */
    public function request() : array
    {
        return $this->request->all();
    }

    /**
     * Retrieve agent.
     *
     * @return string
     */
    public function userAgent() : string
    {
        return $this->request->userAgent();
    }

    /**
     * Retrieve http headers.
     *
     * @return array
     */
    public function httpHeaders() : array
    {
        return $this->request->headers->all();
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
     * Retrieve user's ip.
     *
     * @return string|null
     */
    public  function ip() : ?string
    {
        return $this->request->ip();
    }

    /**
     * Retrieve request's url
     *
     * @return string
     */
    public function url() : string
    {
        return $this->request->fullUrl();
    }

    /**
     * Retrieve request's referer
     *
     * @return string|null
     */
    public function referer() : ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Retrieve request's method.
     *
     * @return string
     */
    public function method() : string
    {
        return $this->request->getMethod();
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
