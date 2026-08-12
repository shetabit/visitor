<?php

namespace Shetabit\Visitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A single visit of a page, of a model, or of both.
 *
 * @property string|null $method
 * @property array<array-key, mixed>|null $request
 * @property string|null $url
 * @property string|null $referer
 * @property array<array-key, mixed>|null $languages
 * @property string|null $useragent
 * @property array<array-key, mixed>|null $headers
 * @property string|null $device
 * @property string|null $platform
 * @property string|null $browser
 * @property string|null $ip
 * @property array<array-key, mixed>|null $geo_raw
 * @property int|string|null $visitor_id
 * @property string|null $visitor_type
 * @property int|string|null $visitable_id
 * @property string|null $visitable_type
 */
class Visit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'method', 'request', 'url', 'referer',
        'languages', 'useragent', 'headers',
        'device', 'platform', 'browser', 'ip',
        'visitor_id', 'visitor_type', 'geo_raw',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        if (!isset($this->table)) {
            $this->setTable((string) config('visitor.table_name'));
        }

        parent::__construct($attributes);
    }

    /**
     * Get the owning visitable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function visitable(): MorphTo
    {
        return $this->morphTo('visitable');
    }

    /**
     * Get the owning user model.
     *
     * @return MorphTo<Model, $this>
     */
    public function visitor(): MorphTo
    {
        return $this->morphTo('visitor');
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request'   => 'array',
            'languages' => 'array',
            'headers'   => 'array',
            'geo_raw'   => 'array',
        ];
    }
}
