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
        $this->assertSame(1.25, DungeonDifficulty::Heroic->dropMultiplier());
        $this->assertSame(1.5, DungeonDifficulty::Mythic->dropMultiplier());
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
}
