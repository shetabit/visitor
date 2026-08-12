<?php

namespace Shetabit\Visitor\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Shetabit\Visitor\Traits\Visitor;

/**
 * A user who visits, the way an application would declare one.
 *
 * @property string $name
 */
class User extends Authenticatable
{
    use Visitor;

    /**
     * @var list<string>
     */
    protected $fillable = ['name'];
}
