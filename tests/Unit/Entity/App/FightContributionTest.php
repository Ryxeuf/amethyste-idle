<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use PHPUnit\Framework\TestCase;

class FightContributionTest extends TestCase
{
    public function testAddContributionAccumulatesDamage(): void
    {
        $fight = new Fight();

        $fight->addContribution(1, 100);
        $fight->addContribution(1, 50);

        $this->assertSame(150, $fight->getPlayerContribution(1));
    }

    public function testGetPlayerContributionReturnsZeroForUnknownPlayer(): void
    {
        $fight = new Fight();

        $this->assertSame(0, $fight->getPlayerContribution(999));
    }

    public function testGetRankedContributorsReturnsSortedByDamageDesc(): void
    {
        $fight = new Fight();

        $fight->addContribution(1, 50);
        $fight->addContribution(2, 200);
        $fight->addContribution(3, 100);

        $ranked = $fight->getRankedContributors();

        $this->assertCount(3, $ranked);

        // Player 2 should be rank 1 (200 damage)
        $this->assertSame(2, $ranked[0]['playerId']);
        $this->assertSame(200, $ranked[0]['damage']);
        $this->assertSame(1, $ranked[0]['rank']);

        // Player 3 should be rank 2 (100 damage)
        $this->assertSame(3, $ranked[1]['playerId']);
        $this->assertSame(100, $ranked[1]['damage']);
        $this->assertSame(2, $ranked[1]['rank']);

        // Player 1 should be rank 3 (50 damage)
        $this->assertSame(1, $ranked[2]['playerId']);
        $this->assertSame(50, $ranked[2]['damage']);
        $this->assertSame(3, $ranked[2]['rank']);
    }

    public function testGetRankedContributorsReturnsEmptyArrayWhenNoContributions(): void
    {
        $fight = new Fight();

        $this->assertSame([], $fight->getRankedContributors());
    }

    public function testIsWorldBossFightReturnsTrueWhenMobIsWorldBoss(): void
    {
        $fight = new Fight();

        $mob = $this->createMock(Mob::class);
        $mob->method('isWorldBoss')->willReturn(true);
        $fight->addMob($mob);

        $this->assertTrue($fight->isWorldBossFight());
    }

    public function testIsWorldBossFightReturnsFalseWhenNoWorldBoss(): void
    {
        $fight = new Fight();

        $mob = $this->createMock(Mob::class);
        $mob->method('isWorldBoss')->willReturn(false);
        $fight->addMob($mob);

        $this->assertFalse($fight->isWorldBossFight());
    }

    public function testIsWorldBossFightReturnsFalseWhenNoMobs(): void
    {
        $fight = new Fight();

        $this->assertFalse($fight->isWorldBossFight());
    }

    public function testMultiplePlayersContributions(): void
    {
        $fight = new Fight();

        // Simulate 5 players contributing
        $fight->addContribution(1, 500);
        $fight->addContribution(2, 300);
        $fight->addContribution(3, 200);
        $fight->addContribution(4, 100);
        $fight->addContribution(5, 50);

        $ranked = $fight->getRankedContributors();

        $this->assertCount(5, $ranked);

        // Top 3 should be players 1, 2, 3
        $this->assertSame(1, $ranked[0]['playerId']);
        $this->assertSame(2, $ranked[1]['playerId']);
        $this->assertSame(3, $ranked[2]['playerId']);

        // Ranks 4 and 5
        $this->assertSame(4, $ranked[3]['rank']);
        $this->assertSame(5, $ranked[4]['rank']);
    }
}
