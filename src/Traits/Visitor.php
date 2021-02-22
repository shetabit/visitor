<?php

namespace Shetabit\Visitor\Traits;

use Illuminate\Support\Facades\Auth;
use Shetabit\Visitor\Models\Visit;
use Illuminate\Database\Eloquent\Builder;

trait Visitor
{
    /**
     * Get all of the post's comments.
     * @return mixed
     */
    public function visits()
    {
        return $this->morphMany(Visit::class, 'visitor');
    }

    /**
     * Create a visit log.
     * @param Model|null $visitable
     * @return mixed
     */
    public function visit(?Model $visitable = NULL)
    {
        return app('shetabit-visitor')->setVisitor($this)->visit($visitable);
    }

    /**
     * Retrieve online users
     * @param $query
     * @param int $seconds
     * @return mixed
     */
    public function scopeOnline($query, $seconds = 180)
    {
        $time = now()->subSeconds($seconds);

        $query->whereHas('visits', function ($query) use ($time) {
            $query->where(config('visitor.table_name') . ".created_at", '>=', $time->toDateTime());

        });
    }

    /**
     * check if user is online
     * @param int $seconds
     * @return bool
     */
    public function isOnline($seconds = 180)
    {
        $time = now()->subSeconds($seconds);

        return $this->visits()->whereHasMorph('visitor', [static::class], function ($query) use ($time) {
                $query
                    ->where('visitor_id', $this->id)
                    ->where(config('visitor.table_name') . ".created_at", '>=', $time->toDateTime());
            })->count() > 0;
    }
}
