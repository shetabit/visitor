<?php

use Shetabit\Visitor\Facade\Visitor as VisitorFacade;
use Shetabit\Visitor\Visitor;

if (!function_exists('visitor')) {
    /**
     * Access visitor through helper.
     */
    function visitor(): Visitor
    {
        return app(VisitorFacade::SERVICE_NAME);
    }
}
