<?php

namespace App\Tests\Unit\GameEngine\Terrain;

use App\Entity\App\Area;
use App\Entity\App\Map;
use App\GameEngine\Terrain\AreaSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class AreaSynchronizerTest extends TestCase
{
    private AreaSynchronizer $synchronizer;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->synchronizer = new AreaSynchronizer($this->entityManager);
    }

    public function testFilterZoneObjectsReturnsOnlyZonesAndBiomes(): void
    {
        $objects = [
            ['type' => 'mob_spawn', 'name' => 'Goblin', 'x' => 5, 'y' => 5, 'width' => 1, 'height' => 1, 'properties' => []],
            ['type' => 'zone', 'name' => 'Foret Sombre', 'x' => 0, 'y' => 0, 'width' => 20, 'height' => 15, 'properties' => ['biome' => 'forest']],
            ['type' => 'portal', 'name' => 'Exit', 'x' => 10, 'y' => 10, 'width' => 1, 'height' => 1, 'properties' => []],
            ['type' => 'biome', 'name' => 'Desert', 'x' => 20, 'y' => 0, 'width' => 10, 'height' => 10, 'properties' => ['biome' => 'desert']],
        ];

        $result = $this->synchronizer->filterZoneObjects($objects);

        $this->assertCount(2, $result);
        $this->assertSame('zone', $result[0]['type']);
        $this->assertSame('biome', $result[1]['type']);
    }

    public function testFilterZoneObjectsReturnsEmptyWhenNoZones(): void
    {
        $objects = [
            ['type' => 'mob_spawn', 'name' => 'Goblin', 'x' => 5, 'y' => 5, 'width' => 1, 'height' => 1, 'properties' => []],
            ['type' => 'portal', 'name' => 'Exit', 'x' => 10, 'y' => 10, 'width' => 1, 'height' => 1, 'properties' => []],
        ];

        $result = $this->synchronizer->filterZoneObjects($objects);

        $this->assertCount(0, $result);
    }

    public function testSyncZonesReturnsEarlyWhenNoZoneObjects(): void
    {
        $objects = [
            ['type' => 'mob_spawn', 'name' => 'Goblin', 'x' => 5, 'y' => 5, 'width' => 1, 'height' => 1, 'properties' => []],
        ];

        $result = $this->synchronizer->syncZonesFromObjects($objects);

        $this->assertSame(0, $result['synced']);
        $this->assertStringContainsString('No zone/biome objects found', $result['messages'][0]);
    }

    public function testSyncZonesReturnsEarlyWhenNoMapFound(): void
    {
        $mapRepo = $this->createMock(EntityRepository::class);
        $mapRepo->method('find')->willReturn(null);
        $mapRepo->method('findOneBy')->willReturn(null);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Map::class, $mapRepo],
            ]);

        $objects = [
            ['type' => 'zone', 'name' => 'Foret', 'x' => 0, 'y' => 0, 'width' => 20, 'height' => 15, 'properties' => [], 'group' => 'zones'],
        ];

        $result = $this->synchronizer->syncZonesFromObjects($objects);

        $this->assertSame(0, $result['synced']);
        $this->assertStringContainsString('No map found', $result['messages'][0]);
    }

    public function testSyncZonesCreatesNewAreaFromZoneObject(): void
    {
        $map = $this->createMock(Map::class);
        $map->method('getId')->willReturn(1);
        $map->method('getName')->willReturn('World 1');

        $mapRepo = $this->createMock(EntityRepository::class);
        $mapRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($map) {
                if (isset($criteria['slug'])) {
                    return null; // Area not found → will create new
                }

                return $map;
            });

        $areaRepo = $this->createMock(EntityRepository::class);
        $areaRepo->method('findOneBy')->willReturn(null);

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function (string $class) use ($mapRepo, $areaRepo) {
                if ($class === Map::class) {
                    return $mapRepo;
                }

                return $areaRepo;
            });

        $this->entityManager->expects($this->once())->method('persist')
            ->with($this->callback(function (Area $area) {
                $this->assertSame('foret-sombre', $area->getSlug());
                $this->assertSame('Foret Sombre', $area->getName());
                $this->assertSame('forest', $area->getBiome());
                $this->assertSame('rain', $area->getWeather());
                $this->assertSame('forest_theme.ogg', $area->getMusic());
                $this->assertSame(0.8, $area->getLightLevel());
                $this->assertSame(0, $area->getZoneX());
                $this->assertSame(0, $area->getZoneY());
                $this->assertSame(20, $area->getZoneWidth());
                $this->assertSame(15, $area->getZoneHeight());

                return true;
            }));
        $this->entityManager->expects($this->once())->method('flush');

        $objects = [
            [
                'type' => 'zone',
                'name' => 'Foret Sombre',
                'x' => 0,
                'y' => 0,
                'width' => 20,
                'height' => 15,
                'group' => 'zones',
                'properties' => [
                    'biome' => 'forest',
                    'weather' => 'rain',
                    'music' => 'forest_theme.ogg',
                    'light_level' => '0.8',
                ],
            ],
        ];

        $result = $this->synchronizer->syncZonesFromObjects($objects);

        $this->assertSame(1, $result['synced']);
        $this->assertStringContainsString('Created zone "Foret Sombre"', $result['messages'][1]);
    }

    public function testSyncZonesUpdatesExistingArea(): void
    {
        $map = $this->createMock(Map::class);
        $map->method('getId')->willReturn(1);
        $map->method('getName')->willReturn('World 1');

        $existingArea = new Area();
        $existingArea->setSlug('foret-sombre');
        $existingArea->setName('Foret Sombre');
        $existingArea->setCoordinates('0.0');
        $existingArea->setFullData('{}');
        $existingArea->setCreatedAt(new \DateTime());
        $existingArea->setUpdatedAt(new \DateTime());

        $mapRepo = $this->createMock(EntityRepository::class);
        $mapRepo->method('findOneBy')->willReturn($map);

        $areaRepo = $this->createMock(EntityRepository::class);
        $areaRepo->method('findOneBy')->willReturn($existingArea);

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function (string $class) use ($mapRepo, $areaRepo) {
                if ($class === Map::class) {
                    return $mapRepo;
                }

                return $areaRepo;
            });

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $objects = [
            [
                'type' => 'zone',
                'name' => 'Foret Sombre',
                'x' => 0,
                'y' => 0,
                'width' => 20,
                'height' => 15,
                'group' => 'zones',
                'properties' => [
                    'biome' => 'dark_forest',
                    'weather' => 'fog',
                ],
            ],
        ];

        $result = $this->synchronizer->syncZonesFromObjects($objects);

        $this->assertSame(1, $result['synced']);
        $this->assertSame('dark_forest', $existingArea->getBiome());
        $this->assertSame('fog', $existingArea->getWeather());
        $this->assertStringContainsString('Updated zone', $result['messages'][1]);
    }
}
