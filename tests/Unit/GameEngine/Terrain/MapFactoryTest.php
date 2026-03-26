<?php

namespace App\Tests\Unit\GameEngine\Terrain;

use App\Entity\App\World;
use App\GameEngine\Terrain\MapFactory;
use App\GameEngine\Terrain\TilesetRegistry;
use App\Helper\CellHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MapFactoryTest extends TestCase
{
    private MapFactory $factory;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->factory = new MapFactory($this->em);
    }

    public function testCreateReturnsMapWithCorrectProperties(): void
    {
        $this->em->expects($this->exactly(2))->method('persist');

        $world = $this->createMock(World::class);
        $map = $this->factory->create('Test Map', $world, 30, 20);

        $this->assertSame('Test Map', $map->getName());
        $this->assertSame($world, $map->getWorld());
        $this->assertSame(30, $map->getAreaWidth());
        $this->assertSame(20, $map->getAreaHeight());
        $this->assertSame('0.0', $map->getCoordinates());
    }

    public function testCreateGeneratesAreaWithFullData(): void
    {
        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $world = $this->createMock(World::class);
        $map = $this->factory->create('Test', $world, 15, 10);

        $this->assertCount(2, $persisted);

        $area = $persisted[1];
        $this->assertInstanceOf(\App\Entity\App\Area::class, $area);
        $this->assertSame('0.0', $area->getCoordinates());
        $this->assertSame('plains', $area->getBiome());

        $data = json_decode($area->getFullData(), true);
        $this->assertArrayHasKey('cells', $data);
        $this->assertCount(15, $data['cells']);
        $this->assertCount(10, $data['cells'][0]);
    }

    public function testBlankCellsAreWalkableGrass(): void
    {
        $persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $world = $this->createMock(World::class);
        $this->factory->create('Test', $world, 5, 5);

        $area = $persisted[1];
        $data = json_decode($area->getFullData(), true);
        $cell = $data['cells'][0][0];

        $this->assertSame(0, $cell['x']);
        $this->assertSame(0, $cell['y']);
        $this->assertSame(CellHelper::MOVE_DEFAULT, $cell['mouvement']);
        $this->assertStringContainsString('0.0_0_0:0:0:0', $cell['slug']);
        $this->assertCount(1, $cell['layers']);
        $this->assertSame('terrain', $cell['layers'][0]['tilesetName']);

        $gid = $cell['layers'][0]['mapIdx'];
        $validGids = [
            TilesetRegistry::GID_GRASS_BASE,
            TilesetRegistry::GID_GRASS_ALT1,
            TilesetRegistry::GID_GRASS_ALT2,
            TilesetRegistry::GID_GRASS_ALT3,
        ];
        $this->assertContains($gid, $validGids);
    }
}
