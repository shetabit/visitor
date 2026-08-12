<?php

namespace Shetabit\Visitor\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Shetabit\Visitor\Models\Visit;

/**
 * Turns a model into something that can be visited, a post for instance.
 *
 * @phpstan-require-extends Model
 */
trait Visitable
{
    /**
     * Get all of the model visit logs.
     *
     * @return MorphMany<Visit, $this>
     */
    public function visitLogs(): MorphMany
    {
        return $this->morphMany(Visit::class, 'visitable');
    }

    /**
     * Create a visit log.
     *
     * The visitor is optional: without one, the log only says that this model
     * was visited.
     */
    public function createVisitLog(Model|null $visitor = null): Visit|null
    {
        return visitor()->setVisitor($visitor)->visit($this);
    }
}
