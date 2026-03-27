<?php

namespace App\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\BiomeDefinition;
use App\GameEngine\Terrain\TilesetRegistry;

/**
 * Biome plaines : herbe variee, peu d'arbres, terrain ouvert.
 * Biome par defaut du generateur procedural.
 */
class PlainsBiome implements BiomeDefinition
{
    public function getSlug(): string
    {
        return 'plains';
    }

    public function getLabel(): string
    {
        return 'Plaines';
    }

    public function getBackgroundGids(): array
    {
        return [
            TilesetRegistry::GID_GRASS_BASE,
            TilesetRegistry::GID_GRASS_ALT1,
            TilesetRegistry::GID_GRASS_ALT2,
            TilesetRegistry::GID_GRASS_ALT3,
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
        return 0.10;
    }

    public function getTreeGids(): array
    {
        // Arbres du tileset forest (petits arbres isoles)
        return [
            TilesetRegistry::FIRST_GID_FOREST + 0,
            TilesetRegistry::FIRST_GID_FOREST + 1,
        ];
    }

    public function getAvailableMobs(): array
    {
        return [
            ['slug' => 'slime', 'minDifficulty' => 1, 'maxDifficulty' => 3],
            ['slug' => 'giant_rat', 'minDifficulty' => 2, 'maxDifficulty' => 4],
            ['slug' => 'bat', 'minDifficulty' => 3, 'maxDifficulty' => 5],
            ['slug' => 'venom_snake', 'minDifficulty' => 5, 'maxDifficulty' => 7],
        ];
    }

    public function getHarvestItems(): array
    {
        return ['dandelion', 'mint', 'lavender'];
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
        return 'water';
    }

    public function getSandTerrainSlug(): string
    {
        return 'sand';
    }
}
