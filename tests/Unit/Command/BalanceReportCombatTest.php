<?php

namespace App\Tests\Unit\Command;

use App\Command\BalanceReportCommand;
use PHPUnit\Framework\TestCase;

class BalanceReportCombatTest extends TestCase
{
    /**
     * @param list<array{monster_name: string, level: int, fight_count: int, total_damage: int, dps: float}> $playerDpsRows
     *
     * @return string[]
     */
    private function invokeDetectDpsVarianceAlerts(array $playerDpsRows): array
    {
        $ref = new \ReflectionClass(BalanceReportCommand::class);
        $method = $ref->getMethod('detectDpsVarianceAlerts');
        $instance = $ref->newInstanceWithoutConstructor();

        return $method->invoke($instance, $playerDpsRows);
    }

    /**
     * @param list<array{monster_name: string, level: int, victories: int, defeats: int, flees: int, avg_turns: float}> $monsterOutcomes
     *
     * @return string[]
     */
    private function invokeDetectLongFightAlerts(array $monsterOutcomes): array
    {
        $ref = new \ReflectionClass(BalanceReportCommand::class);
        $method = $ref->getMethod('detectLongFightAlerts');
        $instance = $ref->newInstanceWithoutConstructor();

        return $method->invoke($instance, $monsterOutcomes);
    }

    public function testNoDpsVarianceAlertWhenBalanced(): void
    {
        $rows = [
            ['monster_name' => 'Slime', 'level' => 1, 'fight_count' => 10, 'total_damage' => 500, 'dps' => 10.0],
            ['monster_name' => 'Goblin', 'level' => 2, 'fight_count' => 10, 'total_damage' => 600, 'dps' => 12.0],
        ];

        $alerts = $this->invokeDetectDpsVarianceAlerts($rows);

        $this->assertSame([], $alerts);
    }

    public function testDpsVarianceAlertWhenLargeGap(): void
    {
        $rows = [
            ['monster_name' => 'Slime', 'level' => 1, 'fight_count' => 10, 'total_damage' => 500, 'dps' => 10.0],
            ['monster_name' => 'Golem', 'level' => 2, 'fight_count' => 10, 'total_damage' => 1000, 'dps' => 20.0],
        ];

        $alerts = $this->invokeDetectDpsVarianceAlerts($rows);

        $this->assertCount(1, $alerts);
        $this->assertStringContainsString('Ecart DPS joueur entre lvl 1', $alerts[0]);
        $this->assertStringContainsString('lvl 2', $alerts[0]);
    }

    public function testDpsVarianceIgnoredWithFewFights(): void
    {
        $rows = [
            ['monster_name' => 'Slime', 'level' => 1, 'fight_count' => 2, 'total_damage' => 500, 'dps' => 10.0],
            ['monster_name' => 'Golem', 'level' => 2, 'fight_count' => 2, 'total_damage' => 1000, 'dps' => 20.0],
        ];

        $alerts = $this->invokeDetectDpsVarianceAlerts($rows);

        $this->assertSame([], $alerts);
    }

    public function testDpsVarianceAggregatesMultipleMonstersPerLevel(): void
    {
        $rows = [
            ['monster_name' => 'Slime', 'level' => 1, 'fight_count' => 10, 'total_damage' => 500, 'dps' => 10.0],
            ['monster_name' => 'Bat', 'level' => 1, 'fight_count' => 10, 'total_damage' => 600, 'dps' => 12.0],
            // avg DPS for level 1 = 11.0, level 2 = 11.5 => no alert
            ['monster_name' => 'Goblin', 'level' => 2, 'fight_count' => 10, 'total_damage' => 500, 'dps' => 11.5],
        ];

        $alerts = $this->invokeDetectDpsVarianceAlerts($rows);

        $this->assertSame([], $alerts);
    }

    public function testNoLongFightAlertWhenShort(): void
    {
        $outcomes = [
            ['monster_name' => 'Slime', 'level' => 1, 'victories' => 18, 'defeats' => 2, 'flees' => 0, 'avg_turns' => 5.0],
        ];

        $alerts = $this->invokeDetectLongFightAlerts($outcomes);

        $this->assertSame([], $alerts);
    }

    public function testLongFightAlertWhenExceedsThreshold(): void
    {
        $outcomes = [
            ['monster_name' => 'Turtle', 'level' => 3, 'victories' => 9, 'defeats' => 1, 'flees' => 0, 'avg_turns' => 25.0],
        ];

        $alerts = $this->invokeDetectLongFightAlerts($outcomes);

        $this->assertCount(1, $alerts);
        $this->assertStringContainsString('Turtle (lvl 3)', $alerts[0]);
        $this->assertStringContainsString('25.0 tours', $alerts[0]);
    }

    public function testLongFightIgnoredWithFewFights(): void
    {
        $outcomes = [
            ['monster_name' => 'Turtle', 'level' => 3, 'victories' => 3, 'defeats' => 1, 'flees' => 0, 'avg_turns' => 25.0],
        ];

        $alerts = $this->invokeDetectLongFightAlerts($outcomes);

        $this->assertSame([], $alerts);
    }
}
