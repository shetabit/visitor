<?php

namespace Shetabit\Visitor\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;

/**
 * A model that can be visited, the way an application would declare one.
 *
 * @property string $title
 */
class Post extends Model
{
    use Visitable;

    /**
     * @var list<string>
     */
    protected $fillable = ['title'];
}
