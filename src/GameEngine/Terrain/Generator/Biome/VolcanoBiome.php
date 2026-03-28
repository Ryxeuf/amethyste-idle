<?php

namespace App\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\BiomeDefinition;
use App\GameEngine\Terrain\TilesetRegistry;

/**
 * Biome volcan : terre sombre, lacs de lave, rochers, arbres morts.
 */
class VolcanoBiome implements BiomeDefinition
{
    public function getSlug(): string
    {
        return 'volcano';
    }

    public function getLabel(): string
    {
        return 'Volcan';
    }

    public function getBackgroundGids(): array
    {
        // dark_dirt center = FIRST_GID_TERRAIN + 100
        // red_dirt center = FIRST_GID_TERRAIN + 103
        return [
            TilesetRegistry::FIRST_GID_TERRAIN + 100, // dark_dirt
            TilesetRegistry::FIRST_GID_TERRAIN + 100,
            TilesetRegistry::FIRST_GID_TERRAIN + 103, // red_dirt
        ];
    }

    public function getWaterThreshold(): float
    {
        return 0.22;
    }

    public function getSandThreshold(): float
    {
        return 0.32;
    }

    public function getTreeDensity(): float
    {
        return 0.05;
    }

    public function getTreeGids(): array
    {
        // Rocs pointus et arbres morts du tileset ashlands
        return [
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 8,
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 9,
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 10,
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 32,
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 33,
        ];
    }

    public function getAvailableMobs(): array
    {
        return [
            ['slug' => 'bat', 'minDifficulty' => 3, 'maxDifficulty' => 5],
            ['slug' => 'skeleton', 'minDifficulty' => 5, 'maxDifficulty' => 8],
            ['slug' => 'spider', 'minDifficulty' => 6, 'maxDifficulty' => 9],
            ['slug' => 'goblin', 'minDifficulty' => 7, 'maxDifficulty' => 10],
        ];
    }

    public function getHarvestItems(): array
    {
        return ['obsidian-shard', 'fire-crystal'];
    }

    public function getWeather(): ?string
    {
        return 'ash';
    }

    public function getMusic(): ?string
    {
        return null;
    }

    public function getWaterTerrainSlug(): string
    {
        return 'lava';
    }

    public function getSandTerrainSlug(): string
    {
        return 'black_dirt';
    }

    public function getPerlinScale(): float
    {
        return 0.06;
    }

    public function getPerlinOctaves(): int
    {
        return 5;
    }

    public function getGrassThreshold(): float
    {
        return 0.32;
    }

    public function getDecorationGids(): array
    {
        // Fissures de lave et petits details du tileset ashlands
        return [
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 113,
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 114,
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 115,
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 240,
            TilesetRegistry::FIRST_GID_ASHLANDS_B + 241,
        ];
    }
}
