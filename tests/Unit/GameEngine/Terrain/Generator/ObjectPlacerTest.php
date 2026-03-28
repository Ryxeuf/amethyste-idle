<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator;

use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\World;
use App\Entity\Game\Monster;
use App\GameEngine\Terrain\Generator\Biome\ForestBiome;
use App\GameEngine\Terrain\Generator\Biome\PlainsBiome;
use App\GameEngine\Terrain\Generator\MapGenerator;
use App\GameEngine\Terrain\Generator\ObjectPlacer;
use App\GameEngine\Terrain\TilesetRegistry;
use App\GameEngine\Terrain\WangTileResolver;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

class ObjectPlacerTest extends TestCase
{
    private ObjectPlacer $objectPlacer;
    private MapGenerator $mapGenerator;
    private EntityManagerInterface $em;
    private EntityRepository $monsterRepo;

    protected function setUp(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')->willReturnArgument(0);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $tilesetRegistry = new TilesetRegistry($packages, $this->em);
        $wangTileResolver = new WangTileResolver();
        $this->monsterRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')->willReturnCallback(function (string $class) {
            if ($class === Monster::class) {
                return $this->monsterRepo;
            }

            return $this->createMock(EntityRepository::class);
        });

        $this->objectPlacer = new ObjectPlacer($this->em);
        $this->mapGenerator = new MapGenerator($tilesetRegistry, $wangTileResolver, $this->em, $this->objectPlacer);
    }

    public function testPlaceMobSpawnsPlacesBetween8And15Mobs(): void
    {
        $map = $this->createGeneratedMap(40, 30, new PlainsBiome());

        $monster = $this->createMonster('slime');
        $this->monsterRepo->method('findOneBy')->willReturn($monster);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted) {
            $persisted[] = $entity;
        });

        $count = $this->objectPlacer->placeMobSpawns($map, new PlainsBiome(), 1, 42);

        $this->assertGreaterThanOrEqual(8, $count);
        $this->assertLessThanOrEqual(15, $count);

        $mobs = array_filter($persisted, fn ($e) => $e instanceof Mob);
        $this->assertCount($count, $mobs);
    }

    public function testPlaceMobSpawnsOnlyOnWalkableCells(): void
    {
        $map = $this->createGeneratedMap(30, 20, new PlainsBiome());

        $monster = $this->createMonster('slime');
        $this->monsterRepo->method('findOneBy')->willReturn($monster);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted) {
            $persisted[] = $entity;
        });

        $this->objectPlacer->placeMobSpawns($map, new PlainsBiome(), 1, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        foreach ($persisted as $entity) {
            if (!$entity instanceof Mob) {
                continue;
            }
            $coords = explode('.', $entity->getCoordinates());
            $x = (int) $coords[0];
            $y = (int) ($coords[1] ?? 0);
            $movement = $fullData['cells'][$x][$y]['mouvement'] ?? CellHelper::MOVE_UNREACHABLE;
            $this->assertNotSame(CellHelper::MOVE_UNREACHABLE, $movement, "Mob placed on blocked cell {$x}.{$y}");
        }
    }

    public function testPlaceHarvestSpotsPlaces5To10Spots(): void
    {
        $map = $this->createGeneratedMap(40, 30, new PlainsBiome());

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted) {
            $persisted[] = $entity;
        });

        $count = $this->objectPlacer->placeHarvestSpots($map, new PlainsBiome(), 42);

        $this->assertGreaterThanOrEqual(5, $count);
        $this->assertLessThanOrEqual(10, $count);

        $spots = array_filter($persisted, fn ($e) => $e instanceof ObjectLayer);
        $this->assertCount($count, $spots);
    }

    public function testPlaceHarvestSpotsHaveCorrectType(): void
    {
        $map = $this->createGeneratedMap(40, 30, new ForestBiome());

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted) {
            $persisted[] = $entity;
        });

        $this->objectPlacer->placeHarvestSpots($map, new ForestBiome(), 42);

        foreach ($persisted as $entity) {
            if (!$entity instanceof ObjectLayer) {
                continue;
            }
            $this->assertSame(ObjectLayer::TYPE_HARVEST_SPOT, $entity->getType());
            $this->assertNotNull($entity->getItems());
            $this->assertNotEmpty($entity->getItems());
        }
    }

    public function testEnsureConnectivityOnConnectedMapReturnsZero(): void
    {
        // Creer une carte entierement walkable (pas d'ilots)
        $map = $this->createSimpleWalkableMap(10, 10);

        $passages = $this->objectPlacer->ensureConnectivity($map);

        $this->assertSame(0, $passages);
    }

    public function testEnsureConnectivityFixesDisconnectedIslands(): void
    {
        // Creer une carte avec un mur qui separe deux zones
        $map = $this->createMapWithWall(20, 10);

        $passages = $this->objectPlacer->ensureConnectivity($map);

        $this->assertGreaterThan(0, $passages, 'Should carve passages to connect islands');

        // Verifier que la carte est maintenant connectee
        $passagesAfter = $this->objectPlacer->ensureConnectivity($map);
        $this->assertSame(0, $passagesAfter, 'Map should be connected after fix');
    }

    public function testPlaceZonesSetsAreaMetadata(): void
    {
        $map = $this->createGeneratedMap(20, 15, new ForestBiome());

        $count = $this->objectPlacer->placeZones($map, new ForestBiome());

        $this->assertSame(1, $count);
        $area = $map->getAreas()->first();
        $this->assertSame('forest', $area->getBiome());
    }

    public function testPlaceAllReturnsCounts(): void
    {
        $map = $this->createGeneratedMap(40, 30, new PlainsBiome());

        $monster = $this->createMonster('slime');
        $this->monsterRepo->method('findOneBy')->willReturn($monster);

        $this->em->method('persist')->willReturnCallback(function () {});

        $result = $this->objectPlacer->placeAll($map, new PlainsBiome(), 1, 42);

        $this->assertArrayHasKey('mobs', $result);
        $this->assertArrayHasKey('harvestSpots', $result);
        $this->assertArrayHasKey('zones', $result);
        $this->assertGreaterThan(0, $result['mobs']);
        $this->assertGreaterThan(0, $result['harvestSpots']);
        $this->assertSame(1, $result['zones']);
    }

    public function testMobSpawnsAreSpacedApart(): void
    {
        $map = $this->createGeneratedMap(40, 30, new PlainsBiome());

        $monster = $this->createMonster('slime');
        $this->monsterRepo->method('findOneBy')->willReturn($monster);

        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted) {
            $persisted[] = $entity;
        });

        $this->objectPlacer->placeMobSpawns($map, new PlainsBiome(), 1, 42);

        $mobCoords = [];
        foreach ($persisted as $entity) {
            if (!$entity instanceof Mob) {
                continue;
            }
            $coords = explode('.', $entity->getCoordinates());
            $mobCoords[] = [(int) $coords[0], (int) ($coords[1] ?? 0)];
        }

        // Verifier l'espacement minimum de 3 (distance Manhattan)
        for ($i = 0; $i < \count($mobCoords); ++$i) {
            for ($j = $i + 1; $j < \count($mobCoords); ++$j) {
                $dist = abs($mobCoords[$i][0] - $mobCoords[$j][0]) + abs($mobCoords[$i][1] - $mobCoords[$j][1]);
                $this->assertGreaterThanOrEqual(3, $dist, 'Mobs should be at least 3 cells apart');
            }
        }
    }

    public function testPlaceMobSpawnsReturnsZeroWhenNoWalkableCells(): void
    {
        $map = $this->createFullyBlockedMap(10, 10);

        $count = $this->objectPlacer->placeMobSpawns($map, new PlainsBiome(), 1, 42);

        $this->assertSame(0, $count);
    }

    public function testPlaceMobSpawnsDeterministic(): void
    {
        $map1 = $this->createGeneratedMap(30, 20, new PlainsBiome());
        $map2 = $this->createGeneratedMap(30, 20, new PlainsBiome());

        $monster = $this->createMonster('slime');
        $this->monsterRepo->method('findOneBy')->willReturn($monster);

        $persisted1 = [];
        $persisted2 = [];
        $callCount = 0;

        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted1, &$persisted2, &$callCount) {
            // First batch goes to persisted1, second batch to persisted2
            if ($callCount === 0) {
                $persisted1[] = $entity;
            } else {
                $persisted2[] = $entity;
            }
        });

        $this->objectPlacer->placeMobSpawns($map1, new PlainsBiome(), 1, 42);
        $callCount = 1;
        $this->objectPlacer->placeMobSpawns($map2, new PlainsBiome(), 1, 42);

        $coords1 = array_map(
            fn ($e) => $e instanceof Mob ? $e->getCoordinates() : null,
            $persisted1
        );
        $coords2 = array_map(
            fn ($e) => $e instanceof Mob ? $e->getCoordinates() : null,
            $persisted2
        );

        $this->assertSame(array_filter($coords1), array_filter($coords2), 'Same seed should produce same mob positions');
    }

    private function createGeneratedMap(int $width, int $height, $biome): Map
    {
        $map = $this->createMap($width, $height);
        $this->mapGenerator->generate($map, $biome, 1, 42);

        return $map;
    }

    private function createSimpleWalkableMap(int $width, int $height): Map
    {
        $map = $this->createMap($width, $height);
        $cells = [];

        for ($x = 0; $x < $width; ++$x) {
            $column = [];
            for ($y = 0; $y < $height; ++$y) {
                $column[$y] = [
                    'x' => $x,
                    'y' => $y,
                    'layers' => [null, null, null, null],
                    'mouvement' => CellHelper::MOVE_DEFAULT,
                    'slug' => $x . '.' . $y . '_0_0:0:0:0',
                ];
            }
            $cells[$x] = $column;
        }

        $area = $map->getAreas()->first();
        $area->setFullData(json_encode([
            'width' => $width,
            'height' => $height,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'cells' => $cells,
        ]));

        return $map;
    }

    private function createFullyBlockedMap(int $width, int $height): Map
    {
        $map = $this->createMap($width, $height);
        $cells = [];

        for ($x = 0; $x < $width; ++$x) {
            $column = [];
            for ($y = 0; $y < $height; ++$y) {
                $column[$y] = [
                    'x' => $x,
                    'y' => $y,
                    'layers' => [null, null, null, null],
                    'mouvement' => CellHelper::MOVE_UNREACHABLE,
                    'slug' => $x . '.' . $y . '_-1_0:0:0:0',
                ];
            }
            $cells[$x] = $column;
        }

        $area = $map->getAreas()->first();
        $area->setFullData(json_encode([
            'width' => $width,
            'height' => $height,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'cells' => $cells,
        ]));

        return $map;
    }

    private function createMapWithWall(int $width, int $height): Map
    {
        $map = $this->createMap($width, $height);
        $cells = [];
        $wallX = (int) ($width / 2);

        for ($x = 0; $x < $width; ++$x) {
            $column = [];
            for ($y = 0; $y < $height; ++$y) {
                $isWall = ($x === $wallX);
                $column[$y] = [
                    'x' => $x,
                    'y' => $y,
                    'layers' => [null, null, null, null],
                    'mouvement' => $isWall ? CellHelper::MOVE_UNREACHABLE : CellHelper::MOVE_DEFAULT,
                    'slug' => $x . '.' . $y . '_' . ($isWall ? '-1' : '0') . '_0:0:0:0',
                ];
            }
            $cells[$x] = $column;
        }

        $area = $map->getAreas()->first();
        $area->setFullData(json_encode([
            'width' => $width,
            'height' => $height,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'cells' => $cells,
        ]));

        return $map;
    }

    private function createMap(int $width, int $height): Map
    {
        $world = new World();
        $world->setName('Test World');

        $map = new Map();
        $map->setName('Test Map');
        $map->setAreaWidth($width);
        $map->setAreaHeight($height);
        $map->setWorld($world);
        $map->setCreatedAt(new \DateTime());
        $map->setUpdatedAt(new \DateTime());

        $area = new Area();
        $area->setName('Test Area');
        $area->setSlug('test-area');
        $area->setCoordinates('0.0');
        $area->setMap($map);
        $area->setFullData(json_encode([
            'width' => $width,
            'height' => $height,
            'tileWidth' => 32,
            'tileHeight' => 32,
            'cells' => [],
        ]));
        $area->setCreatedAt(new \DateTime());
        $area->setUpdatedAt(new \DateTime());

        $ref = new \ReflectionProperty(Map::class, 'areas');
        $ref->getValue($map)->add($area);

        return $map;
    }

    private function createMonster(string $slug): Monster
    {
        $monster = new Monster();

        $ref = new \ReflectionProperty(Monster::class, 'slug');
        $ref->setValue($monster, $slug);

        $ref = new \ReflectionProperty(Monster::class, 'name');
        $ref->setValue($monster, ucfirst($slug));

        $ref = new \ReflectionProperty(Monster::class, 'life');
        $ref->setValue($monster, 50);

        return $monster;
    }
}
