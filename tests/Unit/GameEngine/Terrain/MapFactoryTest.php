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
    /** @var list<object> */
    private array $persisted = [];

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')
            ->willReturnCallback(function (object $entity): void {
                $this->persisted[] = $entity;
            });

        $this->factory = new MapFactory($em);
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

        $this->factory->createBlankMap('Test', 10, 10, $world);

        $this->assertCount(2, $this->persisted);
        $this->assertInstanceOf(Map::class, $this->persisted[0]);
        $this->assertInstanceOf(Area::class, $this->persisted[1]);
    }

    public function testAreaHasCorrectProperties(): void
    {
        $world = new World();
        $world->setName('Test World');

        $map = $this->factory->createBlankMap('Ma Carte', 20, 15, $world);

        /** @var Area $area */
        $area = $this->persisted[1];
        $this->assertSame('Ma Carte - zone principale', $area->getName());
        $this->assertSame('0.0', $area->getCoordinates());
        $this->assertSame($map, $area->getMap());
    }

    public function testFullDataStructure(): void
    {
        $world = new World();
        $world->setName('Test World');

        $this->factory->createBlankMap('Test', 15, 12, $world);

        /** @var Area $area */
        $area = $this->persisted[1];
        $data = json_decode($area->getFullData(), true);

        $this->assertSame(15, $data['width']);
        $this->assertSame(12, $data['height']);
        $this->assertSame(32, $data['tileWidth']);
        $this->assertSame(32, $data['tileHeight']);
        $this->assertArrayHasKey('cells', $data);
    }

    public function testFullDataCellCount(): void
    {
        $world = new World();
        $world->setName('Test World');

        $this->factory->createBlankMap('Test', 10, 10, $world);

        /** @var Area $area */
        $area = $this->persisted[1];
        $data = json_decode($area->getFullData(), true);

        $this->assertCount(10, $data['cells']);
        foreach ($data['cells'] as $column) {
            $this->assertCount(10, $column);
        }
    }

    public function testFullDataCellFormat(): void
    {
        $world = new World();
        $world->setName('Test World');

        $this->factory->createBlankMap('Test', 10, 10, $world);

        /** @var Area $area */
        $area = $this->persisted[1];
        $data = json_decode($area->getFullData(), true);

        $cell = $data['cells'][5][7];
        $this->assertSame(5, $cell['x']);
        $this->assertSame(7, $cell['y']);
        $this->assertSame([null, null, null, null], $cell['layers']);
        $this->assertSame(0, $cell['mouvement']);
        $this->assertSame('5.7_0_0:0:0:0', $cell['slug']);
    }

    public function testAreaSlugIsSanitized(): void
    {
        $world = new World();
        $world->setName('Test');

        $this->factory->createBlankMap('Ma Carte Speciale!', 10, 10, $world);

        /** @var Area $area */
        $area = $this->persisted[1];
        $this->assertSame('area-ma-carte-speciale--0-0', $area->getSlug());
    }
}
