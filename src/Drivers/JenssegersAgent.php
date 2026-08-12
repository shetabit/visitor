<?php

namespace Shetabit\Visitor\Drivers;

use Illuminate\Http\Request;
use Shetabit\Visitor\Agent;
use Shetabit\Visitor\Contracts\UserAgentParser;

class JenssegersAgent implements UserAgentParser
{
    /**
     * Agent parser.
     */
    protected Agent $parser;

    /**
     * Parser constructor.
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
        return $this->stringify($this->parser->device());
    }

    /**
     * Retrieve platform's name.
     */
    public function platform() : string
    {
        return $this->stringify($this->parser->platform());
    }

    /**
     * Retrieve browser's name.
     */
    public function browser() : string
    {
        return $this->stringify($this->parser->browser());
    }

    /**
     * Retrieve languages.
     *
     * @return array<int, string>
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

        /**
         * The parser expects the headers the way PHP hands them over
         * (`HTTP_USER_AGENT`, `HTTP_ACCEPT_LANGUAGE`, …), which is what the
         * server bag of the request holds. Handing them over resets the user
         * agent of the parser, so it is set afterwards.
         */
        $parser->setHttpHeaders($this->request->server->all());
        $parser->setUserAgent($this->request->userAgent() ?? '');

        return $parser;
    }

    /**
     * The parser answers with `false` for what it could not detect.
     */
    private function stringify(bool|string $value) : string
    {
        return is_string($value) ? $value : '';
    }
}
