<?php

namespace Shetabit\Visitor\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * A model without any of the traits of the package.
 *
 * @property string $title
 */
class Page extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['title'];
}
