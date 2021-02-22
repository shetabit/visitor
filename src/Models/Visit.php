<?php

namespace Shetabit\Visitor\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    public function __construct(array $attributes = [])
    {
        if (!isset($this->table)) {
            $this->setTable(config('visitor.table_name'));
        }
        parent::__construct($attributes);
    }
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'method', 'request', 'url', 'referer',
        'languages', 'useragent', 'headers',
        'device', 'platform', 'browser', 'ip',
        'visitor_id', 'visitor_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'request'   => 'array',
        'languages' => 'array',
        'headers'   => 'array',
    ];

    /**
     * Get the owning visitable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function visitable()
    {
        return $this->morphTo('visitable');
    }

    /**
     * Get the owning user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function visitor()
    {
        return $this->morphTo('visitor');
    }
}
