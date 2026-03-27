<?php

namespace App\GameEngine\Terrain\Generator;

use App\GameEngine\Terrain\Generator\Biome\ForestBiome;
use App\GameEngine\Terrain\Generator\Biome\PlainsBiome;
use App\GameEngine\Terrain\Generator\Biome\SwampBiome;

/**
 * Registre des biomes disponibles pour le generateur procedural.
 */
class BiomeRegistry
{
    /** @var array<string, BiomeDefinition> */
    private array $biomes = [];

    public function __construct()
    {
        $this->register(new PlainsBiome());
        $this->register(new ForestBiome());
        $this->register(new SwampBiome());
    }

    public function register(BiomeDefinition $biome): void
    {
        $this->biomes[$biome->getSlug()] = $biome;
    }

    public function get(string $slug): ?BiomeDefinition
    {
        return $this->biomes[$slug] ?? null;
    }

    /**
     * @return array<string, string> slug => label
     */
    public function getChoices(): array
    {
        $choices = [];
        foreach ($this->biomes as $slug => $biome) {
            $choices[$slug] = $biome->getLabel();
        }

        return $choices;
    }
}
