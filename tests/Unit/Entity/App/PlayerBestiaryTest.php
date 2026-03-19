<?php

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\Player;
use App\Entity\App\PlayerBestiary;
use App\Entity\Game\Monster;
use PHPUnit\Framework\TestCase;

class PlayerBestiaryTest extends TestCase
{
    private function createEntry(int $killCount = 1): PlayerBestiary
    {
        $player = $this->createMock(Player::class);
        $monster = $this->createMock(Monster::class);

        $entry = new PlayerBestiary($player, $monster);

        // Increment to reach desired killCount (constructor sets it to 1)
        for ($i = 1; $i < $killCount; ++$i) {
            $entry->incrementKillCount();
        }

        return $entry;
    }

    public function testConstructorSetsInitialValues(): void
    {
        $player = $this->createMock(Player::class);
        $monster = $this->createMock(Monster::class);

        $entry = new PlayerBestiary($player, $monster);

        $this->assertSame($player, $entry->getPlayer());
        $this->assertSame($monster, $entry->getMonster());
        $this->assertSame(1, $entry->getKillCount());
        $this->assertInstanceOf(\DateTimeInterface::class, $entry->getFirstEncounteredAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $entry->getFirstKilledAt());
    }

    public function testIncrementKillCount(): void
    {
        $entry = $this->createEntry(1);
        $this->assertSame(1, $entry->getKillCount());

        $entry->incrementKillCount();
        $this->assertSame(2, $entry->getKillCount());
    }

    public function testTierWeaknessesAt10Kills(): void
    {
        $this->assertFalse($this->createEntry(9)->hasWeaknessesRevealed());
        $this->assertTrue($this->createEntry(10)->hasWeaknessesRevealed());
        $this->assertTrue($this->createEntry(11)->hasWeaknessesRevealed());
    }

    public function testTierLootTableAt50Kills(): void
    {
        $this->assertFalse($this->createEntry(49)->hasLootTableRevealed());
        $this->assertTrue($this->createEntry(50)->hasLootTableRevealed());
        $this->assertTrue($this->createEntry(51)->hasLootTableRevealed());
    }

    public function testTierHunterTitleAt100Kills(): void
    {
        $this->assertFalse($this->createEntry(99)->hasHunterTitle());
        $this->assertTrue($this->createEntry(100)->hasHunterTitle());
        $this->assertTrue($this->createEntry(150)->hasHunterTitle());
    }

    public function testGetTier(): void
    {
        $this->assertSame(0, $this->createEntry(1)->getTier());
        $this->assertSame(0, $this->createEntry(9)->getTier());
        $this->assertSame(1, $this->createEntry(10)->getTier());
        $this->assertSame(1, $this->createEntry(49)->getTier());
        $this->assertSame(2, $this->createEntry(50)->getTier());
        $this->assertSame(2, $this->createEntry(99)->getTier());
        $this->assertSame(3, $this->createEntry(100)->getTier());
        $this->assertSame(3, $this->createEntry(200)->getTier());
    }

    public function testGetNextTierThreshold(): void
    {
        $this->assertSame(10, $this->createEntry(1)->getNextTierThreshold());
        $this->assertSame(10, $this->createEntry(9)->getNextTierThreshold());
        $this->assertSame(50, $this->createEntry(10)->getNextTierThreshold());
        $this->assertSame(50, $this->createEntry(49)->getNextTierThreshold());
        $this->assertSame(100, $this->createEntry(50)->getNextTierThreshold());
        $this->assertSame(100, $this->createEntry(99)->getNextTierThreshold());
        $this->assertNull($this->createEntry(100)->getNextTierThreshold());
        $this->assertNull($this->createEntry(200)->getNextTierThreshold());
    }
}
