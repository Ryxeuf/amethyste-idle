<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\Biome\DesertBiome;
use App\GameEngine\Terrain\TilesetRegistry;
use PHPUnit\Framework\TestCase;

class DesertBiomeTest extends TestCase
{
    private DesertBiome $biome;

    protected function setUp(): void
    {
        $this->biome = new DesertBiome();
    }

    public function testSlug(): void
    {
        $this->assertSame('desert', $this->biome->getSlug());
    }

    public function testLabel(): void
    {
        $this->assertSame('Désert', $this->biome->getLabel());
    }

    public function testBackgroundGidsAreValid(): void
    {
        $gids = $this->biome->getBackgroundGids();
        $this->assertNotEmpty($gids);
        foreach ($gids as $gid) {
            $this->assertGreaterThan(0, $gid);
        }
    }

    public function testThresholdsOrdered(): void
    {
        $this->assertLessThan($this->biome->getSandThreshold(), $this->biome->getWaterThreshold());
        $this->assertLessThanOrEqual($this->biome->getGrassThreshold(), $this->biome->getSandThreshold());
    }

    public function testTreeDensityVeryLow(): void
    {
        $this->assertLessThanOrEqual(0.05, $this->biome->getTreeDensity());
    }

    public function testTreeGidsInBeachTileset(): void
    {
        foreach ($this->biome->getTreeGids() as $gid) {
            $this->assertGreaterThanOrEqual(TilesetRegistry::FIRST_GID_BEACH_B, $gid);
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

    public function testWeather(): void
    {
        $this->assertSame('heat', $this->biome->getWeather());
    }

    public function testWaterTerrainSlug(): void
    {
        $this->assertSame('sand_water', $this->biome->getWaterTerrainSlug());
    }

    public function testPerlinParams(): void
    {
        $this->assertGreaterThan(0.0, $this->biome->getPerlinScale());
        $this->assertGreaterThan(0, $this->biome->getPerlinOctaves());
    }

    public function testDecorationGidsNotEmpty(): void
    {
        $this->assertNotEmpty($this->biome->getDecorationGids());
    }
}
