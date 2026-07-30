<?php

namespace App\Tests\Unit\GameEngine\Quest;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Event\Zone\PlayerTraveledEvent;
use App\GameEngine\Quest\PlayerQuestUpdater;
use App\GameEngine\Quest\QuestEscortTrackingListener;
use App\GameEngine\Quest\QuestExploreTrackingListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * ZON-22 : le suivi d'exploration et d'escorte est declenche par l'arrivee en
 * zone, et non plus par un deplacement sur la carte (dispatcher supprime).
 */
class QuestZoneTrackingListenersTest extends TestCase
{
    private PlayerQuestUpdater&MockObject $updater;

    protected function setUp(): void
    {
        $this->updater = $this->createMock(PlayerQuestUpdater::class);
    }

    public function testExploreListenerSubscribesToTravelOnly(): void
    {
        $this->assertSame(
            [PlayerTraveledEvent::NAME],
            array_keys(QuestExploreTrackingListener::getSubscribedEvents())
        );
    }

    public function testEscortListenerSubscribesToTravelOnly(): void
    {
        $this->assertSame(
            [PlayerTraveledEvent::NAME],
            array_keys(QuestEscortTrackingListener::getSubscribedEvents())
        );
    }

    public function testExploreListenerForwardsArrivalZone(): void
    {
        $zone = $this->makeZone();

        $this->updater->expects($this->once())->method('updateExplored')->with($zone);

        (new QuestExploreTrackingListener($this->updater))
            ->onPlayerTraveled(new PlayerTraveledEvent(new Player(), $zone));
    }

    public function testEscortListenerForwardsArrivalZone(): void
    {
        $zone = $this->makeZone();

        $this->updater->expects($this->once())->method('updateEscort')->with($zone);

        (new QuestEscortTrackingListener($this->updater))
            ->onPlayerTraveled(new PlayerTraveledEvent(new Player(), $zone));
    }

    private function makeZone(): Zone
    {
        return (new Zone())->setSlug('foret-des-murmures')->setName('Forêt des Murmures');
    }
}
