<?php

namespace App\GameEngine\Terrain\Generator;

/**
 * Definition d'un biome pour le generateur procedural.
 *
 * Chaque biome fournit les GID de sol, les seuils de terrain (eau, sable),
 * les densites de vegetation, et les listes de mobs/items associes.
 */
interface BiomeDefinition
{
    /**
     * Identifiant unique du biome (ex: 'forest', 'plains', 'swamp').
     */
    public function getSlug(): string;

    /**
     * Nom affichable du biome.
     */
    public function getLabel(): string;

    /**
     * @return int[] GID des variantes de sol (layer background)
     */
    public function getBackgroundGids(): array;

    public function getWaterThreshold(): float;

    public function getSandThreshold(): float;

    public function getTreeDensity(): float;

    /**
     * @return int[] GID des tiles d'arbres/decoration
     */
    public function getTreeGids(): array;

    /**
     * @return array<int, array{slug: string, minDifficulty: int, maxDifficulty: int}>
     */
    public function getAvailableMobs(): array;

    /**
     * @return string[] Slugs d'items recoltables
     */
    public function getHarvestItems(): array;

    public function getWeather(): ?string;

    public function getMusic(): ?string;

    public function getWaterTerrainSlug(): string;

    public function getSandTerrainSlug(): string;

    /**
     * Echelle du bruit Perlin (0.03 = collines larges, 0.08 = terrain chaotique).
     */
    public function getPerlinScale(): float;

    /**
     * Nombre d'octaves Perlin (2 = lisse, 6 = detaille).
     */
    public function getPerlinOctaves(): int;

    /**
     * Seuil au-dessus duquel la heightmap produit du terrain principal (herbe/sol du biome).
     */
    public function getGrassThreshold(): float;

    /**
     * @return int[] GID de decorations non-bloquantes (rochers, fleurs, etc.) placees sur layer 3
     */
    public function getDecorationGids(): array;
}
