<?php

namespace Shetabit\Visitor\Traits;

use Illuminate\Support\Facades\Auth;
use Shetabit\Visitor\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Visitable
{
    /**
     * Get all of the model visit logs.
     *
     * @return mixed
     */
    public function visitLogs()
    {
        return $this->morphMany(Visit::class, 'visitable');
    }

    /**
     * Create a visit log.
     *
     * @param Model|null $visitor
     *
     * @return mixed
     */
    public function createVisitLog(?Model $visitor)
    {
        return app('shetabit-visitor')->setVisitor($visitor)->visit($this);
    }
}
