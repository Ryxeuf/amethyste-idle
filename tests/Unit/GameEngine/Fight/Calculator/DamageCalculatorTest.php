<?php

namespace App\Tests\Unit\GameEngine\Fight\Calculator;

use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\GameEngine\Fight\Calculator\DamageCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DamageCalculatorTest extends TestCase
{
    private DamageCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new DamageCalculator();
    }

    private function createSpell(int $damage = 0, int $heal = 0, string $valueType = 'fixed', string $element = 'none'): Spell&MockObject
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getDamage')->willReturn($damage === 0 ? null : $damage);
        $spell->method('getHeal')->willReturn($heal === 0 ? null : $heal);
        $spell->method('getElement')->willReturn($element);
        $spell->method('isPercent')->willReturn($valueType === 'percent');

        return $spell;
    }

    private function createTarget(int $life = 100, int $maxLife = 200): Mob&MockObject
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('getLife')->willReturn($life);
        $mob->method('getMaxLife')->willReturn($maxLife);

        return $mob;
    }

    public function testComputeBaseDamageFixed(): void
    {
        $spell = $this->createSpell(damage: 10);
        $target = $this->createTarget();

        $result = $this->calculator->computeBaseDamage($spell, 5, $target);

        // 10 (spell) + 5 (domain) = 15
        $this->assertSame(15, $result);
    }

    public function testComputeBaseDamageReturnsZeroWhenNoDamage(): void
    {
        $spell = $this->createSpell(damage: 0);
        $target = $this->createTarget();

        $result = $this->calculator->computeBaseDamage($spell, 5, $target);

        $this->assertSame(0, $result);
    }

    public function testComputeBaseDamagePercent(): void
    {
        // 10% de 200 maxLife = 20, + 5 domain = 25
        $spell = $this->createSpell(damage: 10, valueType: 'percent');
        $target = $this->createTarget(maxLife: 200);

        $result = $this->calculator->computeBaseDamage($spell, 5, $target);

        $this->assertSame(25, $result);
    }

    public function testComputeBaseHealFixed(): void
    {
        $spell = $this->createSpell(heal: 8);
        $target = $this->createTarget();

        $result = $this->calculator->computeBaseHeal($spell, 3, $target);

        $this->assertSame(11, $result);
    }

    public function testComputeBaseHealPercent(): void
    {
        // 15% de 200 maxLife = 30, + 2 domain = 32
        $spell = $this->createSpell(heal: 15, valueType: 'percent');
        $target = $this->createTarget(maxLife: 200);

        $result = $this->calculator->computeBaseHeal($spell, 2, $target);

        $this->assertSame(32, $result);
    }

    public function testComputeBaseHealReturnsZeroWhenNoHeal(): void
    {
        $spell = $this->createSpell(heal: 0);
        $target = $this->createTarget();

        $result = $this->calculator->computeBaseHeal($spell, 5, $target);

        $this->assertSame(0, $result);
    }

    public function testApplyElementalResistanceReducesDamage(): void
    {
        $spell = $this->createSpell(damage: 40, element: 'fire');
        $mob = $this->createTarget();
        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->with('fire')->willReturn(0.5);
        $mob->method('getMonster')->willReturn($monster);

        $result = $this->calculator->applyElementalResistance(40, $spell, $mob);

        $this->assertSame(20, $result['damage']);
        $this->assertTrue($result['resisted']);
        $this->assertFalse($result['weak']);
    }

    public function testApplyElementalWeaknessIncreasesDamage(): void
    {
        $spell = $this->createSpell(damage: 40, element: 'water');
        $mob = $this->createTarget();
        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->with('water')->willReturn(-0.5);
        $mob->method('getMonster')->willReturn($monster);

        $result = $this->calculator->applyElementalResistance(40, $spell, $mob);

        $this->assertSame(60, $result['damage']);
        $this->assertFalse($result['resisted']);
        $this->assertTrue($result['weak']);
    }

    public function testApplyElementalResistanceNoEffect(): void
    {
        $spell = $this->createSpell(damage: 40, element: 'fire');
        $mob = $this->createTarget();
        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.0);
        $mob->method('getMonster')->willReturn($monster);

        $result = $this->calculator->applyElementalResistance(40, $spell, $mob);

        $this->assertSame(40, $result['damage']);
        $this->assertFalse($result['resisted']);
        $this->assertFalse($result['weak']);
    }

    public function testBerserkModifier(): void
    {
        $this->assertSame(30, $this->calculator->applyBerserkModifier(20));
    }

    public function testBurnReduction(): void
    {
        $this->assertSame(30, $this->calculator->applyBurnReduction(40));
    }

    public function testShieldAbsorptionPartial(): void
    {
        $result = $this->calculator->applyShieldAbsorption(50, 20);

        $this->assertSame(30, $result['damage']);
        $this->assertSame(20, $result['absorbed']);
    }

    public function testShieldAbsorptionFull(): void
    {
        $result = $this->calculator->applyShieldAbsorption(10, 50);

        $this->assertSame(0, $result['damage']);
        $this->assertSame(10, $result['absorbed']);
    }

    public function testShieldAbsorptionNoShield(): void
    {
        $result = $this->calculator->applyShieldAbsorption(50, 0);

        $this->assertSame(50, $result['damage']);
        $this->assertSame(0, $result['absorbed']);
    }
}
