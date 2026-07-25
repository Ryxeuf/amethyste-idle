<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Event\Map\PlayerRespawnedEvent;
use App\GameEngine\Zone\PlayerZoneSynchronizer;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlayerZoneSynchronizerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ZoneRepository&MockObject $zoneRepository;
    private PlayerZoneSynchronizer $synchronizer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->zoneRepository = $this->createMock(ZoneRepository::class);
        $this->synchronizer = new PlayerZoneSynchronizer($this->entityManager, $this->zoneRepository);
    }

    public function testSubscribesToRespawnEventOnly(): void
    {
        $events = PlayerZoneSynchronizer::getSubscribedEvents();

        // ZON-22 : le synchroniseur n'est plus branche sur le deplacement — la
        // zone est la source de verite et le voyage la met a jour directement.
        $this->assertSame([PlayerRespawnedEvent::NAME], array_keys($events));
    }

    public function testAssignsZoneMatchingPlayerMap(): void
    {
        $map = new Map();
        $zone = (new Zone())->setSlug('foret-des-murmures')->setName('Forêt')->setSourceMap($map);
        $player = new Player();
        $player->setMap($map);

        $this->zoneRepository->method('findEnabledBySourceMap')->with($map)->willReturn($zone);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->synchronizer->syncFromMap($player);

        $this->assertSame($zone, $result);
        $this->assertSame($zone, $player->getCurrentZone());
    }

    public function testSkipsRepositoryLookupWhenZoneAlreadyMatchesMap(): void
    {
        $map = new Map();
        $zone = (new Zone())->setSlug('village-de-lumiere')->setName('Village')->setSourceMap($map);
        $player = new Player();
        $player->setMap($map);
        $player->setCurrentZone($zone);

        $this->zoneRepository->expects($this->never())->method('findEnabledBySourceMap');

        $this->assertSame($zone, $this->synchronizer->syncFromMap($player));
    }

    public function testKeepsCurrentZoneWhenMapHasNoZone(): void
    {
        $originMap = new Map();
        $dungeonMap = new Map();
        $originZone = (new Zone())->setSlug('mines-profondes')->setName('Mines')->setSourceMap($originMap);
        $player = new Player();
        $player->setMap($dungeonMap);
        $player->setCurrentZone($originZone);

        $this->zoneRepository->method('findEnabledBySourceMap')->with($dungeonMap)->willReturn(null);

        $result = $this->synchronizer->syncFromMap($player);

        $this->assertSame($originZone, $result);
        $this->assertSame($originZone, $player->getCurrentZone());
    }

    public function testReturnsCurrentZoneWhenPlayerHasNoMap(): void
    {
        $player = new Player();

        $this->zoneRepository->expects($this->never())->method('findEnabledBySourceMap');

        $this->assertNull($this->synchronizer->syncFromMap($player));
    }

    public function testFlushesWhenRequestedAndZoneChanged(): void
    {
        $map = new Map();
        $zone = (new Zone())->setSlug('marais-brumeux')->setName('Marais')->setSourceMap($map);
        $player = new Player();
        $player->setMap($map);

        $this->zoneRepository->method('findEnabledBySourceMap')->willReturn($zone);
        $this->entityManager->expects($this->once())->method('flush');

        $this->synchronizer->syncFromMap($player, true);
    }

    public function testOnPlayerRespawnedSyncsAndFlushes(): void
    {
        $map = new Map();
        $zone = (new Zone())->setSlug('crete-de-ventombre')->setName('Crête')->setSourceMap($map);
        $player = new Player();
        $player->setMap($map);

        $this->zoneRepository->method('findEnabledBySourceMap')->willReturn($zone);
        $this->entityManager->expects($this->once())->method('flush');

        $this->synchronizer->onPlayerRespawned(new PlayerRespawnedEvent($player));

        $this->assertSame($zone, $player->getCurrentZone());
    }
}
