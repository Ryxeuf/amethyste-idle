<?php

namespace App\Tests\Unit\GameEngine\Terrain;

use App\Entity\App\Area;
use App\Entity\App\Map;
use App\Entity\App\World;
use App\GameEngine\Terrain\MapFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MapFactoryTest extends TestCase
{
    private MapFactory $factory;
    private EntityManagerInterface $em;

    /** @var list<object> */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->persisted = [];

        $this->em->method('persist')
            ->willReturnCallback(function (object $entity): void {
                $this->persisted[] = $entity;
            });

        $this->factory = new MapFactory($this->em);
    }

    public function testCreateBlankMapReturnsMap(): void
    {
        $world = new World();
        $world->setName('Test World');

        $map = $this->factory->createBlankMap('Test Map', 40, 30, $world);

        $this->assertInstanceOf(Map::class, $map);
        $this->assertSame('Test Map', $map->getName());
        $this->assertSame(40, $map->getAreaWidth());
        $this->assertSame(30, $map->getAreaHeight());
        $this->assertSame($world, $map->getWorld());
    }

    public function testCreateBlankMapPersistsMapAndArea(): void
    {
        $world = new World();
        $world->setName('Test World');

        $this->factory->createBlankMap('Test', 20, 15, $world);

        $this->assertCount(2, $this->persisted);
        $this->assertInstanceOf(Map::class, $this->persisted[0]);
        $this->assertInstanceOf(Area::class, $this->persisted[1]);
    }

    public function testAreaHasCorrectStructure(): void
    {
        $world = new World();
        $world->setName('W');

        $this->factory->createBlankMap('Ma Carte', 20, 15, $world);

        /** @var Area $area */
        $area = $this->persisted[1];

        $this->assertSame('0.0', $area->getCoordinates());
        $this->assertStringContainsString('ma-carte', $area->getSlug());
        $this->assertStringContainsString('Ma Carte', $area->getName());
    }

    public function testFullDataContainsAllCells(): void
    {
        $world = new World();
        $world->setName('W');

        $this->factory->createBlankMap('Small', 10, 12, $world);

        /** @var Area $area */
        $area = $this->persisted[1];
        $data = $area->getFullDataArray();

        $this->assertArrayHasKey('cells', $data);
        $this->assertCount(10, $data['cells']);
        $this->assertCount(12, $data['cells'][0]);
    }

    public function testFullDataCellStructure(): void
    {
        $world = new World();
        $world->setName('W');

        $this->factory->createBlankMap('Cell Test', 10, 10, $world);

        /** @var Area $area */
        $area = $this->persisted[1];
        $data = $area->getFullDataArray();

        $cell = $data['cells'][5][7];

        $this->assertArrayHasKey('layers', $cell);
        $this->assertArrayHasKey('mouvement', $cell);
        $this->assertArrayHasKey('slug', $cell);
        $this->assertCount(4, $cell['layers']);
        $this->assertSame(0, $cell['mouvement']);
        $this->assertSame('5_7_0_0_0_0_0', $cell['slug']);
    }

    public function testFlushIsCalled(): void
    {
        $this->em->expects($this->once())->method('flush');

        $world = new World();
        $world->setName('W');

        $this->factory->createBlankMap('Flush Test', 10, 10, $world);
    }
}
