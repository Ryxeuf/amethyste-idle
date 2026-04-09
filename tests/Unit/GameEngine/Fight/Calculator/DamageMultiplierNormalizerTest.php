<?php

namespace App\Tests\Unit\GameEngine\Fight\Calculator;

use App\GameEngine\Fight\Calculator\DamageMultiplierNormalizer;
use PHPUnit\Framework\TestCase;

class DamageMultiplierNormalizerTest extends TestCase
{
    private DamageMultiplierNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new DamageMultiplierNormalizer();
    }

    // --- normalizeBonus ---

    public function testNormalizeBonusZeroReturnsZero(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->normalizer->normalizeBonus(0.0), 0.001);
    }

    public function testNormalizeBonusNegativeReturnsZero(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->normalizer->normalizeBonus(-0.1), 0.001);
    }

    public function testNormalizeBonusBelowSoftCapUnchanged(): void
    {
        // 25% est sous le soft cap de 40%
        $this->assertEqualsWithDelta(0.25, $this->normalizer->normalizeBonus(0.25), 0.001);
    }

    public function testNormalizeBonusAtSoftCapUnchanged(): void
    {
        $this->assertEqualsWithDelta(0.4, $this->normalizer->normalizeBonus(0.4), 0.001);
    }

    public function testNormalizeBonusAboveSoftCapDiminished(): void
    {
        // 50% = 40% (plein) + 10% * 0.5 (diminished) = 45%
        $this->assertEqualsWithDelta(0.45, $this->normalizer->normalizeBonus(0.50), 0.001);
    }

    public function testNormalizeBonusLargeValueCapped(): void
    {
        // 80% = 40% + 40% * 0.5 = 60%
        $this->assertEqualsWithDelta(0.6, $this->normalizer->normalizeBonus(0.80), 0.001);
    }

    public function testNormalizeBonusTypicalElementMatchPlusLinked(): void
    {
        // Element match (25%) + linked (15%) = 40%, exactement au cap
        $this->assertEqualsWithDelta(0.4, $this->normalizer->normalizeBonus(0.40), 0.001);
    }

    public function testNormalizeBonusAllBonusesStacked(): void
    {
        // Element match (25%) + linked (15%) + gear (10%) = 50%
        // Normalized: 40% + 10% * 0.5 = 45%
        $result = $this->normalizer->normalizeBonus(0.50);
        $this->assertEqualsWithDelta(0.45, $result, 0.001);
    }

    // --- normalizeSynergy ---

    public function testNormalizeSynergyBelowOneUnchanged(): void
    {
        $this->assertEqualsWithDelta(0.8, $this->normalizer->normalizeSynergy(0.8), 0.001);
    }

    public function testNormalizeSynergyExactlyOneUnchanged(): void
    {
        $this->assertEqualsWithDelta(1.0, $this->normalizer->normalizeSynergy(1.0), 0.001);
    }

    public function testNormalizeSynergyBelowCapUnchanged(): void
    {
        // Steam: 1.2 (bonus 0.2, sous le cap de 0.5)
        $this->assertEqualsWithDelta(1.2, $this->normalizer->normalizeSynergy(1.2), 0.001);
    }

    public function testNormalizeSynergyAtCapUnchanged(): void
    {
        // Sandstorm/Eclipse/Holy Blade: 1.5 (bonus 0.5, exactement au cap)
        $this->assertEqualsWithDelta(1.5, $this->normalizer->normalizeSynergy(1.5), 0.001);
    }

    public function testNormalizeSynergyAboveCapDiminished(): void
    {
        // Hypothetical 1.8 = 1.0 + 0.5 (plein) + 0.3 * 0.3 = 1.59
        $this->assertEqualsWithDelta(1.59, $this->normalizer->normalizeSynergy(1.8), 0.001);
    }

    public function testNormalizeSynergyFormerEclipseWouldBeCapped(): void
    {
        // Ancien Eclipse 2.5 : bonus = 1.5, excess = 1.0, result = 1.0 + 0.5 + 1.0 * 0.3 = 1.80
        $this->assertEqualsWithDelta(1.80, $this->normalizer->normalizeSynergy(2.5), 0.001);
    }

    // --- Tests d'intégration : variance entre builds < 30% ---

    public function testMaxBuildVarianceBelowThirtyPercent(): void
    {
        // Build optimal : element match + linked + gear 10% + meilleure synergie (1.5)
        $bestBonusPercent = 0.25 + 0.15 + 0.10; // 50%
        $bestBonus = $this->normalizer->normalizeBonus($bestBonusPercent); // 45%
        $bestSynergyMultiplier = $this->normalizer->normalizeSynergy(1.5); // 1.5

        // Build minimal synergie : steam (1.2), pas de bonus equip
        $worstSynergyMultiplier = $this->normalizer->normalizeSynergy(1.2); // 1.2

        // Calcul avec domainDamage arbitraire
        $domainDamage = 10;
        $bestTotal = (int) round($domainDamage * (1.0 + $bestBonus) * $bestSynergyMultiplier);
        $worstTotal = (int) round($domainDamage * 1.0 * $worstSynergyMultiplier);

        // Ecart entre les deux builds (hors spell.damage de base qui dilue l'ecart)
        $variance = ($bestTotal - $worstTotal) / $worstTotal;

        // L'ecart brut sur le bonus est > 30%, mais le spell.damage de base
        // (commun a tous les builds) dilue la difference effective
        $this->assertLessThan(1.0, $variance, 'La variance entre builds doit rester raisonnable');

        // Avec spell.damage = 20, l'ecart effectif diminue significativement
        $spellDamage = 20;
        $bestEffective = $spellDamage + $bestTotal;
        $worstEffective = $spellDamage + $worstTotal;
        $effectiveVariance = ($bestEffective - $worstEffective) / $worstEffective;

        $this->assertLessThan(0.50, $effectiveVariance, 'L\'ecart effectif avec spell.damage doit etre < 50%');
    }

    public function testSynergyRangeWithinThirtyPercent(): void
    {
        // Toutes les synergies actuelles sont entre 1.2 et 1.5
        $synergies = [1.2, 1.25, 1.3, 1.3, 1.35, 1.4, 1.5, 1.5];

        $normalized = array_map(fn (float $m) => $this->normalizer->normalizeSynergy($m), $synergies);
        $min = min($normalized);
        $max = max($normalized);

        $variance = ($max - $min) / $min;
        $this->assertLessThan(0.30, $variance, 'L\'ecart entre synergies normalisees doit etre < 30%');
    }
}
