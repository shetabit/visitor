<?php

namespace Shetabit\Visitor\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Support\VisitModel;

/**
 * Turns a model into someone who visits, under the `visitLogs` name.
 *
 * @phpstan-require-extends Model
 */
trait CanVisit
{
    /**
     * The visits of this visitor.
     *
     * @return MorphMany<Visit, $this>
     */
    public function visitLogs(): MorphMany
    {
        return $this->morphMany(VisitModel::name(), 'visitor');
    }

    /**
     * Retrieve online users
     *
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeOnline(Builder $query, int $seconds = 180): Builder
    {
        return $query->whereHas('visitLogs', function (Builder $query) use ($seconds): void {
            $query->where($query->qualifyColumn('created_at'), '>=', now()->subSeconds($seconds));
        });
    }

    /**
     * check if user is online
     */
    public function isOnline(int $seconds = 180): bool
    {
        return $this->visitLogs()
            ->where('created_at', '>=', now()->subSeconds($seconds))
            ->exists();
    }
}
