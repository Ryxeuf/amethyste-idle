<?php

namespace App\Tests\Unit\GameEngine\Terrain\Generator;

use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\World;
use App\GameEngine\Terrain\Generator\Biome\PlainsBiome;
use App\GameEngine\Terrain\Generator\MapGenerator;
use App\GameEngine\Terrain\TilesetRegistry;
use App\GameEngine\Terrain\WangTileResolver;
use App\Helper\CellHelper;
use Doctrine\Common\Collections\ArrayCollection;
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

        $tilesetRegistry = new TilesetRegistry($packages);
        $wangTileResolver = new WangTileResolver();
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->generator = new MapGenerator($tilesetRegistry, $wangTileResolver, $this->em);
    }

    public function testGenerateProducesValidFullData(): void
    {
        $width = 30;
        $height = 20;

        $map = $this->createMap($width, $height);
        $area = $map->getAreas()->first();

        $this->em->expects($this->once())->method('flush');

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
