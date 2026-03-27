<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator;

use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\World;
use App\GameEngine\Terrain\Generator\Biome\CaveBiome;
use App\GameEngine\Terrain\Generator\Biome\DesertBiome;
use App\GameEngine\Terrain\Generator\Biome\ForestBiome;
use App\GameEngine\Terrain\Generator\Biome\JungleBiome;
use App\GameEngine\Terrain\Generator\Biome\PlainsBiome;
use App\GameEngine\Terrain\Generator\Biome\SwampBiome;
use App\GameEngine\Terrain\Generator\Biome\TundraBiome;
use App\GameEngine\Terrain\Generator\Biome\VolcanoBiome;
use App\GameEngine\Terrain\Generator\MapGenerator;
use App\GameEngine\Terrain\Generator\ObjectPlacer;
use App\GameEngine\Terrain\TilesetRegistry;
use App\GameEngine\Terrain\WangTileResolver;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

class MapGeneratorTest extends TestCase
{
    private MapGenerator $generator;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')->willReturnArgument(0);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $tilesetRegistry = new TilesetRegistry($packages, $this->em);
        $wangTileResolver = new WangTileResolver();
        $objectPlacer = new ObjectPlacer($this->em);

        $this->generator = new MapGenerator($tilesetRegistry, $wangTileResolver, $this->em, $objectPlacer);
    }

    public function testGenerateProducesValidFullData(): void
    {
        $width = 30;
        $height = 20;

        $map = $this->createMap($width, $height);
        $area = $map->getAreas()->first();

        $this->em->expects($this->exactly(2))->method('flush');

        $this->generator->generate($map, new PlainsBiome(), 1, 42);

        $fullData = json_decode($area->getFullData(), true);

        $this->assertSame($width, $fullData['width']);
        $this->assertSame($height, $fullData['height']);
        $this->assertSame(32, $fullData['tileWidth']);
        $this->assertSame(32, $fullData['tileHeight']);
        $this->assertCount($width, $fullData['cells']);
    }

    public function testGenerateProducesAllCells(): void
    {
        $width = 20;
        $height = 15;

        $map = $this->createMap($width, $height);

        $this->generator->generate($map, new PlainsBiome(), 1, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        for ($x = 0; $x < $width; ++$x) {
            $this->assertArrayHasKey($x, $fullData['cells']);
            for ($y = 0; $y < $height; ++$y) {
                $this->assertArrayHasKey($y, $fullData['cells'][$x]);
                $cell = $fullData['cells'][$x][$y];
                $this->assertSame($x, $cell['x']);
                $this->assertSame($y, $cell['y']);
                $this->assertCount(4, $cell['layers']);
                $this->assertArrayHasKey('mouvement', $cell);
                $this->assertArrayHasKey('slug', $cell);
            }
        }
    }

    public function testGenerateDeterministic(): void
    {
        $map1 = $this->createMap(20, 15);
        $map2 = $this->createMap(20, 15);

        $this->generator->generate($map1, new PlainsBiome(), 1, 42);
        $this->generator->generate($map2, new PlainsBiome(), 1, 42);

        $this->assertSame(
            $map1->getAreas()->first()->getFullData(),
            $map2->getAreas()->first()->getFullData()
        );
    }

    public function testGenerateContainsMixedTerrain(): void
    {
        $map = $this->createMap(40, 40);

        $this->generator->generate($map, new PlainsBiome(), 1, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        $movements = [];
        foreach ($fullData['cells'] as $column) {
            foreach ($column as $cell) {
                $movements[$cell['mouvement']] = true;
            }
        }

        // Avec une carte 40x40 et un seuil d'eau a 0.25, on devrait avoir a la fois
        // des cellules libres et des cellules bloquees (eau)
        $this->assertArrayHasKey(CellHelper::MOVE_DEFAULT, $movements, 'Should have walkable cells');
        $this->assertArrayHasKey(CellHelper::MOVE_UNREACHABLE, $movements, 'Should have water/blocked cells');
    }

    public function testGenerateSetsBiomeOnArea(): void
    {
        $map = $this->createMap(20, 15);

        $this->generator->generate($map, new PlainsBiome(), 1, 42);

        $area = $map->getAreas()->first();
        $this->assertSame('plains', $area->getBiome());
    }

    public function testGenerateBackgroundLayerHasGrassGids(): void
    {
        $map = $this->createMap(30, 30);

        $this->generator->generate($map, new PlainsBiome(), 1, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        $grassGids = [
            TilesetRegistry::GID_GRASS_BASE,
            TilesetRegistry::GID_GRASS_ALT1,
            TilesetRegistry::GID_GRASS_ALT2,
            TilesetRegistry::GID_GRASS_ALT3,
        ];

        $foundGrassVariants = [];
        foreach ($fullData['cells'] as $column) {
            foreach ($column as $cell) {
                $bgLayer = $cell['layers'][0];
                if ($bgLayer !== null) {
                    $gid = $bgLayer['mapIdx'] + $bgLayer['idxInMap'];
                    if (\in_array($gid, $grassGids, true)) {
                        $foundGrassVariants[$gid] = true;
                    }
                }
            }
        }

        $this->assertNotEmpty($foundGrassVariants, 'Background should contain grass GIDs');
    }

    public function testGenerateForestBiomeProducesValidFullData(): void
    {
        $map = $this->createMap(30, 20);

        $this->generator->generate($map, new ForestBiome(), 3, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        $this->assertSame(30, $fullData['width']);
        $this->assertSame(20, $fullData['height']);
        $this->assertCount(30, $fullData['cells']);
        $this->assertSame('forest', $map->getAreas()->first()->getBiome());
    }

    public function testGenerateSwampBiomeProducesValidFullData(): void
    {
        $map = $this->createMap(30, 20);

        $this->generator->generate($map, new SwampBiome(), 5, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        $this->assertSame(30, $fullData['width']);
        $this->assertSame(20, $fullData['height']);
        $this->assertCount(30, $fullData['cells']);
        $this->assertSame('swamp', $map->getAreas()->first()->getBiome());
        $this->assertSame('fog', $map->getAreas()->first()->getWeather());
    }

    public function testForestBiomePlacesTrees(): void
    {
        $map = $this->createMap(40, 40);

        $this->generator->generate($map, new ForestBiome(), 1, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        $treeCells = 0;
        foreach ($fullData['cells'] as $column) {
            foreach ($column as $cell) {
                if ($cell['layers'][2] !== null) {
                    ++$treeCells;
                    // Les arbres doivent bloquer le passage
                    $this->assertSame(CellHelper::MOVE_UNREACHABLE, $cell['mouvement']);
                }
            }
        }

        // Avec une densite de 40%, on attend des arbres apres lissage
        $this->assertGreaterThan(0, $treeCells, 'Forest biome should place trees on decoration layer');
    }

    public function testSwampBiomeHasMoreWaterThanPlains(): void
    {
        $map1 = $this->createMap(40, 40);
        $map2 = $this->createMap(40, 40);

        $this->generator->generate($map1, new PlainsBiome(), 1, 42);
        $this->generator->generate($map2, new SwampBiome(), 1, 42);

        $countWater = function (string $fullDataJson): int {
            $fullData = json_decode($fullDataJson, true);
            $water = 0;
            foreach ($fullData['cells'] as $column) {
                foreach ($column as $cell) {
                    if ($cell['mouvement'] === CellHelper::MOVE_UNREACHABLE && $cell['layers'][2] === null) {
                        ++$water;
                    }
                }
            }

            return $water;
        };

        $plainsWater = $countWater($map1->getAreas()->first()->getFullData());
        $swampWater = $countWater($map2->getAreas()->first()->getFullData());

        $this->assertGreaterThan($plainsWater, $swampWater, 'Swamp should have more water than plains');
    }

    public function testPlainsBiomeFewTrees(): void
    {
        $map = $this->createMap(40, 40);

        $this->generator->generate($map, new PlainsBiome(), 1, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        $totalCells = 40 * 40;
        $treeCells = 0;
        foreach ($fullData['cells'] as $column) {
            foreach ($column as $cell) {
                if ($cell['layers'][2] !== null) {
                    ++$treeCells;
                }
            }
        }

        // Plains has 10% density, after cellular automaton smoothing should be low
        $this->assertLessThan($totalCells * 0.30, $treeCells, 'Plains should have few trees');
    }

    public function testGenerateDesertBiome(): void
    {
        $map = $this->createMap(30, 20);

        $this->generator->generate($map, new DesertBiome(), 3, 42);

        $area = $map->getAreas()->first();
        $fullData = json_decode($area->getFullData(), true);

        $this->assertSame(30, $fullData['width']);
        $this->assertCount(30, $fullData['cells']);
        $this->assertSame('desert', $area->getBiome());
        $this->assertSame('heat', $area->getWeather());
    }

    public function testGenerateTundraBiome(): void
    {
        $map = $this->createMap(30, 20);

        $this->generator->generate($map, new TundraBiome(), 3, 42);

        $area = $map->getAreas()->first();
        $this->assertSame('tundra', $area->getBiome());
        $this->assertSame('snow', $area->getWeather());
    }

    public function testGenerateVolcanoBiome(): void
    {
        $map = $this->createMap(30, 20);

        $this->generator->generate($map, new VolcanoBiome(), 5, 42);

        $area = $map->getAreas()->first();
        $this->assertSame('volcano', $area->getBiome());
        $this->assertSame('ash', $area->getWeather());
    }

    public function testGenerateJungleBiome(): void
    {
        $map = $this->createMap(30, 20);

        $this->generator->generate($map, new JungleBiome(), 4, 42);

        $area = $map->getAreas()->first();
        $this->assertSame('jungle', $area->getBiome());
        $this->assertSame('rain', $area->getWeather());
    }

    public function testGenerateCaveBiome(): void
    {
        $map = $this->createMap(30, 20);

        $this->generator->generate($map, new CaveBiome(), 6, 42);

        $area = $map->getAreas()->first();
        $this->assertSame('cave', $area->getBiome());
        $this->assertNull($area->getWeather());
    }

    public function testSpawnZoneIsWalkable(): void
    {
        $map = $this->createMap(30, 20);

        $this->generator->generate($map, new PlainsBiome(), 1, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        $centerX = 15;
        $centerY = 10;

        // Le carre 5x5 au centre doit etre walkable
        for ($x = $centerX - 2; $x <= $centerX + 2; ++$x) {
            for ($y = $centerY - 2; $y <= $centerY + 2; ++$y) {
                $cell = $fullData['cells'][$x][$y];
                $this->assertSame(
                    CellHelper::MOVE_DEFAULT,
                    $cell['mouvement'],
                    "Spawn zone cell ($x, $y) should be walkable"
                );
            }
        }
    }

    public function testDecorationsArePlacedOnOverlayLayer(): void
    {
        $map = $this->createMap(60, 60);

        // Le desert a des decorations definies
        $this->generator->generate($map, new DesertBiome(), 1, 42);

        $fullData = json_decode($map->getAreas()->first()->getFullData(), true);

        $decoCount = 0;
        foreach ($fullData['cells'] as $column) {
            foreach ($column as $cell) {
                if ($cell['layers'][3] !== null) {
                    ++$decoCount;
                    // Les decorations ne bloquent pas le passage
                    $this->assertSame(CellHelper::MOVE_DEFAULT, $cell['mouvement']);
                }
            }
        }

        // Avec ~5% de chance sur une carte 60x60, on s'attend a des decorations
        $this->assertGreaterThan(0, $decoCount, 'Desert biome should place decorations on overlay layer');
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

        // Synchroniser manuellement la collection (pas de addArea() dans Map)
        $ref = new \ReflectionProperty(Map::class, 'areas');
        $ref->getValue($map)->add($area);

        return $map;
    }
}
