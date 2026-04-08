<?php

namespace App\Tests\Unit\Enum;

use App\Enum\DungeonDifficulty;
use PHPUnit\Framework\TestCase;

class DungeonDifficultyTest extends TestCase
{
    public function testStatMultiplierValues(): void
    {
        $this->assertSame(1.0, DungeonDifficulty::Normal->statMultiplier());
        $this->assertSame(1.5, DungeonDifficulty::Heroic->statMultiplier());
        $this->assertSame(2.5, DungeonDifficulty::Mythic->statMultiplier());
    }

    public function testDamageMultiplierValues(): void
    {
        $this->assertSame(1.0, DungeonDifficulty::Normal->damageMultiplier());
        $this->assertSame(1.25, DungeonDifficulty::Heroic->damageMultiplier());
        $this->assertSame(1.75, DungeonDifficulty::Mythic->damageMultiplier());
    }

    public function testDropMultiplierValues(): void
    {
        $this->assertSame(1.0, DungeonDifficulty::Normal->dropMultiplier());
        $this->assertSame(1.5, DungeonDifficulty::Heroic->dropMultiplier());
        $this->assertSame(2.0, DungeonDifficulty::Mythic->dropMultiplier());
    }

    public function testXpMultiplierValues(): void
    {
        $this->assertSame(1.0, DungeonDifficulty::Normal->xpMultiplier());
        $this->assertSame(1.5, DungeonDifficulty::Heroic->xpMultiplier());
        $this->assertSame(2.5, DungeonDifficulty::Mythic->xpMultiplier());
    }

    public function testDamageMultiplierAlwaysLessThanStatMultiplier(): void
    {
        foreach (DungeonDifficulty::cases() as $difficulty) {
            $this->assertLessThanOrEqual(
                $difficulty->statMultiplier(),
                $difficulty->damageMultiplier(),
                sprintf('%s: damageMultiplier should be <= statMultiplier', $difficulty->name)
            );
        }
    }

    public function testDropMultiplierAlwaysLessThanStatMultiplier(): void
    {
        foreach (DungeonDifficulty::cases() as $difficulty) {
            $this->assertLessThanOrEqual(
                $difficulty->statMultiplier(),
                $difficulty->dropMultiplier(),
                sprintf('%s: dropMultiplier should be <= statMultiplier', $difficulty->name)
            );
        }
    }

    public function testXpMultiplierAlwaysLessThanOrEqualStatMultiplier(): void
    {
        foreach (DungeonDifficulty::cases() as $difficulty) {
            $this->assertLessThanOrEqual(
                $difficulty->statMultiplier(),
                $difficulty->xpMultiplier(),
                sprintf('%s: xpMultiplier should be <= statMultiplier', $difficulty->name)
            );
        }
    }

    public function testRewardScalesWithDifficulty(): void
    {
        $difficulties = DungeonDifficulty::cases();

        for ($i = 1; $i < count($difficulties); ++$i) {
            $current = $difficulties[$i];
            $previous = $difficulties[$i - 1];

            $this->assertGreaterThan(
                $previous->dropMultiplier(),
                $current->dropMultiplier(),
                sprintf('%s dropMultiplier should be > %s', $current->name, $previous->name)
            );
            $this->assertGreaterThan(
                $previous->xpMultiplier(),
                $current->xpMultiplier(),
                sprintf('%s xpMultiplier should be > %s', $current->name, $previous->name)
            );
        }
    }
}
