<?php

namespace Shetabit\Visitor\Contracts;

interface Driver
{
    /**
     * Retrieve request's data
     *
     * @return array
     */
    public function request() : array;

    /**
     * Retrieve agent.
     *
     * @return string
     */
    public function userAgent() : string;

    /**
     * Retrieve http headers.
     *
     * @return array
     */
    public function httpHeaders() : array;

    /**
     * Retrieve device's name.
     *
     * @return string
     */
    public function device() : string;

    /**
     * Retrieve platform's name.
     *
     * @return string
     */
    public function platform() : string;

    /**
     * Retrieve browser's name.
     *
     * @return string
     */
    public function browser() : string;

    /**
     * Retrieve languages.
     *
     * @return array
     */
    public function languages() : array;

    /**
     * Retrieve user's ip.
     *
     * @return string|null
     */
    public  function ip() : ?string;

    /**
     * Retrieve request's url.
     *
     * @return string
     */
    public function url() : string;


    /**
     * Retrieve request's referer.
     *
     * @return string|null
     */
    public function referer() : ?string;

    /**
     * Retrieve request's method.
     *
     * @return string
     */
    public function method() : string;
}
