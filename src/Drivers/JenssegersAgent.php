<?php

namespace Shetabit\Visitor\Drivers;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Shetabit\Visitor\Contracts\UserAgentParser;

class JenssegersAgent implements UserAgentParser
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
     * Initialize userAgent parser.
     *
     * @return Agent
     */
    protected function initParser()
    {
        $parser = new Agent();

        $parser->setUserAgent($this->request->userAgent());
        $parser->setHttpHeaders($this->request->headers);

        return $parser;
    }
}
