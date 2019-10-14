<?php

namespace Shetabit\Visitor\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Shetabit\Visitor\Tests\Mocks\Drivers\BarDriver;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return ['Shetabit\Visitor\Provider\VisitorServiceProvider'];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Visitor' => 'Shetabit\Visitor\Facade\Visitor',
        ];
    }
}
