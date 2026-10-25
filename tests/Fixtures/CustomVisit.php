<?php

namespace Shetabit\Visitor\Tests\Fixtures;

use Shetabit\Visitor\Models\Visit;

/**
 * A visit model with columns of its own, the way an application would extend the package's one.
 *
 * @property string|null $name
 * @property string|null $mobile
 */
class CustomVisit extends Visit
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->mergeFillable(['name', 'mobile']);

        parent::__construct($attributes);
    }
}
