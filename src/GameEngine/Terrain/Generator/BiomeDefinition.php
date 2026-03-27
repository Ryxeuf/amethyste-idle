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
     * GID des variantes de sol (layer background).
     * Selection aleatoire ponderee parmi ces GID.
     *
     * @return int[] Liste de GID globaux
     */
    public function getBackgroundGids(): array;

    /**
     * Seuil de heightmap en dessous duquel on place de l'eau.
     * Valeur entre 0 et 1 (ex: 0.25 = 25% de la carte en eau).
     */
    public function getWaterThreshold(): float;

    /**
     * Seuil de heightmap pour la bande de sable/transition entre eau et terrain.
     * Valeur entre waterThreshold et 1.
     */
    public function getSandThreshold(): float;

    /**
     * Densite d'arbres/vegetation (0.0 a 1.0).
     * Utilisee par l'automate cellulaire pour le placement.
     */
    public function getTreeDensity(): float;

    /**
     * GID des tiles d'arbres/decoration (layer decoration).
     *
     * @return int[] Liste de GID globaux
     */
    public function getTreeGids(): array;

    /**
     * Mobs disponibles par plage de difficulte.
     * Format: [['slug' => string, 'minDifficulty' => int, 'maxDifficulty' => int], ...]
     *
     * @return array<int, array{slug: string, minDifficulty: int, maxDifficulty: int}>
     */
    public function getAvailableMobs(): array;

    /**
     * Items recoltables dans ce biome.
     *
     * @return string[] Slugs d'items
     */
    public function getHarvestItems(): array;

    /**
     * Type de meteo par defaut (null = pas de meteo speciale).
     */
    public function getWeather(): ?string;

    /**
     * Musique de fond du biome (null = musique par defaut).
     */
    public function getMusic(): ?string;

    /**
     * Terrain slug pour l'auto-tiling de l'eau (ex: 'water', 'sand_water').
     */
    public function getWaterTerrainSlug(): string;

    /**
     * Terrain slug pour la bande de transition (ex: 'sand', 'earth').
     */
    public function getSandTerrainSlug(): string;
}
