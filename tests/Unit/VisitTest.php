<?php

namespace Shetabit\Visitor\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testItIsKeptInTheDefaultConnectionByDefault() : void
    {
        $this->assertNull(new Visit()->getConnectionName());
    }

    #[DataProvider('defaultConnections')]
    public function testAConfigurationWithoutAConnectionKeepsItInTheDefaultOne(mixed $connection) : void
    {
        config()->set('visitor.connection', $connection);

        $this->assertNull(new Visit()->getConnectionName());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function defaultConnections() : array
    {
        return ['null' => [null], 'an empty name' => ['']];
    }

    public function testItIsKeptInTheConnectionOfTheConfiguration() : void
    {
        // A connection that exists, since the migrations are rolled back through it after the test
        config()->set('visitor.connection', 'testing');

        $this->assertSame('testing', new Visit()->getConnectionName());
    }

    public function testAModelOfTheApplicationKeepsTheConnectionItNames() : void
    {
        config()->set('visitor.connection', 'testing');

        $visit = new class extends Visit {
            /**
             * @var string|null
             */
            protected $connection = 'another';
        };

        $this->assertSame('another', $visit->getConnectionName());
    }

    #[DataProvider('encodedUrls')]
    public function testItDecodesTheUrlForShowingIt(string|null $url, string|null $decoded) : void
    {
        $visit = new Visit(['url' => $url]);

        $this->assertSame($decoded, $visit->decoded_url);
        $this->assertSame($url, $visit->url);
    }

    /**
     * @return array<string, array{string|null, string|null}>
     */
    public static function encodedUrls() : array
    {
        return [
            'a persian path' => [
                'https://example.com/products/%D9%86%DA%AF%D8%A7%D9%87-%D8%A8%D9%87-%D8%B3%D8%A7%DB%8C%D8%AA',
                'https://example.com/products/نگاه-به-سایت',
            ],
            'a persian query' => [
                'https://example.com/search?q=%D9%85%D8%AD%D8%B5%D9%88%D9%84',
                'https://example.com/search?q=محصول',
            ],
            'a url without anything encoded' => [
                'https://example.com/products?page=2',
                'https://example.com/products?page=2',
            ],
            'a plus sign' => ['https://example.com/c++/a+b', 'https://example.com/c++/a+b'],
            'bytes that are not utf-8' => ['https://example.com/%FF', 'https://example.com/?'],
            'no url' => [null, null],
        ];
    }

    public function testTheDecodedUrlIsReadFromTheStoredOne() : void
    {
        $url = 'https://example.com/products/%D9%85%D8%AD%D8%B5%D9%88%D9%84';
        $visit = Visit::query()->create(['url' => $url]);

        $stored = Visit::query()->whereKey($visit->getKey())->sole();

        $this->assertSame($url, $stored->url);
        $this->assertSame('https://example.com/products/محصول', $stored->decoded_url);
        $this->assertArrayNotHasKey('decoded_url', $stored->getAttributes());
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
