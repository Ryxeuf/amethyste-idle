<?php

namespace App\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\BiomeDefinition;
use App\GameEngine\Terrain\TilesetRegistry;

/**
 * Biome desert : sable, dunes, oasis rares, rochers epars.
 */
class DesertBiome implements BiomeDefinition
{
    public function getSlug(): string
    {
        return 'desert';
    }

    public function getLabel(): string
    {
        return 'Désert';
    }

    public function getBackgroundGids(): array
    {
        // sand center = FIRST_GID_TERRAIN + 307
        // earth center = FIRST_GID_TERRAIN + 676
        return [
            TilesetRegistry::FIRST_GID_TERRAIN + 307, // sand
            TilesetRegistry::FIRST_GID_TERRAIN + 307,
            TilesetRegistry::FIRST_GID_TERRAIN + 307,
            TilesetRegistry::FIRST_GID_TERRAIN + 676, // earth (variante)
        ];
    }

    public function getWaterThreshold(): float
    {
        return 0.15;
    }

    public function getSandThreshold(): float
    {
        return 0.25;
    }

    public function getTreeDensity(): float
    {
        return 0.02;
    }

    public function getTreeGids(): array
    {
        // Rochers du tileset beach comme "arbres" (obstacles)
        return [
            TilesetRegistry::FIRST_GID_BEACH_B + 0,
            TilesetRegistry::FIRST_GID_BEACH_B + 1,
            TilesetRegistry::FIRST_GID_BEACH_B + 2,
            TilesetRegistry::FIRST_GID_BEACH_B + 3,
        ];
    }

    public function getAvailableMobs(): array
    {
        return [
            ['slug' => 'giant_rat', 'minDifficulty' => 1, 'maxDifficulty' => 3],
            ['slug' => 'venom_snake', 'minDifficulty' => 3, 'maxDifficulty' => 6],
            ['slug' => 'skeleton', 'minDifficulty' => 5, 'maxDifficulty' => 8],
            ['slug' => 'bat', 'minDifficulty' => 2, 'maxDifficulty' => 5],
        ];
    }

    public function getHarvestItems(): array
    {
        return ['cactus-fruit', 'sand-crystal'];
    }

    public function getWeather(): ?string
    {
        return 'heat';
    }

    public function getMusic(): ?string
    {
        return null;
    }

    public function getWaterTerrainSlug(): string
    {
        return 'sand_water';
    }

    public function getSandTerrainSlug(): string
    {
        return 'earth';
    }

    public function getPerlinScale(): float
    {
        return 0.04;
    }

    public function getPerlinOctaves(): int
    {
        return 3;
    }

    public function getGrassThreshold(): float
    {
        return 0.25;
    }

    public function getDecorationGids(): array
    {
        // Coquillages et petite vegetation du tileset beach
        return [
            TilesetRegistry::FIRST_GID_BEACH_B + 18,
            TilesetRegistry::FIRST_GID_BEACH_B + 19,
            TilesetRegistry::FIRST_GID_BEACH_B + 34,
            TilesetRegistry::FIRST_GID_BEACH_B + 35,
        ];
    }
}
