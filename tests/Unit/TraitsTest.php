<?php

namespace Shetabit\Visitor\Tests\Unit;

use Shetabit\Visitor\Models\Visit;
use Shetabit\Visitor\Tests\Fixtures\Member;
use Shetabit\Visitor\Tests\Fixtures\User;
use Shetabit\Visitor\Tests\TestCase;

final class TraitsTest extends TestCase
{
    public function testAVisitableModelLogsAVisitOfItself() : void
    {
        $post = $this->createPost();

        // The visitor used to be a required argument, while the readme shows the
        // method being called without one.
        $visit = $post->createVisitLog();

        $this->assertInstanceOf(Visit::class, $visit);
        $this->assertTrue($post->is($visit->visitable));
        $this->assertNull($visit->visitor_id);
        $this->assertSame(1, $post->visitLogs()->count());
    }

    public function testAVisitableModelLogsWhoVisitedIt() : void
    {
        $post = $this->createPost();
        $user = $this->createUser();

        $visit = $post->createVisitLog($user);

        $this->assertTrue($user->is($visit?->visitor));
        $this->assertTrue($post->is($visit?->visitable));
    }

    public function testAVisitorLogsAVisit() : void
    {
        $user = $this->createUser();

        $visit = $user->visit();

        $this->assertInstanceOf(Visit::class, $visit);
        $this->assertTrue($user->is($visit->visitor));
        $this->assertNull($visit->visitable_id);
        $this->assertSame(1, $user->visits()->count());
    }

    public function testAVisitorLogsAVisitOfAModel() : void
    {
        $user = $this->createUser();
        $post = $this->createPost();

        $visit = $user->visit($post);

        $this->assertTrue($user->is($visit?->visitor));
        $this->assertTrue($post->is($visit?->visitable));
    }

    public function testAVisitorIsOnlineRightAfterAVisit() : void
    {
        $user = $this->createUser();

        $this->assertFalse($user->isOnline());

        $user->visit();

        $this->assertTrue($user->fresh()?->isOnline());
    }

    public function testAVisitorOfTenMinutesAgoIsNotOnline() : void
    {
        $user = $this->createUser();

        $this->travelTo(now()->subMinutes(10));
        $user->visit();
        $this->travelBack();

        $this->assertFalse($user->isOnline());
        $this->assertTrue($user->isOnline(3600));
    }

    public function testTheOnlineScopeOnlyFindsVisitorsOfTheLastSeconds() : void
    {
        $online = $this->createUser('online');
        $offline = $this->createUser('offline');

        $online->visit();

        $this->travelTo(now()->subMinutes(10));
        $offline->visit();
        $this->travelBack();

        $this->assertSame(['online'], User::online()->pluck('name')->all());
        $this->assertSame(['online', 'offline'], User::online(3600)->pluck('name')->all());
    }

    public function testTheCanVisitTraitLogsAndFindsTheVisitsOfAVisitor() : void
    {
        $member = Member::query()->create(['name' => 'a-member']);

        $this->assertFalse($member->isOnline());
        $this->assertSame([], Member::online()->pluck('name')->all());

        visitor()->setVisitor($member)->visit();

        // `isOnline()` used to ask for a `user` relation of the visits, which
        // does not exist, and for a `user_id` column, which does not either.
        $this->assertTrue($member->isOnline());
        $this->assertSame(['a-member'], Member::online()->pluck('name')->all());
        $this->assertSame(1, $member->visitLogs()->count());
    }

    public function testTheVisitsOfAModelCanBeCounted() : void
    {
        $post = $this->createPost();
        $user = $this->createUser();

        $post->createVisitLog($user);
        $post->createVisitLog($user);
        $post->createVisitLog();

        $this->assertSame(3, $post->visitLogs()->count());
        $this->assertSame(1, $post->visitLogs()->distinct('ip')->count('ip'));
        $this->assertSame(1, $post->visitLogs()->distinct('visitor_id')->count('visitor_id'));
    }
}
