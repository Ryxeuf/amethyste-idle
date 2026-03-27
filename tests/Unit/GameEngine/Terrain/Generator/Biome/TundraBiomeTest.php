<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\Biome\TundraBiome;
use PHPUnit\Framework\TestCase;

class TundraBiomeTest extends TestCase
{
    private TundraBiome $biome;

    protected function setUp(): void
    {
        $this->biome = new TundraBiome();
    }

    public function testSlug(): void
    {
        $this->assertSame('tundra', $this->biome->getSlug());
    }

    public function testLabel(): void
    {
        $this->assertSame('Toundra', $this->biome->getLabel());
    }

    public function testBackgroundGidsAreValid(): void
    {
        $this->assertNotEmpty($this->biome->getBackgroundGids());
    }

    public function testThresholdsOrdered(): void
    {
        $this->assertLessThan($this->biome->getSandThreshold(), $this->biome->getWaterThreshold());
    }

    public function testWeather(): void
    {
        $this->assertSame('snow', $this->biome->getWeather());
    }

    public function testWaterTerrainSlug(): void
    {
        $this->assertSame('snow_water', $this->biome->getWaterTerrainSlug());
    }

    public function testSandTerrainSlug(): void
    {
        $this->assertSame('snow_ice', $this->biome->getSandTerrainSlug());
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
