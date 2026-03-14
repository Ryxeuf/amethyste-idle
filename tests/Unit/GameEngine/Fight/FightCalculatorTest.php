<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\GameEngine\Fight\FightCalculator;
use PHPUnit\Framework\TestCase;

class FightCalculatorTest extends TestCase
{
    public function testHasAttackHitWith100PercentAlwaysReturnsTrue(): void
    {
        // random_int(0,99) < 100 => toujours vrai
        for ($i = 0; $i < 50; $i++) {
            $this->assertTrue(
                FightCalculator::hasAttackHit(100),
                'Un taux de toucher de 100 devrait toujours toucher'
            );
        }
    }

    public function testHasAttackHitWith0PercentAlwaysReturnsFalse(): void
    {
        // random_int(0,99) < 0 => toujours faux
        for ($i = 0; $i < 50; $i++) {
            $this->assertFalse(
                FightCalculator::hasAttackHit(0),
                'Un taux de toucher de 0 ne devrait jamais toucher'
            );
        }
    }

    public function testHasAttackHitReturnsBoolean(): void
    {
        $result = FightCalculator::hasAttackHit(50);

        $this->assertIsBool($result);
    }

    public function testHasAttackHitWithMidRangeProducesBothOutcomes(): void
    {
        // Avec 50% de chance, sur 200 essais on devrait avoir les deux resultats
        $hits = 0;
        $misses = 0;
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            if (FightCalculator::hasAttackHit(50)) {
                $hits++;
            } else {
                $misses++;
            }
        }

        $this->assertGreaterThan(0, $hits, 'Avec 50% de chances, il devrait y avoir au moins un hit');
        $this->assertGreaterThan(0, $misses, 'Avec 50% de chances, il devrait y avoir au moins un miss');
    }

    public function testHasAttackHitWith1PercentCanHit(): void
    {
        // random_int(0,99) < 1 => seulement quand random_int retourne 0
        // Sur 1000 essais, on devrait avoir au moins un hit
        $hasHit = false;
        for ($i = 0; $i < 1000; $i++) {
            if (FightCalculator::hasAttackHit(1)) {
                $hasHit = true;
                break;
            }
        }

        $this->assertTrue($hasHit, 'Avec 1% de chances, un hit devrait etre possible');
    }

    public function testHasAttackHitWith99PercentCanMiss(): void
    {
        // random_int(0,99) < 99 => miss seulement quand random_int retourne 99
        $hasMissed = false;
        for ($i = 0; $i < 1000; $i++) {
            if (!FightCalculator::hasAttackHit(99)) {
                $hasMissed = true;
                break;
            }
        }

        $this->assertTrue($hasMissed, 'Avec 99% de chances, un miss devrait etre possible');
    }
}
