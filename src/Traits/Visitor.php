<?php

namespace Shetabit\Visitor\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Shetabit\Visitor\Models\Visit;

/**
 * Turns a model into someone who visits, a user for instance.
 *
 * @phpstan-require-extends Model
 */
trait Visitor
{
    /**
     * The visits of this visitor.
     *
     * @return MorphMany<Visit, $this>
     */
    public function visits(): MorphMany
    {
        return $this->morphMany(Visit::class, 'visitor');
    }

    /**
     * Create a visit log.
     *
     * @param array<string, mixed> $extraAttributes what the application keeps beside the visit, as key and value pairs
     */
    public function visit(Model|null $visitable = null, array $extraAttributes = []): Visit|null
    {
        return visitor()->setVisitor($this)->visit($visitable, $extraAttributes);
    }

    /**
     * Retrieve online users
     *
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeOnline(Builder $query, int $seconds = 180): Builder
    {
        return $query->whereHas('visits', function (Builder $query) use ($seconds): void {
            $query->where($query->qualifyColumn('created_at'), '>=', now()->subSeconds($seconds));
        });
    }

    /**
     * check if user is online
     */
    public function isOnline(int $seconds = 180): bool
    {
        return $this->visits()
            ->where('created_at', '>=', now()->subSeconds($seconds))
            ->exists();
    }
}
