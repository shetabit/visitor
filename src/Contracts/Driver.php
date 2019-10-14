<?php

namespace Shetabit\Visitor\Contracts;

interface Driver
{
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
    public  function ip() : string;
}
