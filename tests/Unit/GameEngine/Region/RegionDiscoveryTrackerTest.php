<?php

namespace App\Tests\Unit\GameEngine\Region;

use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\PlayerVisitedRegion;
use App\Entity\App\Region;
use App\Event\Map\PlayerMovedEvent;
use App\GameEngine\Region\RegionDiscoveryTracker;
use App\Repository\PlayerVisitedRegionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegionDiscoveryTrackerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerVisitedRegionRepository&MockObject $repository;
    private RegionDiscoveryTracker $tracker;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(PlayerVisitedRegionRepository::class);
        $this->tracker = new RegionDiscoveryTracker($this->entityManager, $this->repository);
    }

    public function testOnPlayerMovedPersistsNewVisit(): void
    {
        $region = $this->makeRegion('forest');
        $player = $this->makePlayerOnRegion($region);

        $this->repository->method('hasVisited')->with($player, $region)->willReturn(false);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(fn ($e) => $e instanceof PlayerVisitedRegion
                && $e->getPlayer() === $player
                && $e->getRegion() === $region));
        $this->entityManager->expects($this->once())->method('flush');

        $this->tracker->onPlayerMoved(new PlayerMovedEvent($player));
    }

    public function testRecordCurrentRegionIsIdempotent(): void
    {
        $region = $this->makeRegion('forest');
        $player = $this->makePlayerOnRegion($region);

        $this->repository->method('hasVisited')->willReturn(true);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->assertFalse($this->tracker->recordCurrentRegion($player));
    }

    public function testRecordCurrentRegionDoesNothingWithoutRegion(): void
    {
        // Pas de map.
        $playerNoMap = $this->makePlayerOnRegion(null);

        // Map sans region.
        $playerNoRegion = $this->makePlayerOnRegion(null);
        $bareMap = new Map();
        $bareMap->setAreaWidth(10);
        $bareMap->setAreaHeight(10);
        $playerNoRegion->setMap($bareMap);

        $this->repository->expects($this->never())->method('hasVisited');
        $this->entityManager->expects($this->never())->method('persist');

        $this->assertFalse($this->tracker->recordCurrentRegion($playerNoMap));
        $this->assertFalse($this->tracker->recordCurrentRegion($playerNoRegion));
    }

    public function testRecordCurrentRegionWithoutFlush(): void
    {
        $region = $this->makeRegion('forest');
        $player = $this->makePlayerOnRegion($region);

        $this->repository->method('hasVisited')->willReturn(false);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->assertTrue($this->tracker->recordCurrentRegion($player, false));
    }

    public function testSubscribedEventsListsPlayerMoved(): void
    {
        $events = RegionDiscoveryTracker::getSubscribedEvents();
        $this->assertSame(['onPlayerMoved'], [$events[PlayerMovedEvent::NAME] ?? null]);
    }

    private function makeRegion(string $slug): Region
    {
        $region = new Region();
        $region->setName($slug);
        $region->setSlug($slug);

        return $region;
    }

    private function makePlayerOnRegion(?Region $region): Player
    {
        $player = new Player();
        $player->setName('TestPlayer');
        $player->setMaxLife(100);
        $player->setLife(100);
        $player->setEnergy(50);
        $player->setMaxEnergy(50);
        $player->setClassType('warrior');
        $player->setCoordinates('5.5');
        $player->setLastCoordinates('5.5');

        if ($region !== null) {
            $map = new Map();
            $map->setAreaWidth(10);
            $map->setAreaHeight(10);
            $map->setRegion($region);
            $player->setMap($map);
        }

        return $player;
    }
}
