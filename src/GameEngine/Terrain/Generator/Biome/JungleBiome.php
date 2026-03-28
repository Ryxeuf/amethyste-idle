<?php

namespace App\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\BiomeDefinition;
use App\GameEngine\Terrain\TilesetRegistry;

/**
 * Biome jungle : vegetation dense, herbes hautes, fleurs tropicales, pluie.
 */
class JungleBiome implements BiomeDefinition
{
    public function getSlug(): string
    {
        return 'jungle';
    }

    public function getLabel(): string
    {
        return 'Jungle';
    }

    public function getBackgroundGids(): array
    {
        // dark_grass center = FIRST_GID_TERRAIN + 295
        // long_grass center = FIRST_GID_TERRAIN + 301
        return [
            TilesetRegistry::FIRST_GID_TERRAIN + 295, // dark_grass
            TilesetRegistry::FIRST_GID_TERRAIN + 295,
            TilesetRegistry::FIRST_GID_TERRAIN + 301, // long_grass
            TilesetRegistry::FIRST_GID_TERRAIN + 301,
        ];
    }

    public function getWaterThreshold(): float
    {
        return 0.18;
    }

    public function getSandThreshold(): float
    {
        return 0.26;
    }

    public function getTreeDensity(): float
    {
        return 0.45;
    }

    public function getTreeGids(): array
    {
        // Arbres feuillus denses du tileset forest
        return [
            TilesetRegistry::FIRST_GID_FOREST + 0,
            TilesetRegistry::FIRST_GID_FOREST + 1,
            TilesetRegistry::FIRST_GID_FOREST + 2,
            TilesetRegistry::FIRST_GID_FOREST + 16,
            TilesetRegistry::FIRST_GID_FOREST + 17,
        ];
    }

    public function getAvailableMobs(): array
    {
        return [
            ['slug' => 'slime', 'minDifficulty' => 1, 'maxDifficulty' => 3],
            ['slug' => 'spider', 'minDifficulty' => 3, 'maxDifficulty' => 6],
            ['slug' => 'venom_snake', 'minDifficulty' => 5, 'maxDifficulty' => 8],
            ['slug' => 'goblin', 'minDifficulty' => 6, 'maxDifficulty' => 9],
        ];
    }

    public function getHarvestItems(): array
    {
        return ['tropical-flower', 'vine-rope', 'healing-herb'];
    }

    public function getWeather(): ?string
    {
        return 'rain';
    }

    public function getMusic(): ?string
    {
        return null;
    }

    public function getWaterTerrainSlug(): string
    {
        return 'water';
    }

    public function getSandTerrainSlug(): string
    {
        return 'earth';
    }

    public function getPerlinScale(): float
    {
        return 0.06;
    }

    public function getPerlinOctaves(): int
    {
        return 4;
    }

    public function getGrassThreshold(): float
    {
        return 0.26;
    }

    public function getDecorationGids(): array
    {
        // Fleurs et champignons du tileset jungle
        return [
            TilesetRegistry::FIRST_GID_JUNGLE_B + 0,
            TilesetRegistry::FIRST_GID_JUNGLE_B + 1,
            TilesetRegistry::FIRST_GID_JUNGLE_B + 2,
            TilesetRegistry::FIRST_GID_JUNGLE_B + 3,
            TilesetRegistry::FIRST_GID_JUNGLE_B + 16,
            TilesetRegistry::FIRST_GID_JUNGLE_B + 17,
        ];
    }
}
