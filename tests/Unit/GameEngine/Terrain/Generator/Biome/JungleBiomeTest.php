<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\Biome\JungleBiome;
use App\GameEngine\Terrain\TilesetRegistry;
use PHPUnit\Framework\TestCase;

class JungleBiomeTest extends TestCase
{
    private JungleBiome $biome;

    protected function setUp(): void
    {
        $this->biome = new JungleBiome();
    }

    public function testSlug(): void
    {
        $this->assertSame('jungle', $this->biome->getSlug());
    }

    public function testLabel(): void
    {
        $this->assertSame('Jungle', $this->biome->getLabel());
    }

    public function testHighTreeDensity(): void
    {
        $this->assertGreaterThanOrEqual(0.40, $this->biome->getTreeDensity());
    }

    public function testDecorationGidsInJungleTileset(): void
    {
        $decoGids = $this->biome->getDecorationGids();
        $this->assertNotEmpty($decoGids);
        foreach ($decoGids as $gid) {
            $this->assertGreaterThanOrEqual(TilesetRegistry::FIRST_GID_JUNGLE_B, $gid);
        }
    }

    public function testWeather(): void
    {
        $this->assertSame('rain', $this->biome->getWeather());
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
