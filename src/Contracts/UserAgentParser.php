<?php

namespace Shetabit\Visitor\Contracts;

interface UserAgentParser
{
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
}
