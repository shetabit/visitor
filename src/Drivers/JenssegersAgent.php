<?php

namespace Shetabit\Visitor\Drivers;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Shetabit\Visitor\Contracts\Driver;

class JenssegersAgent implements Driver
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
     * @var Agent
     */
    protected $parser;

    /**
     * Parser constructor.
     *
     * @param Request $request
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
        return $this->parser->device();
    }

    /**
     * Retrieve platform's name.
     *
     * @return string
     */
    public function platform() : string
    {
        return $this->parser->platform();
    }

    /**
     * Retrieve browser's name.
     *
     * @return string
     */
    public function browser() : string
    {
        return $this->parser->browser();
    }

    /**
     * Retrieve languages.
     *
     * @return array
     */
    public function languages() : array
    {
        return $this->parser->languages();
    }

    /**
     * Retrieve user's ip.
     *
     * @return string|null
     */
    public  function ip() : string
    {
        return $this->request->ip();
    }

    /**
     * Initialize userAgent parser.
     *
     * @return Agent
     */
    protected function initParser()
    {
        $parser = new Agent();

        $parser->setUserAgent($this->userAgent());
        $parser->setHttpHeaders($this->httpHeaders());

        return $parser;
    }
}
