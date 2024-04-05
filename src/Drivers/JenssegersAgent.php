<?php

namespace Shetabit\Visitor\Drivers;

use Illuminate\Http\Request;
use Shetabit\Visitor\Agent;
use Shetabit\Visitor\Contracts\UserAgentParser;

class JenssegersAgent implements UserAgentParser
{
    /**
     * Request container.
     */
    protected Request $request;

    /**
     * Agent parser.
     */
    protected Agent $parser;

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
     * Retrieve device's name.
     */
    public function device() : string
    {
        return $this->parser->device();
    }

    /**
     * Retrieve platform's name.
     */
    public function platform() : string
    {
        return $this->parser->platform();
    }

    /**
     * Retrieve browser's name.
     */
    public function browser() : string
    {
        return $this->parser->browser();
    }

    /**
     * Retrieve languages.
     */
    public function languages() : array
    {
        return $this->parser->languages();
    }

    /**
     * Initialize userAgent parser.
     */
    protected function initParser(): Agent
    {
        $parser = new Agent();

        $parser->setUserAgent($this->request->userAgent());
        $parser->setHttpHeaders((array)$this->request->headers);

        return $parser;
    }
}
