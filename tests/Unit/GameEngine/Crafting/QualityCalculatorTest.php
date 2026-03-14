<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\GameEngine\Crafting\QualityCalculator;
use PHPUnit\Framework\TestCase;

class QualityCalculatorTest extends TestCase
{
    private QualityCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new QualityCalculator();
    }

    public function testQualityTiersOrder(): void
    {
        $expected = ['normal', 'uncommon', 'rare', 'epic', 'legendary'];
        $this->assertSame($expected, QualityCalculator::QUALITY_TIERS);
    }

    public function testGetQualityLabel(): void
    {
        $this->assertSame('Normal', QualityCalculator::getQualityLabel('normal'));
        $this->assertSame('Peu commun', QualityCalculator::getQualityLabel('uncommon'));
        $this->assertSame('Rare', QualityCalculator::getQualityLabel('rare'));
        $this->assertSame('Epique', QualityCalculator::getQualityLabel('epic'));
        $this->assertSame('Legendaire', QualityCalculator::getQualityLabel('legendary'));
        $this->assertSame('Inconnu', QualityCalculator::getQualityLabel('invalid'));
    }

    public function testGetQualityColor(): void
    {
        $this->assertSame('text-gray-400', QualityCalculator::getQualityColor('normal'));
        $this->assertSame('text-green-400', QualityCalculator::getQualityColor('uncommon'));
        $this->assertSame('text-blue-400', QualityCalculator::getQualityColor('rare'));
        $this->assertSame('text-purple-400', QualityCalculator::getQualityColor('epic'));
        $this->assertSame('text-orange-400', QualityCalculator::getQualityColor('legendary'));
        $this->assertSame('text-gray-400', QualityCalculator::getQualityColor('unknown'));
    }

    public function testCalculateQualityReturnsValidTier(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $result = $this->calculator->calculateQuality('normal', random_int(1, 100));
            $this->assertContains($result, QualityCalculator::QUALITY_TIERS);
        }
    }

    public function testCalculateQualityWithInvalidBaseDefaultsToNormal(): void
    {
        $result = $this->calculator->calculateQuality('nonexistent', 0);
        // skillLevel 0 => upgradeChance = 0%, donc reste à l'index 0 = normal
        $this->assertSame('normal', $result);
    }

    public function testCalculateQualityNeverDowngrades(): void
    {
        // Même avec un skill de 0, la qualité ne devrait pas descendre en dessous de la base
        for ($i = 0; $i < 20; $i++) {
            $result = $this->calculator->calculateQuality('rare', 0);
            $index = array_search($result, QualityCalculator::QUALITY_TIERS, true);
            $this->assertGreaterThanOrEqual(
                array_search('rare', QualityCalculator::QUALITY_TIERS, true),
                $index
            );
        }
    }

    public function testCalculateQualityCannotExceedLegendary(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $result = $this->calculator->calculateQuality('legendary', 100);
            $this->assertSame('legendary', $result);
        }
    }
}
