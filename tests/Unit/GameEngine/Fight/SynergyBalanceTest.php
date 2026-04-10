<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\Element;
use App\GameEngine\Fight\ElementalSynergyCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Validates that synergy damage multipliers stay within balanced bounds
 * and that every element participates in at least one synergy.
 */
class SynergyBalanceTest extends TestCase
{
    private ElementalSynergyCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ElementalSynergyCalculator();
    }

    public function testAllMultipliersWithinBalancedRange(): void
    {
        $synergies = $this->calculator->getAllSynergies();

        foreach ($synergies as $key => $data) {
            $multiplier = $data['damageMultiplier'];
            $this->assertGreaterThanOrEqual(1.0, $multiplier, "Synergy {$key} multiplier too low: {$multiplier}");
            $this->assertLessThanOrEqual(1.5, $multiplier, "Synergy {$key} multiplier too high: {$multiplier}");
        }
    }

    public function testMaxMultiplierVarianceBelow30Percent(): void
    {
        $synergies = $this->calculator->getAllSynergies();
        $multipliers = array_column($synergies, 'damageMultiplier');

        $min = min($multipliers);
        $max = max($multipliers);
        $variance = ($max - $min) / $min;

        $this->assertLessThanOrEqual(0.30, $variance, "Synergy multiplier variance {$variance} exceeds 30%: min={$min}, max={$max}");
    }

    public function testEveryElementParticipatesInAtLeastOneSynergy(): void
    {
        $elements = [
            Element::Fire, Element::Water, Element::Earth, Element::Air,
            Element::Light, Element::Dark, Element::Metal, Element::Beast,
        ];

        foreach ($elements as $element) {
            $hasSynergy = false;
            foreach ($elements as $other) {
                if ($element === $other) {
                    continue;
                }
                if ($this->calculator->checkSynergy($element, $other) !== null) {
                    $hasSynergy = true;
                    break;
                }
            }
            $this->assertTrue($hasSynergy, "Element {$element->value} has no synergy with any other element");
        }
    }

    /**
     * Validates that applying synergy to typical tier 2-4 spell damage
     * keeps output within expected bounds.
     */
    public function testSynergyDamageOutputForTier2To4Spells(): void
    {
        $synergies = $this->calculator->getAllSynergies();
        $tierDamage = [2 => 3, 3 => 5, 4 => 7];

        foreach ($synergies as $key => $data) {
            foreach ($tierDamage as $tier => $baseDamage) {
                $result = $this->calculator->applySynergyDamage($baseDamage, $data);
                $this->assertGreaterThanOrEqual(
                    $baseDamage,
                    $result,
                    "Synergy {$key} reduced damage at tier {$tier}"
                );
                $maxExpected = (int) ceil($baseDamage * 1.5);
                $this->assertLessThanOrEqual(
                    $maxExpected,
                    $result,
                    "Synergy {$key} damage {$result} exceeds cap {$maxExpected} at tier {$tier}"
                );
            }
        }
    }

    public function testSelfDamageNeverExceedsTenPercent(): void
    {
        $synergies = $this->calculator->getAllSynergies();

        foreach ($synergies as $key => $data) {
            $selfDamagePercent = $data['selfDamagePercent'] ?? 0;
            $this->assertLessThanOrEqual(
                10,
                $selfDamagePercent,
                "Synergy {$key} self-damage {$selfDamagePercent}% exceeds 10%"
            );
        }
    }
}
