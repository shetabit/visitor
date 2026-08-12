<?php

namespace Shetabit\Visitor\Contracts;

interface UserAgentParser
{
    /**
     * Retrieve device's name.
     */
    public function device() : string;

    /**
     * Retrieve platform's name.
     */
    public function platform() : string;

    /**
     * Retrieve browser's name.
     */
    public function browser() : string;

    /**
     * Retrieve languages.
     *
     * @return array<int, string>
     */
    public function languages() : array;
}
