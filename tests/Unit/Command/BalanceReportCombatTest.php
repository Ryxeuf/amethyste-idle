<?php

namespace App\Tests\Unit\Command;

use App\Command\BalanceReportCommand;
use PHPUnit\Framework\TestCase;

class BalanceReportCombatTest extends TestCase
{
    /**
     * @return string[]
     */
    private function invokeDetectCombatAlerts(array $tiers): array
    {
        $command = new \ReflectionClass(BalanceReportCommand::class);
        $method = $command->getMethod('detectCombatAlerts');

        $instance = $command->newInstanceWithoutConstructor();

        return $method->invoke($instance, $tiers);
    }

    public function testNoAlertsWhenBalanced(): void
    {
        $tiers = [
            '1:Slime' => [
                'level' => 1, 'monster_name' => 'Slime', 'is_boss' => false,
                'total_fights' => 20, 'victories' => 18, 'defeats' => 2, 'flees' => 0,
                'avg_turns' => 5.0, 'avg_player_dps' => 10.0,
            ],
            '2:Goblin' => [
                'level' => 2, 'monster_name' => 'Goblin', 'is_boss' => false,
                'total_fights' => 15, 'victories' => 13, 'defeats' => 2, 'flees' => 0,
                'avg_turns' => 7.0, 'avg_player_dps' => 12.0,
            ],
        ];

        $alerts = $this->invokeDetectCombatAlerts($tiers);

        $this->assertSame([], $alerts);
    }

    public function testHighDeathRateAlert(): void
    {
        $tiers = [
            '5:Dragon' => [
                'level' => 5, 'monster_name' => 'Dragon', 'is_boss' => false,
                'total_fights' => 10, 'victories' => 3, 'defeats' => 7, 'flees' => 0,
                'avg_turns' => 10.0, 'avg_player_dps' => 8.0,
            ],
        ];

        $alerts = $this->invokeDetectCombatAlerts($tiers);

        $this->assertCount(1, $alerts);
        $this->assertStringContainsString('[COMBAT] Dragon (lvl 5)', $alerts[0]);
        $this->assertStringContainsString('taux de defaite', $alerts[0]);
    }

    public function testHighDeathRateIgnoredWithFewFights(): void
    {
        $tiers = [
            '5:Dragon' => [
                'level' => 5, 'monster_name' => 'Dragon', 'is_boss' => false,
                'total_fights' => 3, 'victories' => 1, 'defeats' => 2, 'flees' => 0,
                'avg_turns' => 10.0, 'avg_player_dps' => 8.0,
            ],
        ];

        $alerts = $this->invokeDetectCombatAlerts($tiers);

        $this->assertSame([], $alerts);
    }

    public function testBossesExcludedFromDeathRateAlert(): void
    {
        $tiers = [
            '10:Boss' => [
                'level' => 10, 'monster_name' => 'Boss', 'is_boss' => true,
                'total_fights' => 20, 'victories' => 5, 'defeats' => 15, 'flees' => 0,
                'avg_turns' => 15.0, 'avg_player_dps' => 20.0,
            ],
        ];

        $alerts = $this->invokeDetectCombatAlerts($tiers);

        $this->assertSame([], $alerts);
    }

    public function testDpsVarianceAlert(): void
    {
        $tiers = [
            '1:Slime' => [
                'level' => 1, 'monster_name' => 'Slime', 'is_boss' => false,
                'total_fights' => 20, 'victories' => 18, 'defeats' => 2, 'flees' => 0,
                'avg_turns' => 5.0, 'avg_player_dps' => 10.0,
            ],
            '2:Golem' => [
                'level' => 2, 'monster_name' => 'Golem', 'is_boss' => false,
                'total_fights' => 15, 'victories' => 13, 'defeats' => 2, 'flees' => 0,
                'avg_turns' => 7.0, 'avg_player_dps' => 20.0, // +100% variance
            ],
        ];

        $alerts = $this->invokeDetectCombatAlerts($tiers);

        $this->assertCount(1, $alerts);
        $this->assertStringContainsString('Ecart DPS joueur entre lvl 1', $alerts[0]);
    }

    public function testLongFightAlert(): void
    {
        $tiers = [
            '3:Turtle' => [
                'level' => 3, 'monster_name' => 'Turtle', 'is_boss' => false,
                'total_fights' => 10, 'victories' => 9, 'defeats' => 1, 'flees' => 0,
                'avg_turns' => 25.0, 'avg_player_dps' => 5.0,
            ],
        ];

        $alerts = $this->invokeDetectCombatAlerts($tiers);

        $this->assertCount(1, $alerts);
        $this->assertStringContainsString('duree moyenne 25.0 tours', $alerts[0]);
    }

    public function testMultipleAlertsFromSameData(): void
    {
        $tiers = [
            '1:Slime' => [
                'level' => 1, 'monster_name' => 'Slime', 'is_boss' => false,
                'total_fights' => 10, 'victories' => 3, 'defeats' => 7, 'flees' => 0,
                'avg_turns' => 25.0, 'avg_player_dps' => 10.0,
            ],
        ];

        $alerts = $this->invokeDetectCombatAlerts($tiers);

        // Should have both: high death rate + long fight
        $this->assertCount(2, $alerts);
    }
}
