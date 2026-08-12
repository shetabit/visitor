<?php

namespace Shetabit\Visitor\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shetabit\Visitor\Support\AcceptLanguage;

final class AcceptLanguageTest extends TestCase
{
    public function testItReadsTheLanguagesOfTheHeader() : void
    {
        $this->assertSame(['en-us', 'en'], AcceptLanguage::parse('en-US,en'));
    }

    public function testItOrdersTheLanguagesByTheirPriority() : void
    {
        $this->assertSame(
            ['de', 'fr', 'en'],
            AcceptLanguage::parse('en;q=0.3,fr;q=0.7,de')
        );
    }

    public function testItIgnoresTheWhitespaceAroundALanguage() : void
    {
        $this->assertSame(['fa-ir', 'fa'], AcceptLanguage::parse('fa-IR, fa ;q=0.9'));
    }

    public function testItAnswersWithAnEmptyListForAHeaderThatIsNotThere() : void
    {
        $this->assertSame([], AcceptLanguage::parse(null));
        $this->assertSame([], AcceptLanguage::parse(''));
        $this->assertSame([], AcceptLanguage::parse('   '));
    }

    public function testItSkipsEmptyPieces() : void
    {
        $this->assertSame(['en'], AcceptLanguage::parse('en,,'));
    }
}
