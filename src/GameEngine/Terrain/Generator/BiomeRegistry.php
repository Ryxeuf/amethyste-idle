<?php

namespace App\GameEngine\Terrain\Generator;

use App\GameEngine\Terrain\Generator\Biome\CaveBiome;
use App\GameEngine\Terrain\Generator\Biome\DesertBiome;
use App\GameEngine\Terrain\Generator\Biome\ForestBiome;
use App\GameEngine\Terrain\Generator\Biome\JungleBiome;
use App\GameEngine\Terrain\Generator\Biome\PlainsBiome;
use App\GameEngine\Terrain\Generator\Biome\SwampBiome;
use App\GameEngine\Terrain\Generator\Biome\TundraBiome;
use App\GameEngine\Terrain\Generator\Biome\VolcanoBiome;

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
        $this->register(new DesertBiome());
        $this->register(new TundraBiome());
        $this->register(new VolcanoBiome());
        $this->register(new JungleBiome());
        $this->register(new CaveBiome());
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
