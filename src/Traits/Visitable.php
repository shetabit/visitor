<?php

namespace Shetabit\Visitor\Traits;

use Illuminate\Support\Facades\Auth;
use Shetabit\Visitor\Models\Visit;
use Illuminate\Database\Eloquent\Builder;

trait Visitable
{
    /**
     * Get all of the model visits' log.
     *
     * @return mixed
     */
    public function visits()
    {
        return $this->morphMany(Visit::class, 'visitable');
    }

    /**
     * Create a visit log.
     *
     * @return mixed
     */
    public function visit()
    {
        $visitor = app('shetabit-visitor');

        $visit = $this->visits()->create([
            'method' => $visitor->method(),
            'request' => $visitor->request(),
            'url' => $visitor->url(),
            'referer' => $visitor->referer(),
            'languages' => $visitor->languages(),
            'useragent' => $visitor->userAgent(),
            'headers' => $visitor->httpHeaders(),
            'device' => $visitor->device(),
            'platform' => $visitor->platform(),
            'browser' => $visitor->browser(),
            'ip' => $visitor->ip(),
            'user_id' => request()->user() ? request()->user()->id : null,
            'user_type' => request()->user() ? get_class(request()->user()): null
        ]);

        return $visit;
    }

    /**
     * Alias for visits.
     *
     * @return mixed
     */
    public function views()
    {
        return $this->visits();
    }

    /**
     * Alias for visit
     *
     * @return mixed
     */
    public function view()
    {
        return $this->visit();
    }
}
