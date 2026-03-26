<?php

namespace App\Tests\Unit\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\Event\Game\GameEventActivatedEvent;
use App\Event\Game\GameEventCompletedEvent;
use App\GameEngine\Event\InvasionManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class InvasionManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private HubInterface&MockObject $hub;
    private InvasionManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->hub = $this->createMock(HubInterface::class);

        $this->manager = new InvasionManager(
            $this->em,
            $this->hub,
            new NullLogger(),
        );
    }

    public function testIgnoresNonInvasionEvents(): void
    {
        $event = new GameEvent();
        $event->setName('Test');
        $event->setType(GameEvent::TYPE_XP_BONUS);
        $event->setStatus(GameEvent::STATUS_ACTIVE);
        $event->setStartsAt(new \DateTime('-1 hour'));
        $event->setEndsAt(new \DateTime('+1 hour'));

        $this->em->expects($this->never())->method('persist');

        $this->manager->onEventActivated(new GameEventActivatedEvent($event));
    }

    public function testSpawnsMobsOnActivation(): void
    {
        $monster = $this->createMonster('goblin', 'Gobelin', 50, 5);
        $map = $this->createMap(2);
        $event = $this->createInvasionEvent();

        $monsterRepo = $this->createMock(EntityRepository::class);
        $monsterRepo->method('findBy')->with(['slug' => ['goblin', 'skeleton']])->willReturn([$monster]);

        $mapRepo = $this->createMock(EntityRepository::class);
        $mapRepo->method('find')->with(2)->willReturn($map);

        $this->em->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Monster::class => $monsterRepo,
                Map::class => $mapRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $persistedMobs = [];
        $this->em->expects($this->exactly(3))->method('persist')
            ->with($this->callback(function (Mob $mob) use (&$persistedMobs): bool {
                $ref = new \ReflectionProperty(Mob::class, 'id');
                $ref->setValue($mob, \count($persistedMobs) + 1);
                $persistedMobs[] = $mob;

                return true;
            }));

        $this->em->expects($this->exactly(2))->method('flush');
        $this->hub->expects($this->exactly(4))->method('publish')
            ->with($this->isInstanceOf(Update::class));

        $this->manager->onEventActivated(new GameEventActivatedEvent($event));

        $this->assertCount(3, $persistedMobs);

        $params = $event->getParameters();
        $this->assertSame(1, $params['current_wave']);
        $this->assertSame(0, $params['total_kills']);
    }

    public function testSkipsActivationWithMissingParameters(): void
    {
        $event = new GameEvent();
        $event->setName('Bad Invasion');
        $event->setType(GameEvent::TYPE_INVASION);
        $event->setStatus(GameEvent::STATUS_ACTIVE);
        $event->setStartsAt(new \DateTime('-1 hour'));
        $event->setEndsAt(new \DateTime('+1 hour'));
        $event->setParameters(['mob_slugs' => ['goblin']]);

        $this->em->expects($this->never())->method('persist');

        $this->manager->onEventActivated(new GameEventActivatedEvent($event));
    }

    public function testRecordKillUpdatesParameters(): void
    {
        $event = $this->createInvasionEvent();
        $params = $event->getParameters();
        $params['kills'] = [];
        $params['total_kills'] = 0;
        $event->setParameters($params);

        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn(42);

        $this->hub->expects($this->once())->method('publish');

        $this->manager->recordKill($event, $player);

        $params = $event->getParameters();
        $this->assertSame(1, $params['kills']['42']);
        $this->assertSame(1, $params['total_kills']);

        $this->manager->recordKill($event, $player);

        $params = $event->getParameters();
        $this->assertSame(2, $params['kills']['42']);
        $this->assertSame(2, $params['total_kills']);
    }

    public function testDespawnMobsOnCompletion(): void
    {
        $event = $this->createInvasionEvent();
        $params = $event->getParameters();
        $params['kills'] = [];
        $params['total_kills'] = 0;
        $event->setParameters($params);

        $mob = $this->createMock(Mob::class);
        $mob->method('getName')->willReturn('Gobelin');
        $mob->method('getCoordinates')->willReturn('15.10');
        $mob->method('getMap')->willReturn($this->createMap(2));
        $mob->method('getFight')->willReturn(null);

        $mobRepo = $this->createMock(EntityRepository::class);
        $mobRepo->method('findBy')->with(['gameEvent' => $event])->willReturn([$mob]);

        $playerRepo = $this->createMock(EntityRepository::class);
        $playerRepo->method('findBy')->willReturn([]);

        $this->em->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Mob::class => $mobRepo,
                Player::class => $playerRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $this->em->expects($this->once())->method('remove')->with($mob);
        $this->em->expects($this->atLeastOnce())->method('flush');

        $this->manager->onEventCompleted(new GameEventCompletedEvent($event));
    }

    public function testDistributesRewardsWhenObjectiveMet(): void
    {
        $event = $this->createInvasionEvent();
        $params = $event->getParameters();
        $params['kills'] = ['1' => 5, '2' => 3];
        $params['total_kills'] = 8;
        $event->setParameters($params);

        $player1 = $this->createMock(Player::class);
        $player1->method('getId')->willReturn(1);
        $player1->expects($this->once())->method('addGils')
            ->with($this->callback(fn (int $gold) => $gold === 94)); // 150 * 5/8 = 93.75 -> 94

        $player2 = $this->createMock(Player::class);
        $player2->method('getId')->willReturn(2);
        $player2->expects($this->once())->method('addGils')
            ->with($this->callback(fn (int $gold) => $gold === 56)); // 150 * 3/8 = 56.25 -> 56

        $mobRepo = $this->createMock(EntityRepository::class);
        $mobRepo->method('findBy')->willReturn([]);

        $playerRepo = $this->createMock(EntityRepository::class);
        $playerRepo->method('findBy')->with(['id' => [1, 2]])->willReturn([$player1, $player2]);

        $this->em->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Mob::class => $mobRepo,
                Player::class => $playerRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $this->manager->onEventCompleted(new GameEventCompletedEvent($event));
    }

    public function testNoRewardsWhenObjectiveNotMet(): void
    {
        $event = $this->createInvasionEvent();
        $params = $event->getParameters();
        $params['kills'] = ['1' => 2];
        $params['total_kills'] = 2;
        $event->setParameters($params);

        $mobRepo = $this->createMock(EntityRepository::class);
        $mobRepo->method('findBy')->willReturn([]);

        $this->em->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Mob::class => $mobRepo,
                Player::class => $this->createMock(EntityRepository::class),
                default => $this->createMock(EntityRepository::class),
            },
        );

        // Player repo should not be queried for rewards
        $this->em->expects($this->never())->method('persist');

        $this->manager->onEventCompleted(new GameEventCompletedEvent($event));
    }

    public function testTickSpawnsNextWaveWhenReady(): void
    {
        $monster = $this->createMonster('goblin', 'Gobelin', 50, 5);
        $map = $this->createMap(2);

        $event = $this->createInvasionEvent();
        $params = $event->getParameters();
        $params['current_wave'] = 1;
        $params['wave_spawned_at'] = (new \DateTime('-3 minutes'))->format('c');
        $event->setParameters($params);

        $eventRepo = $this->createMock(EntityRepository::class);
        $eventRepo->method('findBy')->willReturn([$event]);

        $monsterRepo = $this->createMock(EntityRepository::class);
        $monsterRepo->method('findBy')->willReturn([$monster]);

        $mapRepo = $this->createMock(EntityRepository::class);
        $mapRepo->method('find')->willReturn($map);

        $this->em->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                GameEvent::class => $eventRepo,
                Monster::class => $monsterRepo,
                Map::class => $mapRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $this->em->expects($this->exactly(3))->method('persist');

        $wavesSpawned = $this->manager->tick();

        $this->assertSame(1, $wavesSpawned);
        $this->assertSame(2, $event->getParameters()['current_wave']);
    }

    public function testTickDoesNotSpawnWhenTooEarly(): void
    {
        $event = $this->createInvasionEvent();
        $params = $event->getParameters();
        $params['current_wave'] = 1;
        $params['wave_spawned_at'] = (new \DateTime('-30 seconds'))->format('c');
        $event->setParameters($params);

        $eventRepo = $this->createMock(EntityRepository::class);
        $eventRepo->method('findBy')->willReturn([$event]);

        $this->em->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                GameEvent::class => $eventRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $this->em->expects($this->never())->method('persist');

        $wavesSpawned = $this->manager->tick();

        $this->assertSame(0, $wavesSpawned);
    }

    public function testTickDoesNotSpawnBeyondMaxWaves(): void
    {
        $event = $this->createInvasionEvent();
        $params = $event->getParameters();
        $params['current_wave'] = 3;
        $params['wave_spawned_at'] = (new \DateTime('-5 minutes'))->format('c');
        $event->setParameters($params);

        $eventRepo = $this->createMock(EntityRepository::class);
        $eventRepo->method('findBy')->willReturn([$event]);

        $this->em->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                GameEvent::class => $eventRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $this->em->expects($this->never())->method('persist');

        $wavesSpawned = $this->manager->tick();

        $this->assertSame(0, $wavesSpawned);
    }

    private function createInvasionEvent(): GameEvent
    {
        $event = new GameEvent();
        $event->setName('Invasion Test');
        $event->setType(GameEvent::TYPE_INVASION);
        $event->setStatus(GameEvent::STATUS_ACTIVE);
        $event->setStartsAt(new \DateTime('-1 hour'));
        $event->setEndsAt(new \DateTime('+1 hour'));
        $event->setParameters([
            'mob_slugs' => ['goblin', 'skeleton'],
            'count_per_wave' => 3,
            'map_id' => 2,
            'spawn_coordinates' => ['15.10', '16.10', '17.10'],
            'wave_count' => 3,
            'wave_interval_seconds' => 120,
            'kill_objective' => 7,
            'rewards' => ['gold' => 150, 'xp' => 300],
        ]);

        return $event;
    }

    private function createMonster(string $slug, string $name, int $life, int $level): Monster
    {
        $monster = new Monster();
        $monster->setName($name);
        $monster->setSlug($slug);
        $monster->setLife($life);
        $monster->setLevel($level);

        return $monster;
    }

    private function createMap(int $id): Map&MockObject
    {
        $map = $this->createMock(Map::class);
        $map->method('getId')->willReturn($id);

        return $map;
    }
}
