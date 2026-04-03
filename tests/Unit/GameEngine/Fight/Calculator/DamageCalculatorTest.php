<?php

namespace App\Tests\Unit\GameEngine\Fight\Calculator;

use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\Enum\Element;
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

    private function createSpell(int $damage = 0, int $heal = 0, string $valueType = 'fixed', Element $element = Element::None, bool $nullDamage = true, bool $nullHeal = true): Spell&MockObject
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getDamage')->willReturn($damage === 0 && $nullDamage ? null : $damage);
        $spell->method('getHeal')->willReturn($heal === 0 && $nullHeal ? null : $heal);
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
        $spell = $this->createSpell(damage: 40, element: Element::Fire);
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
        $spell = $this->createSpell(damage: 40, element: Element::Water);
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
        $spell = $this->createSpell(damage: 40, element: Element::Fire);
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

    public function testBerserkModifierWithRoundingValue(): void
    {
        // 7 * 1.5 = 10.5, round = 11 (floor serait 10)
        $this->assertSame(11, $this->calculator->applyBerserkModifier(7));
    }

    public function testBurnReductionWithRoundingValue(): void
    {
        // 7 * 0.75 = 5.25, round = 5 (ceil serait 6)
        $this->assertSame(5, $this->calculator->applyBurnReduction(7));
    }

    public function testBurnReductionRoundsUp(): void
    {
        // 10 * 0.75 = 7.5, round = 8 (floor serait 7)
        $this->assertSame(8, $this->calculator->applyBurnReduction(10));
    }

    public function testComputeBaseDamagePercentWithRounding(): void
    {
        // 33% de 100 = 33.0, + 0 domain = 33
        $spell = $this->createSpell(damage: 33, valueType: 'percent');
        $target = $this->createTarget(maxLife: 100);

        $result = $this->calculator->computeBaseDamage($spell, 0, $target);

        $this->assertSame(33, $result);
    }

    public function testComputeBaseDamagePercentRoundsCorrectly(): void
    {
        // 7% de 150 = 10.5, round = 11 (floor serait 10), + 0 = 11
        $spell = $this->createSpell(damage: 7, valueType: 'percent');
        $target = $this->createTarget(maxLife: 150);

        $result = $this->calculator->computeBaseDamage($spell, 0, $target);

        $this->assertSame(11, $result);
    }

    public function testComputeBaseHealPercentRoundsCorrectly(): void
    {
        // 7% de 150 = 10.5, round = 11, + 0 = 11
        $spell = $this->createSpell(heal: 7, valueType: 'percent');
        $target = $this->createTarget(maxLife: 150);

        $result = $this->calculator->computeBaseHeal($spell, 0, $target);

        $this->assertSame(11, $result);
    }

    public function testComputeBaseDamageWithEffectiveMaxLife(): void
    {
        // 10% of effectiveMaxLife 300 = 30, + 5 domain = 35
        $spell = $this->createSpell(damage: 10, valueType: 'percent');
        $target = $this->createTarget(maxLife: 200);

        $result = $this->calculator->computeBaseDamage($spell, 5, $target, 300);

        $this->assertSame(35, $result);
    }

    public function testComputeBaseHealWithEffectiveMaxLife(): void
    {
        // 10% of effectiveMaxLife 300 = 30, + 2 domain = 32
        $spell = $this->createSpell(heal: 10, valueType: 'percent');
        $target = $this->createTarget(maxLife: 200);

        $result = $this->calculator->computeBaseHeal($spell, 2, $target, 300);

        $this->assertSame(32, $result);
    }

    public function testApplyWeatherModifierNoChange(): void
    {
        // modifier = 1.0 => pas de changement
        $this->assertSame(50, $this->calculator->applyWeatherModifier(50, 1.0));
    }

    public function testApplyWeatherModifierZeroDamage(): void
    {
        // damage = 0 => pas de changement
        $this->assertSame(0, $this->calculator->applyWeatherModifier(0, 1.5));
    }

    public function testApplyWeatherModifierNegativeDamage(): void
    {
        // damage <= 0 => pas de changement
        $this->assertSame(-5, $this->calculator->applyWeatherModifier(-5, 1.5));
    }

    public function testApplyWeatherModifierAmplifies(): void
    {
        // 50 * 1.5 = 75
        $this->assertSame(75, $this->calculator->applyWeatherModifier(50, 1.5));
    }

    public function testApplyWeatherModifierReduces(): void
    {
        // 50 * 0.5 = 25
        $this->assertSame(25, $this->calculator->applyWeatherModifier(50, 0.5));
    }

    public function testApplyWeatherModifierRoundsCorrectly(): void
    {
        // 7 * 1.3 = 9.1, round = 9 (ceil serait 10)
        $this->assertSame(9, $this->calculator->applyWeatherModifier(7, 1.3));
    }

    public function testApplyWeatherModifierRoundsUp(): void
    {
        // 10 * 1.15 = 11.5, round = 12 (floor serait 11)
        $this->assertSame(12, $this->calculator->applyWeatherModifier(10, 1.15));
    }

    public function testApplyElementalResistanceZeroDamage(): void
    {
        $spell = $this->createSpell(damage: 40, element: Element::Fire);
        $mob = $this->createTarget();
        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->willReturn(0.5);
        $mob->method('getMonster')->willReturn($monster);

        // damage = 0 => court-circuit, pas de resistance appliquee
        $result = $this->calculator->applyElementalResistance(0, $spell, $mob);

        $this->assertSame(0, $result['damage']);
        $this->assertFalse($result['resisted']);
        $this->assertFalse($result['weak']);
    }

    public function testApplyElementalResistanceRoundsCorrectly(): void
    {
        // 7 * (1.0 - 0.3) = 7 * 0.7 = 4.9, round = 5 (floor serait 4)
        $spell = $this->createSpell(damage: 40, element: Element::Fire);
        $mob = $this->createTarget();
        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->with('fire')->willReturn(0.3);
        $mob->method('getMonster')->willReturn($monster);

        $result = $this->calculator->applyElementalResistance(7, $spell, $mob);

        $this->assertSame(5, $result['damage']);
        $this->assertTrue($result['resisted']);
    }

    public function testShieldAbsorptionNegativeDamage(): void
    {
        $result = $this->calculator->applyShieldAbsorption(-5, 20);

        $this->assertSame(-5, $result['damage']);
        $this->assertSame(0, $result['absorbed']);
    }

    public function testShieldAbsorptionExactEqual(): void
    {
        $result = $this->calculator->applyShieldAbsorption(20, 20);

        $this->assertSame(0, $result['damage']);
        $this->assertSame(20, $result['absorbed']);
    }

    public function testComputeBaseDamageReturnsZeroWhenDamageIsExactlyZero(): void
    {
        // getDamage() retourne 0 (pas null) — doit aussi retourner 0
        $spell = $this->createSpell(damage: 0, nullDamage: false);
        $target = $this->createTarget();

        $result = $this->calculator->computeBaseDamage($spell, 5, $target);

        $this->assertSame(0, $result);
    }

    public function testComputeBaseHealReturnsZeroWhenHealIsExactlyZero(): void
    {
        // getHeal() retourne 0 (pas null) — doit aussi retourner 0
        $spell = $this->createSpell(heal: 0, nullHeal: false);
        $target = $this->createTarget();

        $result = $this->calculator->computeBaseHeal($spell, 5, $target);

        $this->assertSame(0, $result);
    }

    public function testApplyWeatherModifierClampsToZero(): void
    {
        // modifier tres bas pourrait donner negatif, mais max(0, ...)
        $this->assertSame(0, $this->calculator->applyWeatherModifier(1, 0.0));
    }

    public function testApplyElementalResistanceFullResistanceClampsToZero(): void
    {
        // resistance = 1.0 => damage * 0 = 0
        $spell = $this->createSpell(damage: 40, element: Element::Fire);
        $mob = $this->createTarget();
        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->with('fire')->willReturn(1.0);
        $mob->method('getMonster')->willReturn($monster);

        $result = $this->calculator->applyElementalResistance(40, $spell, $mob);

        $this->assertSame(0, $result['damage']);
        $this->assertTrue($result['resisted']);
    }

    public function testApplyElementalResistanceOverResistanceClampsToZero(): void
    {
        // resistance > 1.0 => damage negatif clamp a 0
        $spell = $this->createSpell(damage: 40, element: Element::Fire);
        $mob = $this->createTarget();
        $monster = $this->createMock(Monster::class);
        $monster->method('getElementalResistance')->with('fire')->willReturn(1.5);
        $mob->method('getMonster')->willReturn($monster);

        $result = $this->calculator->applyElementalResistance(40, $spell, $mob);

        $this->assertSame(0, $result['damage']);
        $this->assertTrue($result['resisted']);
    }
}
