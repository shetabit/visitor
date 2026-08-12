<?php

namespace Shetabit\Visitor\Tests\Fixtures;

use Shetabit\Visitor\Contracts\UserAgentParser;

/**
 * A user agent parser that answers with fixed values, and counts how often it
 * was built.
 */
class FakeParser implements UserAgentParser
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    public function device() : string
    {
        return 'a-device';
    }

    public function platform() : string
    {
        return 'a-platform';
    }

    public function browser() : string
    {
        return 'a-browser';
    }

    /**
     * @return array<int, string>
     */
    public function languages() : array
    {
        return ['a-language'];
    }
}
