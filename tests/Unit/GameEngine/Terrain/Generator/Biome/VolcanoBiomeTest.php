<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\Biome\VolcanoBiome;
use App\GameEngine\Terrain\TilesetRegistry;
use PHPUnit\Framework\TestCase;

class VolcanoBiomeTest extends TestCase
{
    private VolcanoBiome $biome;

    protected function setUp(): void
    {
        $this->biome = new VolcanoBiome();
    }

    public function testSlug(): void
    {
        $this->assertSame('volcano', $this->biome->getSlug());
    }

    public function testLabel(): void
    {
        $this->assertSame('Volcan', $this->biome->getLabel());
    }

    public function testWaterTerrainIsLava(): void
    {
        $this->assertSame('lava', $this->biome->getWaterTerrainSlug());
    }

    public function testTreeGidsInAshlandsTileset(): void
    {
        foreach ($this->biome->getTreeGids() as $gid) {
            $this->assertGreaterThanOrEqual(TilesetRegistry::FIRST_GID_ASHLANDS_B, $gid);
        }
    }

    public function testDecorationGidsInAshlandsTileset(): void
    {
        $decoGids = $this->biome->getDecorationGids();
        $this->assertNotEmpty($decoGids);
        foreach ($decoGids as $gid) {
            $this->assertGreaterThanOrEqual(TilesetRegistry::FIRST_GID_ASHLANDS_B, $gid);
        }
    }

    public function testWeather(): void
    {
        $this->assertSame('ash', $this->biome->getWeather());
    }

    public function testPerlinParams(): void
    {
        $this->assertGreaterThan(0.0, $this->biome->getPerlinScale());
        $this->assertGreaterThan(0, $this->biome->getPerlinOctaves());
    }

    public function testAvailableMobsNotEmpty(): void
    {
        $this->assertNotEmpty($this->biome->getAvailableMobs());
    }
}
