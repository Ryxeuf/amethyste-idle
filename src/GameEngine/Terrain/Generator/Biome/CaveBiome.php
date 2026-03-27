<?php

namespace App\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\BiomeDefinition;
use App\GameEngine\Terrain\TilesetRegistry;

/**
 * Biome caverne : sol sombre, bassins toxiques, cristaux, stalagmites.
 */
class CaveBiome implements BiomeDefinition
{
    public function getSlug(): string
    {
        return 'cave';
    }

    public function getLabel(): string
    {
        return 'Caverne';
    }

    public function getBackgroundGids(): array
    {
        // black_dirt center = FIRST_GID_TERRAIN + 106
        // grey_dirt center = FIRST_GID_TERRAIN + 109
        return [
            TilesetRegistry::FIRST_GID_TERRAIN + 106, // black_dirt
            TilesetRegistry::FIRST_GID_TERRAIN + 106,
            TilesetRegistry::FIRST_GID_TERRAIN + 109, // grey_dirt
        ];
    }

    public function getWaterThreshold(): float
    {
        return 0.25;
    }

    public function getSandThreshold(): float
    {
        return 0.35;
    }

    public function getTreeDensity(): float
    {
        return 0.15;
    }

    public function getTreeGids(): array
    {
        // Rochers et cristaux du tileset dark dimension comme obstacles
        return [
            TilesetRegistry::FIRST_GID_DARKDIM_B + 3,
            TilesetRegistry::FIRST_GID_DARKDIM_B + 4,
            TilesetRegistry::FIRST_GID_DARKDIM_B + 5,
            TilesetRegistry::FIRST_GID_DARKDIM_B + 64,
            TilesetRegistry::FIRST_GID_DARKDIM_B + 65,
        ];
    }

    public function getAvailableMobs(): array
    {
        return [
            ['slug' => 'bat', 'minDifficulty' => 2, 'maxDifficulty' => 5],
            ['slug' => 'spider', 'minDifficulty' => 4, 'maxDifficulty' => 7],
            ['slug' => 'skeleton', 'minDifficulty' => 6, 'maxDifficulty' => 9],
            ['slug' => 'goblin', 'minDifficulty' => 7, 'maxDifficulty' => 10],
        ];
    }

    public function getHarvestItems(): array
    {
        return ['dark-crystal', 'iron-ore', 'cave-moss'];
    }

    public function getWeather(): ?string
    {
        return null;
    }

    public function getMusic(): ?string
    {
        return null;
    }

    public function getWaterTerrainSlug(): string
    {
        return 'sewer_water';
    }

    public function getSandTerrainSlug(): string
    {
        return 'dark_dirt';
    }

    public function getPerlinScale(): float
    {
        return 0.07;
    }

    public function getPerlinOctaves(): int
    {
        return 5;
    }

    public function getGrassThreshold(): float
    {
        return 0.35;
    }

    public function getDecorationGids(): array
    {
        // Cristaux bleus et roses du tileset dark dimension
        return [
            TilesetRegistry::FIRST_GID_DARKDIM_B + 8,
            TilesetRegistry::FIRST_GID_DARKDIM_B + 9,
            TilesetRegistry::FIRST_GID_DARKDIM_B + 10,
            TilesetRegistry::FIRST_GID_DARKDIM_B + 24,
            TilesetRegistry::FIRST_GID_DARKDIM_B + 25,
            TilesetRegistry::FIRST_GID_DARKDIM_B + 32,
        ];
    }
}
