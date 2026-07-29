<?php

namespace App\Tests\Unit\GameEngine\GameMaster;

use App\Entity\App\GameEvent;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Monster;
use App\Event\Game\GameEventActivatedEvent;
use App\GameEngine\GameMaster\GameMasterAnimationService;
use App\GameEngine\GameMaster\GameMasterJournal;
use App\GameEngine\GameMaster\GameMasterRestrictionException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GameMasterAnimationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private GameMasterJournal&MockObject $journal;
    private GameMasterAnimationService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->journal = $this->createMock(GameMasterJournal::class);
        $this->service = new GameMasterAnimationService($this->entityManager, $this->eventDispatcher, $this->journal);
    }

    private function gameMaster(): Player
    {
        return (new Player())->setGameMaster(true);
    }

    private function zone(): Zone
    {
        return (new Zone())->setSlug('foret')->setName('Foret');
    }

    /**
     * Le journal cite l'identifiant de l'evenement : un evenement de test doit
     * donc en porter un, comme il en porterait un en base.
     */
    private function event(string $name, string $status, string $startsAt, string $endsAt): GameEvent
    {
        $event = new GameEvent();
        (new \ReflectionProperty(GameEvent::class, 'id'))->setValue($event, 42);
        $event->setName($name);
        $event->setStartsAt(new \DateTime($startsAt));
        $event->setEndsAt(new \DateTime($endsAt));
        $event->setStatus($status);

        return $event;
    }

    private function monster(): Monster
    {
        $monster = new Monster();
        $monster->setName('Loup');
        $monster->setLevel(4);
        $monster->setLife(30);

        return $monster;
    }

    public function testSpawnPersistsOrdinaryMobsInTheZone(): void
    {
        $zone = $this->zone();
        $monster = $this->monster();

        $persisted = [];
        $this->entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$persisted): void { $persisted[] = $entity; }
        );

        $spawned = $this->service->spawnMonsters($this->gameMaster(), $zone, $monster, 3);

        $this->assertCount(3, $spawned);
        $this->assertCount(3, $persisted);
        foreach ($spawned as $mob) {
            $this->assertInstanceOf(Mob::class, $mob);
            $this->assertSame($zone, $mob->getZone());
            $this->assertSame($monster, $mob->getMonster());
            // Le monstre d'animation est ordinaire : un mob qui frapperait plus
            // fort qu'un autre rendrait la soiree illisible.
            $this->assertSame(4, $mob->getLevel());
            $this->assertSame(30, $mob->getLife());
        }
    }

    /**
     * Garde-fou, pas regle de jeu : une faute de frappe ne doit pas peupler une
     * zone de deux cents monstres.
     */
    public function testSpawnIsCappedAndNeverEmpty(): void
    {
        $this->assertCount(
            GameMasterAnimationService::MAX_SPAWN_COUNT,
            $this->service->spawnMonsters($this->gameMaster(), $this->zone(), $this->monster(), 500),
        );

        $this->assertCount(1, $this->service->spawnMonsters($this->gameMaster(), $this->zone(), $this->monster(), 0));
    }

    public function testSpawnIsRefusedToAnyoneButAGameMaster(): void
    {
        $this->expectException(GameMasterRestrictionException::class);

        $this->service->spawnMonsters(new Player(), $this->zone(), $this->monster(), 1);
    }

    public function testSpawnIsJournaled(): void
    {
        $this->journal->expects($this->once())
            ->method('record')
            ->with($this->isInstanceOf(Player::class), 'spawn', $this->stringContains('Loup'));

        $this->service->spawnMonsters($this->gameMaster(), $this->zone(), $this->monster(), 2);
    }

    /**
     * La fenetre est **translatee** a maintenant, pas etiree : un evenement
     * d'une heure dure une heure quel que soit le moment du declenchement.
     */
    public function testLaunchKeepsTheEventDuration(): void
    {
        $event = $this->event('La Grande Battue', GameEvent::STATUS_SCHEDULED, '-3 days', '-3 days +2 hours');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(GameEventActivatedEvent::class), GameEventActivatedEvent::NAME);

        $this->service->launchEvent($this->gameMaster(), $event);

        $this->assertSame(GameEvent::STATUS_ACTIVE, $event->getStatus());
        $this->assertEqualsWithDelta(time(), $event->getStartsAt()->getTimestamp(), 2);
        $this->assertSame(7200, $event->getEndsAt()->getTimestamp() - $event->getStartsAt()->getTimestamp());
    }

    public function testLaunchRefusesAnEventAlreadyRunning(): void
    {
        $event = $this->event('En cours', GameEvent::STATUS_ACTIVE, '-1 hour', '+1 hour');

        $this->expectException(GameMasterRestrictionException::class);

        $this->service->launchEvent($this->gameMaster(), $event);
    }

    public function testStopClosesARunningEvent(): void
    {
        $event = $this->event('En cours', GameEvent::STATUS_ACTIVE, '-1 hour', '+1 hour');

        $this->service->stopEvent($this->gameMaster(), $event);

        $this->assertSame(GameEvent::STATUS_COMPLETED, $event->getStatus());
        $this->assertEqualsWithDelta(time(), $event->getEndsAt()->getTimestamp(), 2);
    }

    public function testStopRefusesAnEventThatIsNotRunning(): void
    {
        $event = $this->event('Programme', GameEvent::STATUS_SCHEDULED, '+1 day', '+1 day +1 hour');

        $this->expectException(GameMasterRestrictionException::class);

        $this->service->stopEvent($this->gameMaster(), $event);
    }
}
