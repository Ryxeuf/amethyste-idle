<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Zone;
use App\GameEngine\Zone\ZoneDefinitionException;
use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

class ZoneDefinitionLoaderTest extends TestCase
{
    private ZoneDefinitionLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new ZoneDefinitionLoader('/project');
    }

    public function testDefaultFileTargetsWorldOne(): void
    {
        self::assertSame('/project/config/game/zones/world_1.yaml', $this->loader->defaultFile());
    }

    public function testNormalizeMinimalZone(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'village' => ['name' => 'Village'],
            ],
        ]);

        self::assertCount(1, $result['zones']);
        $zone = $result['zones'][0];
        self::assertSame('village', $zone['slug']);
        self::assertSame('Village', $zone['name']);
        self::assertSame(Zone::TYPE_WILDERNESS, $zone['type']);
        self::assertFalse($zone['safe']);
        self::assertTrue($zone['enabled']);
        self::assertNull($zone['explore']);
        self::assertNull($zone['gather']);
        self::assertSame([], $result['connections']);
    }

    public function testNormalizeFullZoneWithExploreGatherAndTranslations(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'foret' => [
                    'name' => 'Forêt',
                    'name_en' => 'Forest',
                    'description' => 'Des arbres',
                    'description_en' => 'Trees',
                    'type' => 'wilderness',
                    'safe' => false,
                    'source_map' => 'Forêt des murmures',
                    'explore' => [
                        'weights' => ['mob' => 60, 'nothing' => 40, 'ignored' => 'x'],
                        'chest_gils_min' => 5,
                        'chest_gils_max' => 30,
                    ],
                    'gather' => [
                        ['slug' => 'herbes', 'item' => 'plant-mint', 'profession' => 'herbalism', 'capacity' => 24, 'respawn_seconds' => 900, 'yield_min' => 1, 'yield_max' => 3],
                    ],
                ],
            ],
        ]);

        $zone = $result['zones'][0];
        self::assertSame('Forest', $zone['name_en']);
        self::assertSame('Forêt des murmures', $zone['source_map']);
        self::assertSame(['mob' => 60, 'nothing' => 40], $zone['explore']['weights']);
        self::assertSame(5, $zone['explore']['chest_gils_min']);
        self::assertSame(30, $zone['explore']['chest_gils_max']);
        self::assertCount(1, $zone['gather']);
        self::assertSame('plant-mint', $zone['gather'][0]['item']);
        self::assertSame(3, $zone['gather'][0]['yield_max']);
    }

    public function testNormalizeBidirectionalConnection(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'a' => ['name' => 'A'],
                'b' => ['name' => 'B'],
            ],
            'connections' => [
                ['from' => 'a', 'to' => 'b', 'travel_seconds' => 300, 'bidirectional' => true],
            ],
        ]);

        self::assertCount(1, $result['connections']);
        $connection = $result['connections'][0];
        self::assertSame('a', $connection['from']);
        self::assertSame('b', $connection['to']);
        self::assertSame(300, $connection['travel_seconds']);
        self::assertTrue($connection['bidirectional']);
        self::assertFalse($connection['requires_discovery']);
        self::assertTrue($connection['enabled']);
    }

    public function testEmptyZonesRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => []]);
    }

    public function testZoneMissingNameRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['type' => 'city']]]);
    }

    public function testUnknownZoneTypeRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['name' => 'A', 'type' => 'nowhere']]]);
    }

    public function testConnectionToUnknownZoneRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize([
            'zones' => ['a' => ['name' => 'A']],
            'connections' => [['from' => 'a', 'to' => 'ghost']],
        ]);
    }

    public function testSelfLoopingConnectionRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize([
            'zones' => ['a' => ['name' => 'A']],
            'connections' => [['from' => 'a', 'to' => 'a']],
        ]);
    }

    public function testGatherResourceMissingItemRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize([
            'zones' => [
                'a' => [
                    'name' => 'A',
                    'gather' => [['slug' => 'x', 'profession' => 'mining']],
                ],
            ],
        ]);
    }

    public function testLoadFileParsesShippedWorldOne(): void
    {
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 4));
        $result = $loader->loadFile($loader->defaultFile());

        $slugs = array_column($result['zones'], 'slug');
        self::assertContains('village-de-lumiere', $slugs);
        self::assertContains('crete-de-ventombre', $slugs);
        // Le compte est epingle volontairement : il attrape une edition
        // accidentelle du graphe livre. 8 depuis ZON-26a (etoile + anneau).
        self::assertCount(8, $result['connections']);
    }

    public function testShippedWorldOneHasNoIsolatedZone(): void
    {
        // ZON-26a : une zone injoignable est un contenu mort. La verification
        // porte sur l'intention du graphe, pas sur un effectif fige.
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 4));
        $result = $loader->loadFile($loader->defaultFile());

        $connected = [];
        foreach ($result['connections'] as $connection) {
            $connected[$connection['from']] = true;
            $connected[$connection['to']] = true;
        }

        foreach (array_column($result['zones'], 'slug') as $slug) {
            self::assertArrayHasKey($slug, $connected, sprintf('La zone "%s" n\'est reliee a aucune autre.', $slug));
        }
    }
}
