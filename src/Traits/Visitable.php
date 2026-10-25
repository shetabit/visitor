<?php

namespace Shetabit\Visitor\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Support\VisitModel;

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
        return $this->morphMany(VisitModel::name(), 'visitable');
    }

    /**
     * Create a visit log.
     *
     * The visitor is optional: without one, the log only says that this model
     * was visited.
     *
     * @param array<string, mixed> $extraAttributes what the application keeps beside the visit, as key and value pairs
     */
    public function createVisitLog(Model|null $visitor = null, array $extraAttributes = []): Visit|null
    {
        return visitor()->setVisitor($visitor)->visit($this, $extraAttributes);
    }
}
