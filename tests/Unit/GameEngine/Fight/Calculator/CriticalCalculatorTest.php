<?php

namespace App\Tests\Unit\GameEngine\Fight\Calculator;

use App\Entity\Game\Spell;
use App\GameEngine\Fight\Calculator\CriticalCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CriticalCalculatorTest extends TestCase
{
    private CriticalCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CriticalCalculator();
    }

    private function createSpell(int $critical = 5): Spell&MockObject
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getCritical')->willReturn($critical);

        return $spell;
    }

    public function testComputeCriticalChance(): void
    {
        $spell = $this->createSpell(critical: 10);

        $result = $this->calculator->computeCriticalChance($spell, 5);

        $this->assertSame(15, $result);
    }

    public function testComputeCriticalChanceCappedAt100(): void
    {
        $spell = $this->createSpell(critical: 80);

        $result = $this->calculator->computeCriticalChance($spell, 30);

        $this->assertSame(100, $result);
    }

    public function testComputeCriticalChanceMinimum0(): void
    {
        $spell = $this->createSpell(critical: 0);

        $result = $this->calculator->computeCriticalChance($spell, 0);

        $this->assertSame(0, $result);
    }

    public function testComputeCriticalChanceNegativeClampsTo0(): void
    {
        $spell = $this->createSpell(critical: 0);

        // 0 + (-10) = -10, clampe a 0
        $result = $this->calculator->computeCriticalChance($spell, -10);

        $this->assertSame(0, $result);
    }

    public function testComputeCriticalChanceUsesDefaultDomainCritical(): void
    {
        $spell = $this->createSpell(critical: 25);

        // Appel sans domainCritical : defaut = 0, donc 25 + 0 = 25
        $result = $this->calculator->computeCriticalChance($spell);

        $this->assertSame(25, $result);
    }

    public function testIsCriticalNeverWith0Chance(): void
    {
        $spell = $this->createSpell(critical: 0);

        // random_int(0,99) < 0 => toujours faux
        $result = $this->calculator->isCritical($spell, 0);

        $this->assertFalse($result);
    }

    public function testIsCriticalAlwaysWith100Chance(): void
    {
        $spell = $this->createSpell(critical: 100);

        // random_int(0,99) < 100 => toujours vrai
        $result = $this->calculator->isCritical($spell, 0);

        $this->assertTrue($result);
    }

    public function testIsCriticalUsesDefaultDomainCritical(): void
    {
        $spell = $this->createSpell(critical: 100);

        // Appel sans domainCritical : defaut = 0, donc 100% crit
        $this->assertTrue($this->calculator->isCritical($spell));
    }

    public function testApplyCriticalModifier(): void
    {
        // 100 * 1.5 = 150
        $result = $this->calculator->applyCriticalModifier(100);

        $this->assertSame(150, $result);
    }

    public function testApplyCriticalModifierSmallValue(): void
    {
        // 3 * 1.5 = 4.5, arrondi a 5 (pas floor=4, pas ceil=5 par hasard ici)
        $result = $this->calculator->applyCriticalModifier(3);

        $this->assertSame(5, $result);
    }

    public function testApplyCriticalModifierOddValue(): void
    {
        // 7 * 1.5 = 10.5, round = 11 (floor serait 10, ceil serait 11)
        $result = $this->calculator->applyCriticalModifier(7);

        $this->assertSame(11, $result);
    }

    public function testApplyCriticalModifierZero(): void
    {
        $result = $this->calculator->applyCriticalModifier(0);

        $this->assertSame(0, $result);
    }
}
