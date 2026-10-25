<?php

namespace Shetabit\Visitor\Tests\Fixtures;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Shetabit\Visitor\Models\Visit;

/**
 * A visit model of the application, the way one would extend the package's model.
 *
 * @property-read string|null $mobile
 */
class CustomVisit extends Visit
{
    /**
     * The mobile number the application kept among the extra attributes of the visit.
     *
     * @return Attribute<string|null, never>
     */
    protected function mobile(): Attribute
    {
        return Attribute::get(fn (): string|null => $this->extra_attributes['mobile'] ?? null);
    }
}
