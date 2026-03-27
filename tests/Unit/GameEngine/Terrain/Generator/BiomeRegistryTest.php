<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator;

use App\GameEngine\Terrain\Generator\BiomeRegistry;
use PHPUnit\Framework\TestCase;

class BiomeRegistryTest extends TestCase
{
    public function testDefaultBiomesAvailable(): void
    {
        $registry = new BiomeRegistry();

        $this->assertNotNull($registry->get('plains'));
        $this->assertSame('Plaines', $registry->get('plains')->getLabel());
    }

    public function testGetChoicesReturnsBiomes(): void
    {
        $registry = new BiomeRegistry();
        $choices = $registry->getChoices();

        $this->assertArrayHasKey('plains', $choices);
        $this->assertSame('Plaines', $choices['plains']);
    }

    public function testGetUnknownBiomeReturnsNull(): void
    {
        $registry = new BiomeRegistry();

        $this->assertNull($registry->get('nonexistent'));
    }
}
