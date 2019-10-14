<?php

namespace Shetabit\Visitor\Traits;

use Shetabit\Visitor\Models\Visit;
use Illuminate\Database\Eloquent\Builder;

trait Visitable
{
    /**
     * Get all of the post's comments.
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

        return $this->visits()->create([
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
        ]);
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
