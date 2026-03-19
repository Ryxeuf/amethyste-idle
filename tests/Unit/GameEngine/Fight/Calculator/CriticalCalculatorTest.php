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

    public function testApplyCriticalModifier(): void
    {
        // 100 * 1.5 = 150
        $result = $this->calculator->applyCriticalModifier(100);

        $this->assertSame(150, $result);
    }

    public function testApplyCriticalModifierSmallValue(): void
    {
        // 3 * 1.5 = 4.5, arrondi a 5
        $result = $this->calculator->applyCriticalModifier(3);

        $this->assertSame(5, $result);
    }

    public function testApplyCriticalModifierZero(): void
    {
        $result = $this->calculator->applyCriticalModifier(0);

        $this->assertSame(0, $result);
    }
}
