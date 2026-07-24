<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\GameEngine\Zone\WorldEntityZoneListener;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorldEntityZoneListenerTest extends TestCase
{
    private ZoneRepository&MockObject $zoneRepository;
    private WorldEntityZoneListener $listener;

    protected function setUp(): void
    {
        $this->zoneRepository = $this->createMock(ZoneRepository::class);
        $this->listener = new WorldEntityZoneListener($this->zoneRepository);
    }

    private function dispatch(object $entity): void
    {
        $this->listener->prePersist(new PrePersistEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }

    public function testAssignsZoneToMobPnjAndObjectLayer(): void
    {
        $map = new Map();
        $zone = (new Zone())->setSlug('foret-des-murmures')->setName('Forêt')->setSourceMap($map);
        $this->zoneRepository->method('findEnabledBySourceMap')->with($map)->willReturn($zone);

        $mob = new Mob();
        $mob->setMap($map);
        $pnj = new Pnj();
        $pnj->setMap($map);
        $spot = new ObjectLayer();
        $spot->setMap($map);

        $this->dispatch($mob);
        $this->dispatch($pnj);
        $this->dispatch($spot);

        $this->assertSame($zone, $mob->getZone());
        $this->assertSame($zone, $pnj->getZone());
        $this->assertSame($zone, $spot->getZone());
    }

    public function testMemoizesLookupPerMap(): void
    {
        $map = new Map();
        $zone = (new Zone())->setSlug('mines-profondes')->setName('Mines')->setSourceMap($map);
        $this->zoneRepository->expects($this->once())->method('findEnabledBySourceMap')->willReturn($zone);

        foreach ([new Mob(), new Mob(), new Pnj()] as $entity) {
            $entity->setMap($map);
            $this->dispatch($entity);
        }
    }

    public function testLeavesZoneNullWhenMapIsOffGraph(): void
    {
        $dungeonMap = new Map();
        $this->zoneRepository->method('findEnabledBySourceMap')->willReturn(null);

        $mob = new Mob();
        $mob->setMap($dungeonMap);
        $this->dispatch($mob);

        $this->assertNull($mob->getZone());
    }

    public function testDoesNotOverrideExplicitZone(): void
    {
        $explicitZone = (new Zone())->setSlug('marais-brumeux')->setName('Marais');
        $this->zoneRepository->expects($this->never())->method('findEnabledBySourceMap');

        $mob = new Mob();
        $mob->setMap(new Map());
        $mob->setZone($explicitZone);
        $this->dispatch($mob);

        $this->assertSame($explicitZone, $mob->getZone());
    }

    public function testIgnoresEntitiesWithoutMapOrOtherTypes(): void
    {
        $this->zoneRepository->expects($this->never())->method('findEnabledBySourceMap');

        $mobWithoutMap = new Mob();
        $this->dispatch($mobWithoutMap);
        $this->dispatch(new Player());

        $this->assertNull($mobWithoutMap->getZone());
    }
}
