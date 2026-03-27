<?php

namespace App\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\BiomeDefinition;
use App\GameEngine\Terrain\TilesetRegistry;

/**
 * Biome toundra : neige, lacs geles, sapins epars, vent glacial.
 */
class TundraBiome implements BiomeDefinition
{
    public function getSlug(): string
    {
        return 'tundra';
    }

    public function getLabel(): string
    {
        return 'Toundra';
    }

    public function getBackgroundGids(): array
    {
        // snow center = FIRST_GID_TERRAIN + 499
        // ice center = FIRST_GID_TERRAIN + 496
        return [
            TilesetRegistry::FIRST_GID_TERRAIN + 499, // snow
            TilesetRegistry::FIRST_GID_TERRAIN + 499,
            TilesetRegistry::FIRST_GID_TERRAIN + 499,
            TilesetRegistry::FIRST_GID_TERRAIN + 496, // ice (variante)
        ];
    }

    public function getWaterThreshold(): float
    {
        return 0.20;
    }

    public function getSandThreshold(): float
    {
        return 0.30;
    }

    public function getTreeDensity(): float
    {
        return 0.08;
    }

    public function getTreeGids(): array
    {
        // Sapins du tileset forest
        return [
            TilesetRegistry::FIRST_GID_FOREST + 0,
            TilesetRegistry::FIRST_GID_FOREST + 1,
            TilesetRegistry::FIRST_GID_FOREST + 32,
        ];
    }

    public function getAvailableMobs(): array
    {
        return [
            ['slug' => 'slime', 'minDifficulty' => 1, 'maxDifficulty' => 3],
            ['slug' => 'bat', 'minDifficulty' => 3, 'maxDifficulty' => 5],
            ['slug' => 'skeleton', 'minDifficulty' => 5, 'maxDifficulty' => 8],
            ['slug' => 'goblin', 'minDifficulty' => 6, 'maxDifficulty' => 9],
        ];
    }

    public function getHarvestItems(): array
    {
        return ['frost-berry', 'ice-crystal'];
    }

    public function getWeather(): ?string
    {
        return 'snow';
    }

    public function getMusic(): ?string
    {
        return null;
    }

    public function getWaterTerrainSlug(): string
    {
        return 'snow_water';
    }

    public function getSandTerrainSlug(): string
    {
        return 'snow_ice';
    }

    public function getPerlinScale(): float
    {
        return 0.05;
    }

    public function getPerlinOctaves(): int
    {
        return 3;
    }

    public function getGrassThreshold(): float
    {
        return 0.30;
    }

    public function getDecorationGids(): array
    {
        return [];
    }
}
