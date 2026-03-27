<?php

namespace App\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\BiomeDefinition;
use App\GameEngine\Terrain\TilesetRegistry;

/**
 * Biome marecage : herbe sombre, zones d'eau etendues, arbres morts epars.
 */
class SwampBiome implements BiomeDefinition
{
    public function getSlug(): string
    {
        return 'swamp';
    }

    public function getLabel(): string
    {
        return 'Marécage';
    }

    public function getBackgroundGids(): array
    {
        // long_grass center = FIRST_GID_TERRAIN + 301 = 302
        // dark_grass center = FIRST_GID_TERRAIN + 295 = 296
        return [
            TilesetRegistry::FIRST_GID_TERRAIN + 301, // long_grass center
            TilesetRegistry::FIRST_GID_TERRAIN + 301,
            TilesetRegistry::FIRST_GID_TERRAIN + 295, // dark_grass center
        ];
    }

    public function getWaterThreshold(): float
    {
        return 0.35;
    }

    public function getSandThreshold(): float
    {
        return 0.42;
    }

    public function getTreeDensity(): float
    {
        return 0.20;
    }

    public function getTreeGids(): array
    {
        // Arbres morts / souches du tileset forest
        return [
            TilesetRegistry::FIRST_GID_FOREST + 32,
            TilesetRegistry::FIRST_GID_FOREST + 33,
            TilesetRegistry::FIRST_GID_FOREST + 48,
        ];
    }

    public function getAvailableMobs(): array
    {
        return [
            ['slug' => 'zombie', 'minDifficulty' => 5, 'maxDifficulty' => 8],
            ['slug' => 'banshee', 'minDifficulty' => 7, 'maxDifficulty' => 10],
            ['slug' => 'spider', 'minDifficulty' => 5, 'maxDifficulty' => 7],
            ['slug' => 'ochu', 'minDifficulty' => 8, 'maxDifficulty' => 12],
        ];
    }

    public function getHarvestItems(): array
    {
        return ['poisonous-mushroom', 'swamp-root'];
    }

    public function getWeather(): ?string
    {
        return 'fog';
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
        return 'earth';
    }
}
