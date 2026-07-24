<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Quest;
use PHPUnit\Framework\TestCase;

class QuestStoryArcTest extends TestCase
{
    public function testNoArcByDefault(): void
    {
        $quest = new Quest();

        $this->assertNull($quest->getStoryArc());
        $this->assertNull($quest->getArcOrder());
        $this->assertFalse($quest->hasStoryArc());
    }

    public function testSetAndGetStoryArc(): void
    {
        $quest = new Quest();
        $quest->setStoryArc('intro');

        $this->assertSame('intro', $quest->getStoryArc());
        $this->assertTrue($quest->hasStoryArc());
        $this->assertTrue($quest->belongsToArc('intro'));
        $this->assertFalse($quest->belongsToArc('season_summer'));
    }

    public function testSetStoryArcNormalisesEmptyStringToNull(): void
    {
        $quest = new Quest();

        $quest->setStoryArc('   ');
        $this->assertNull($quest->getStoryArc());
        $this->assertFalse($quest->hasStoryArc());

        $quest->setStoryArc('  intro  ');
        $this->assertSame('intro', $quest->getStoryArc());

        $quest->setStoryArc(null);
        $this->assertNull($quest->getStoryArc());
    }

    public function testBelongsToArcIsFalseForIsolatedQuest(): void
    {
        $quest = new Quest();

        $this->assertFalse($quest->belongsToArc('intro'));
    }

    public function testSetAndGetArcOrder(): void
    {
        $quest = new Quest();
        $quest->setArcOrder(3);

        $this->assertSame(3, $quest->getArcOrder());

        $quest->setArcOrder(null);
        $this->assertNull($quest->getArcOrder());
    }

    public function testSortByArcOrderOrdersAscending(): void
    {
        $third = (new Quest())->setName('c')->setArcOrder(3);
        $first = (new Quest())->setName('a')->setArcOrder(1);
        $second = (new Quest())->setName('b')->setArcOrder(2);

        $sorted = Quest::sortByArcOrder([$third, $first, $second]);

        $this->assertSame(['a', 'b', 'c'], array_map(
            static fn (Quest $q): string => $q->getName(),
            $sorted
        ));
    }

    public function testSortByArcOrderPushesNullPositionsLast(): void
    {
        $unpositioned = (new Quest())->setName('none');
        $first = (new Quest())->setName('a')->setArcOrder(1);
        $second = (new Quest())->setName('b')->setArcOrder(2);

        $sorted = Quest::sortByArcOrder([$unpositioned, $second, $first]);

        $this->assertSame(['a', 'b', 'none'], array_map(
            static fn (Quest $q): string => $q->getName(),
            $sorted
        ));
    }

    public function testSortByArcOrderReturnsReindexedList(): void
    {
        $first = (new Quest())->setName('a')->setArcOrder(1);
        $second = (new Quest())->setName('b')->setArcOrder(2);

        $sorted = Quest::sortByArcOrder([5 => $second, 9 => $first]);

        $this->assertSame([0, 1], array_keys($sorted));
    }

    public function testSortByArcOrderIsStableForEqualPositions(): void
    {
        $a = (new Quest())->setName('a')->setArcOrder(1);
        $b = (new Quest())->setName('b')->setArcOrder(1);

        $sorted = Quest::sortByArcOrder([$a, $b]);

        $this->assertSame(['a', 'b'], array_map(
            static fn (Quest $q): string => $q->getName(),
            $sorted
        ));
    }
}
