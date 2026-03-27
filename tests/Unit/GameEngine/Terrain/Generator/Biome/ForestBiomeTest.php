<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\Biome\ForestBiome;
use App\GameEngine\Terrain\TilesetRegistry;
use PHPUnit\Framework\TestCase;

class ForestBiomeTest extends TestCase
{
    private ForestBiome $biome;

    protected function setUp(): void
    {
        $this->biome = new ForestBiome();
    }

    public function testSlug(): void
    {
        $this->assertSame('forest', $this->biome->getSlug());
    }

    public function testLabel(): void
    {
        $this->assertSame('Forêt', $this->biome->getLabel());
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

    public function testWaterThresholdLowerThanPlains(): void
    {
        $this->assertSame(0.20, $this->biome->getWaterThreshold());
        $this->assertLessThan($this->biome->getSandThreshold(), $this->biome->getWaterThreshold());
    }

    public function testTreeDensityHigherThanPlains(): void
    {
        $this->assertGreaterThanOrEqual(0.30, $this->biome->getTreeDensity());
        $this->assertLessThanOrEqual(0.50, $this->biome->getTreeDensity());
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

    public function testWaterTerrainSlug(): void
    {
        $this->assertSame('water', $this->biome->getWaterTerrainSlug());
    }

    public function testSandTerrainSlug(): void
    {
        $this->assertSame('dark_grass', $this->biome->getSandTerrainSlug());
    }
}
