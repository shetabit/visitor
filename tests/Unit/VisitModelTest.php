<?php

namespace Shetabit\Visitor\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Support\VisitModel;
use Shetabit\Visitor\Tests\Fixtures\CustomVisit;
use Shetabit\Visitor\Tests\Fixtures\Member;
use Shetabit\Visitor\Tests\Fixtures\Post;
use Shetabit\Visitor\Tests\TestCase;

/**
 * Visits logged with a model of the application, which keeps columns of its own beside a visit.
 */
final class VisitModelTest extends TestCase
{
    public function testTheVisitsAreLoggedWithThePackageModelByDefault() : void
    {
        $this->assertSame(Visit::class, VisitModel::name());
        $this->assertSame(Visit::class, visitor()->visit()?->getMorphClass());
    }

    public function testTheVisitsAreLoggedWithThePackageModelWithoutAConfiguration() : void
    {
        // A configuration that was published before the model could be configured has no `model`
        config()->set('visitor.model', null);

        $this->assertSame(Visit::class, VisitModel::name());
    }

    #[DataProvider('modelsThatAreNoVisitModel')]
    public function testItRefusesAModelThatDoesNotExtendThePackageModel(mixed $model) : void
    {
        config()->set('visitor.model', $model);

        $this->expectException(InvalidArgumentException::class);

        VisitModel::name();
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function modelsThatAreNoVisitModel() : array
    {
        return [
            'another model' => [Post::class],
            'a class that does not exist' => ['App\\Models\\NotThere'],
            'not a class name' => [42],
        ];
    }

    public function testAVisitKeepsTheColumnsOfTheModelOfTheApplication() : void
    {
        config()->set('visitor.model', CustomVisit::class);

        $visit = visitor()->visit(null, ['name' => 'Mahdi', 'mobile' => '09120000000']);

        $this->assertInstanceOf(CustomVisit::class, $visit);

        $stored = CustomVisit::query()->whereKey($visit->getKey())->sole();

        $this->assertSame('Mahdi', $stored->name);
        $this->assertSame('09120000000', $stored->mobile);
        $this->assertSame('http://localhost', $stored->url);
    }

    public function testTheAttributesTakeThePlaceOfTheOnesThePackageFills() : void
    {
        $visit = visitor()->visit(null, ['url' => 'https://example.com/another-page']);

        $this->assertSame('https://example.com/another-page', $visit?->url);
    }

    public function testTheVisitsOfAVisitableModelAreLoggedWithTheModelOfTheApplication() : void
    {
        config()->set('visitor.model', CustomVisit::class);
        $post = $this->createPost();

        $post->createVisitLog(null, ['mobile' => '09120000000']);

        $visit = $post->visitLogs()->sole();

        $this->assertInstanceOf(CustomVisit::class, $visit);
        $this->assertSame('09120000000', $visit->mobile);
    }

    public function testTheVisitsOfAVisitorAreLoggedWithTheModelOfTheApplication() : void
    {
        config()->set('visitor.model', CustomVisit::class);
        $user = $this->createUser();
        $post = $this->createPost();

        $user->visit($post, ['name' => 'Mahdi']);

        $visit = $user->visits()->sole();

        $this->assertInstanceOf(CustomVisit::class, $visit);
        $this->assertSame('Mahdi', $visit->name);
        $this->assertTrue($post->is($visit->visitable));
        $this->assertTrue($user->isOnline());
        $this->assertTrue(visitor()->isOnline($user));
    }

    public function testTheVisitsOfAMemberAreReadWithTheModelOfTheApplication() : void
    {
        config()->set('visitor.model', CustomVisit::class);
        $member = Member::query()->create(['name' => 'Mahdi']);

        visitor()->setVisitor($member)->visit(null, ['mobile' => '09120000000']);

        $this->assertInstanceOf(CustomVisit::class, $member->visitLogs()->sole());
        $this->assertTrue($member->isOnline());
    }
}
