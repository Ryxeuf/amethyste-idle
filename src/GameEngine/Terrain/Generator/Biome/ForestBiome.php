<?php

namespace App\GameEngine\Terrain\Generator\Biome;

use App\GameEngine\Terrain\Generator\BiomeDefinition;
use App\GameEngine\Terrain\TilesetRegistry;

/**
 * Biome foret : herbe sombre, densite d'arbres elevee (30-50%), clustering naturel.
 */
class ForestBiome implements BiomeDefinition
{
    public function getSlug(): string
    {
        return 'forest';
    }

    public function getLabel(): string
    {
        return 'Forêt';
    }

    public function getBackgroundGids(): array
    {
        // dark_grass center = FIRST_GID_TERRAIN + 295 = 296
        // + variantes herbe classiques pour diversite
        return [
            TilesetRegistry::FIRST_GID_TERRAIN + 295, // dark_grass center
            TilesetRegistry::FIRST_GID_TERRAIN + 295,
            TilesetRegistry::GID_GRASS_BASE,
            TilesetRegistry::GID_GRASS_ALT1,
        ];
    }

    public function getWaterThreshold(): float
    {
        return 0.20;
    }

    public function getSandThreshold(): float
    {
        return 0.28;
    }

    public function getTreeDensity(): float
    {
        return 0.40;
    }

    public function getTreeGids(): array
    {
        // Tiles du tileset forest (arbres feuillus, differentes variantes)
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
            ['slug' => 'goblin', 'minDifficulty' => 4, 'maxDifficulty' => 6],
            ['slug' => 'spider', 'minDifficulty' => 5, 'maxDifficulty' => 7],
            ['slug' => 'skeleton', 'minDifficulty' => 7, 'maxDifficulty' => 10],
        ];
    }

    public function getHarvestItems(): array
    {
        return ['healing-herb', 'sage', 'rosemary', 'wood'];
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
        return 'dark_grass';
    }

    public function getPerlinScale(): float
    {
        return 0.05;
    }

    public function getPerlinOctaves(): int
    {
        return 4;
    }

    public function getGrassThreshold(): float
    {
        return 0.28;
    }

    public function getDecorationGids(): array
    {
        return [];
    }
}
