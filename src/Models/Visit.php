<?php

namespace Shetabit\Visitor\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'visits';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'request', 'url', 'referer',
        'languages', 'useragent', 'headers',
        'device', 'platform', 'browser', 'ip',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'request' => 'array',
        'languages' => 'array',
        'headers' => 'array',
    ];

    /**
     * Get the owning visitable model.
     */
    public function visitable()
    {
        return $this->morphTo();
    }
}
