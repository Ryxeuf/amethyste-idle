<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\Biome\SwampBiome;
use App\GameEngine\Terrain\TilesetRegistry;
use PHPUnit\Framework\TestCase;

class SwampBiomeTest extends TestCase
{
    private SwampBiome $biome;

    protected function setUp(): void
    {
        $this->biome = new SwampBiome();
    }

    public function testSlug(): void
    {
        $this->assertSame('swamp', $this->biome->getSlug());
    }

    public function testLabel(): void
    {
        $this->assertSame('Marécage', $this->biome->getLabel());
    }

    public function testBackgroundGidsAreValid(): void
    {
        $gids = $this->biome->getBackgroundGids();
        $this->assertNotEmpty($gids);
        foreach ($gids as $gid) {
            $this->assertGreaterThan(0, $gid);
            $this->assertLessThan(TilesetRegistry::FIRST_GID_FOREST, $gid);
        }
    }

    public function testWaterThresholdHigherThanPlains(): void
    {
        $this->assertSame(0.35, $this->biome->getWaterThreshold());
    }

    public function testTreeDensityBetween15And25Percent(): void
    {
        $density = $this->biome->getTreeDensity();
        $this->assertGreaterThanOrEqual(0.15, $density);
        $this->assertLessThanOrEqual(0.25, $density);
    }

    public function testTreeGidsInForestTileset(): void
    {
        foreach ($this->biome->getTreeGids() as $gid) {
            $this->assertGreaterThanOrEqual(TilesetRegistry::FIRST_GID_FOREST, $gid);
        }
    }

    public function testAvailableMobsNotEmpty(): void
    {
        $mobs = $this->biome->getAvailableMobs();
        $this->assertNotEmpty($mobs);
        foreach ($mobs as $mob) {
            $this->assertArrayHasKey('slug', $mob);
            $this->assertArrayHasKey('minDifficulty', $mob);
            $this->assertArrayHasKey('maxDifficulty', $mob);
        }
    }

    public function testHarvestItemsNotEmpty(): void
    {
        $this->assertNotEmpty($this->biome->getHarvestItems());
    }

    public function testWeatherIsFog(): void
    {
        $this->assertSame('fog', $this->biome->getWeather());
    }

    public function testWaterTerrainSlugIsSewerWater(): void
    {
        $this->assertSame('sewer_water', $this->biome->getWaterTerrainSlug());
    }

    public function testSandTerrainSlugIsEarth(): void
    {
        $this->assertSame('earth', $this->biome->getSandTerrainSlug());
    }
}
