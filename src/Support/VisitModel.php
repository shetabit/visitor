<?php

namespace Shetabit\Visitor\Support;

use InvalidArgumentException;
use Shetabit\Visitor\Models\Visit;

/**
 * The model the visits are logged with, as the `model` configuration names it.
 *
 * An application can extend the package's model, to keep columns of its own beside a visit, and log the visits with
 * it: the visitor and the traits all ask this class for it.
 */
final class VisitModel
{
    /**
     * The class of the model.
     *
     * @return class-string<Visit>
     *
     * @throws InvalidArgumentException when the configuration names a class that does not extend the package's model
     */
    public static function name(): string
    {
        $model = config('visitor.model') ?? Visit::class;

        if (!is_string($model) || !is_a($model, Visit::class, true)) {
            throw new InvalidArgumentException(
                'The visit model of the configuration has to extend '.Visit::class.'.'
            );
        }

        return $model;
    }
}
