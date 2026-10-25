<?php

namespace Shetabit\Visitor\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Tests\TestCase;

final class VisitTest extends TestCase
{
    public function testItIsStoredInTheTableOfTheConfiguration() : void
    {
        $this->assertSame('shetabit_visits', new Visit()->getTable());
    }

    public function testTheTableOfTheConfigurationCanBeChanged() : void
    {
        config()->set('visitor.table_name', 'another_table');

        $this->assertSame('another_table', new Visit()->getTable());
    }

    public function testItCastsTheColumnsThatHoldJson() : void
    {
        $visit = Visit::query()->create([
            'method' => 'GET',
            'route_name' => 'products.index',
            'request' => ['page' => '2'],
            'languages' => ['en-us', 'en'],
            'headers' => ['user-agent' => ['Firefox']],
            'geo_raw' => ['country_code' => 'IR'],
            'extra_attributes' => ['mobile' => '09120000000'],
        ]);

        $stored = Visit::query()->whereKey($visit->getKey())->sole();

        $this->assertSame('products.index', $stored->route_name);
        $this->assertSame(['page' => '2'], $stored->request);
        $this->assertSame(['en-us', 'en'], $stored->languages);
        $this->assertSame(['user-agent' => ['Firefox']], $stored->headers);
        $this->assertSame(['country_code' => 'IR'], $stored->geo_raw);
        $this->assertSame(['mobile' => '09120000000'], $stored->extra_attributes);
    }

    public function testItBelongsToTheModelThatWasVisitedAndToTheVisitor() : void
    {
        $post = $this->createPost();
        $user = $this->createUser();

        $visit = visitor()->setVisitor($user)->visit($post);

        $this->assertInstanceOf(Visit::class, $visit);
        $this->assertInstanceOf(MorphTo::class, $visit->visitable());
        $this->assertInstanceOf(MorphTo::class, $visit->visitor());
        $this->assertTrue($post->is($visit->visitable));
        $this->assertTrue($user->is($visit->visitor));
    }
}
