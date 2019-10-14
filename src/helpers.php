<?php
if (!function_exists('visitor')) {
    /**
     * Access visitor through helper.
     *
     * @return \Shetabit\Visitor\Visitor
     */
    function visitor()
    {
        return app('shetabit-visitor');
    }
}
