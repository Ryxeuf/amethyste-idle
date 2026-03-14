<?php

namespace App\Tests\Unit\GameEngine\Craft;

use App\GameEngine\Craft\CraftQuality;
use PHPUnit\Framework\TestCase;

class CraftQualityTest extends TestCase
{
    public function testStatMultipliers(): void
    {
        $this->assertSame(1.0, CraftQuality::NORMAL->getStatMultiplier());
        $this->assertSame(1.15, CraftQuality::SUPERIOR->getStatMultiplier());
        $this->assertSame(1.3, CraftQuality::EXCEPTIONAL->getStatMultiplier());
        $this->assertSame(1.5, CraftQuality::MASTERPIECE->getStatMultiplier());
    }

    public function testLabels(): void
    {
        $this->assertSame('Normal', CraftQuality::NORMAL->getLabel());
        $this->assertSame('Supérieur', CraftQuality::SUPERIOR->getLabel());
        $this->assertSame('Exceptionnel', CraftQuality::EXCEPTIONAL->getLabel());
        $this->assertSame("Chef-d'œuvre", CraftQuality::MASTERPIECE->getLabel());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('normal', CraftQuality::NORMAL->value);
        $this->assertSame('superior', CraftQuality::SUPERIOR->value);
        $this->assertSame('exceptional', CraftQuality::EXCEPTIONAL->value);
        $this->assertSame('masterpiece', CraftQuality::MASTERPIECE->value);
    }

    public function testRollQualityLowSkillAlwaysNormal(): void
    {
        // Avec skill 0, aucune chance d'amélioration
        // masterpieceChance = max(0, 0-80) = 0
        // exceptionalChance = max(0, 0-40) = 0
        // superiorChance = max(0, 0-10) = 0
        // Donc tout roll > 0 donne NORMAL
        for ($i = 0; $i < 20; $i++) {
            $quality = CraftQuality::rollQuality(0);
            $this->assertSame(CraftQuality::NORMAL, $quality);
        }
    }

    public function testRollQualityReturnsValidEnum(): void
    {
        $validQualities = CraftQuality::cases();

        for ($i = 0; $i < 50; $i++) {
            $quality = CraftQuality::rollQuality(random_int(0, 100));
            $this->assertContains($quality, $validQualities);
        }
    }
}
