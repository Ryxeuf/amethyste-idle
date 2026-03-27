<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\Biome\CaveBiome;
use App\GameEngine\Terrain\TilesetRegistry;
use PHPUnit\Framework\TestCase;

class CaveBiomeTest extends TestCase
{
    private CaveBiome $biome;

    protected function setUp(): void
    {
        $this->biome = new CaveBiome();
    }

    public function testSlug(): void
    {
        $this->assertSame('cave', $this->biome->getSlug());
    }

    public function testLabel(): void
    {
        $this->assertSame('Caverne', $this->biome->getLabel());
    }

    public function testWaterTerrainIsSewerWater(): void
    {
        $this->assertSame('sewer_water', $this->biome->getWaterTerrainSlug());
    }

    public function testTreeGidsInDarkDimTileset(): void
    {
        foreach ($this->biome->getTreeGids() as $gid) {
            $this->assertGreaterThanOrEqual(TilesetRegistry::FIRST_GID_DARKDIM_B, $gid);
        }
    }

    public function testDecorationGidsInDarkDimTileset(): void
    {
        $decoGids = $this->biome->getDecorationGids();
        $this->assertNotEmpty($decoGids);
        foreach ($decoGids as $gid) {
            $this->assertGreaterThanOrEqual(TilesetRegistry::FIRST_GID_DARKDIM_B, $gid);
        }
    }

    public function testNoWeather(): void
    {
        $this->assertNull($this->biome->getWeather());
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
