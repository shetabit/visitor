<?php

namespace Shetabit\Visitor\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\CanVisit;

/**
 * A visitor that uses the `CanVisit` trait instead of the `Visitor` one.
 *
 * @property string $name
 */
class Member extends Model
{
    use CanVisit;

    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $fillable = ['name'];
}
