<?php

namespace App\Tests\Unit\GameEngine\Fight\Calculator;

use App\Entity\Game\Spell;
use App\GameEngine\Fight\Calculator\HitChanceCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HitChanceCalculatorTest extends TestCase
{
    private HitChanceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new HitChanceCalculator();
    }

    private function createSpell(int $hit = 90, int $level = 1): Spell&MockObject
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getHit')->willReturn($hit);
        $spell->method('getLevel')->willReturn($level);

        return $spell;
    }

    public function testComputeHitChanceSameLevel(): void
    {
        $spell = $this->createSpell(hit: 90, level: 3);

        $result = $this->calculator->computeHitChance($spell, 3);

        // 90 + (3 - 3) * 2 = 90
        $this->assertSame(90, $result);
    }

    public function testComputeHitChanceHigherSpellLevel(): void
    {
        $spell = $this->createSpell(hit: 90, level: 5);

        $result = $this->calculator->computeHitChance($spell, 3);

        // 90 + (5 - 3) * 2 = 94
        $this->assertSame(94, $result);
    }

    public function testComputeHitChanceLowerSpellLevel(): void
    {
        $spell = $this->createSpell(hit: 90, level: 1);

        $result = $this->calculator->computeHitChance($spell, 5);

        // 90 + (1 - 5) * 2 = 82
        $this->assertSame(82, $result);
    }

    public function testComputeHitChanceCappedAt100(): void
    {
        $spell = $this->createSpell(hit: 95, level: 10);

        $result = $this->calculator->computeHitChance($spell, 1);

        // 95 + (10 - 1) * 2 = 113, mais cap a 100
        $this->assertSame(100, $result);
    }

    public function testComputeHitChanceMinimum5(): void
    {
        $spell = $this->createSpell(hit: 10, level: 1);

        $result = $this->calculator->computeHitChance($spell, 50);

        // 10 + (1 - 50) * 2 = -88, mais min a 5
        $this->assertSame(5, $result);
    }

    public function testHasHitAlwaysWithMaxChance(): void
    {
        $spell = $this->createSpell(hit: 100, level: 5);

        // Avec 100% de chances, doit toujours toucher
        $result = $this->calculator->hasHit($spell, 1);

        $this->assertTrue($result);
    }

    public function testHasHitWithZeroBaseHit(): void
    {
        $spell = $this->createSpell(hit: 0, level: 1);

        // 0 + (1 - 1) * 2 = 0 => min 5%, pas forcement faux mais tres improbable
        $hitChance = $this->calculator->computeHitChance($spell, 1);
        $this->assertSame(5, $hitChance);
    }

    public function testComputeHitChanceUsesDefaultTargetLevel(): void
    {
        $spell = $this->createSpell(hit: 90, level: 3);

        // Appel sans targetLevel : defaut = 1, donc 90 + (3 - 1) * 2 = 94
        $result = $this->calculator->computeHitChance($spell);

        $this->assertSame(94, $result);
    }

    public function testHasHitUsesDefaultTargetLevel(): void
    {
        $spell = $this->createSpell(hit: 100, level: 5);

        // Appel sans targetLevel : defaut = 1, 100 + (5 - 1)*2 = 108 cap 100 => toujours true
        $this->assertTrue($this->calculator->hasHit($spell));
    }

    public function testComputeHitChanceExactMinimumBoundary(): void
    {
        // Resultat exactement egal a 5 : doit retourner 5
        $spell = $this->createSpell(hit: 5, level: 1);

        $result = $this->calculator->computeHitChance($spell, 1);

        // 5 + (1 - 1) * 2 = 5
        $this->assertSame(5, $result);
    }

    public function testComputeHitChanceJustAboveMinimum(): void
    {
        // Resultat a 6 : doit rester 6 (pas clamp a 5)
        $spell = $this->createSpell(hit: 6, level: 1);

        $result = $this->calculator->computeHitChance($spell, 1);

        $this->assertSame(6, $result);
    }
}
