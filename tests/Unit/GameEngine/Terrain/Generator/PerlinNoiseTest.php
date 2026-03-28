<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator;

use App\GameEngine\Terrain\Generator\PerlinNoise;
use PHPUnit\Framework\TestCase;

class PerlinNoiseTest extends TestCase
{
    public function testNoiseReturnsBoundedValues(): void
    {
        $perlin = new PerlinNoise(42);

        for ($x = 0; $x < 50; ++$x) {
            for ($y = 0; $y < 50; ++$y) {
                $value = $perlin->noise($x * 0.1, $y * 0.1);
                $this->assertGreaterThanOrEqual(-1.0, $value);
                $this->assertLessThanOrEqual(1.0, $value);
            }
        }
    }

    public function testNoiseDeterministic(): void
    {
        $perlin1 = new PerlinNoise(123);
        $perlin2 = new PerlinNoise(123);

        for ($i = 0; $i < 20; ++$i) {
            $x = $i * 0.3;
            $y = $i * 0.7;
            $this->assertSame($perlin1->noise($x, $y), $perlin2->noise($x, $y));
        }
    }

    public function testDifferentSeedsDifferentResults(): void
    {
        $perlin1 = new PerlinNoise(1);
        $perlin2 = new PerlinNoise(999);

        $different = false;
        for ($i = 0; $i < 10; ++$i) {
            if ($perlin1->noise($i * 0.5, $i * 0.5) !== $perlin2->noise($i * 0.5, $i * 0.5)) {
                $different = true;
                break;
            }
        }

        $this->assertTrue($different, 'Different seeds should produce different noise values');
    }

    public function testOctaveNoiseReturnsBoundedValues(): void
    {
        $perlin = new PerlinNoise(77);

        for ($x = 0; $x < 30; ++$x) {
            for ($y = 0; $y < 30; ++$y) {
                $value = $perlin->octaveNoise($x * 0.1, $y * 0.1, 4);
                $this->assertGreaterThanOrEqual(-1.0, $value);
                $this->assertLessThanOrEqual(1.0, $value);
            }
        }
    }

    public function testGenerateHeightmapDimensions(): void
    {
        $perlin = new PerlinNoise(42);
        $heightmap = $perlin->generateHeightmap(40, 30);

        $this->assertCount(40, $heightmap);
        foreach ($heightmap as $column) {
            $this->assertCount(30, $column);
        }
    }

    public function testGenerateHeightmapValuesBetweenZeroAndOne(): void
    {
        $perlin = new PerlinNoise(42);
        $heightmap = $perlin->generateHeightmap(20, 20);

        foreach ($heightmap as $column) {
            foreach ($column as $value) {
                $this->assertGreaterThanOrEqual(0.0, $value);
                $this->assertLessThanOrEqual(1.0, $value);
            }
        }
    }

    public function testGenerateHeightmapNotUniform(): void
    {
        $perlin = new PerlinNoise(42);
        $heightmap = $perlin->generateHeightmap(30, 30);

        $values = [];
        foreach ($heightmap as $column) {
            foreach ($column as $value) {
                $values[] = $value;
            }
        }

        $min = min($values);
        $max = max($values);

        $this->assertGreaterThan(0.1, $max - $min, 'Heightmap should have variation');
    }
}
