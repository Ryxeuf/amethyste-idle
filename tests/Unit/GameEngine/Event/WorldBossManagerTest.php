<?php

namespace App\Tests\Unit\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use App\Event\Game\GameEventActivatedEvent;
use App\Event\Game\GameEventCompletedEvent;
use App\GameEngine\Event\WorldBossManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class WorldBossManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private HubInterface&MockObject $hub;
    private WorldBossManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->hub = $this->createMock(HubInterface::class);

        $this->manager = new WorldBossManager(
            $this->em,
            $this->hub,
            new NullLogger(),
        );
    }

    public function testSpawnWorldBossOnActivation(): void
    {
        $monster = $this->createMonster('ancient_wyrm', 'Wyrm Ancien', 2000, 30);
        $map = $this->createMap(2);

        $event = $this->createBossSpawnEvent([
            'monster_slug' => 'ancient_wyrm',
            'map_id' => 2,
            'coordinates' => '15.10',
            'level' => 30,
        ]);

        $monsterRepo = $this->createMock(EntityRepository::class);
        $monsterRepo->method('findOneBy')->with(['slug' => 'ancient_wyrm'])->willReturn($monster);

        $mapRepo = $this->createMock(EntityRepository::class);
        $mapRepo->method('find')->with(2)->willReturn($map);

        $this->em->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Monster::class => $monsterRepo,
                Map::class => $mapRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $this->em->expects($this->once())->method('persist')
            ->with($this->callback(function (Mob $mob) use ($monster, $map): bool {
                $this->assertSame($monster, $mob->getMonster());
                $this->assertSame($map, $mob->getMap());
                $this->assertSame('15.10', $mob->getCoordinates());
                $this->assertSame(30, $mob->getLevel());
                $this->assertTrue($mob->isWorldBoss());
                $this->assertSame(2000, $mob->getLife());

                // Simulate Doctrine assigning an ID after persist
                $ref = new \ReflectionProperty(Mob::class, 'id');
                $ref->setValue($mob, 99);

                return true;
            }));

        $this->em->expects($this->once())->method('flush');
        $this->hub->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(Update::class));

        $this->manager->onEventActivated(new GameEventActivatedEvent($event));
    }

    public function testIgnoresNonBossSpawnEvents(): void
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

    public function testSkipsSpawnWithMissingParameters(): void
    {
        $event = $this->createBossSpawnEvent(['monster_slug' => 'test']);

        $this->em->expects($this->never())->method('persist');

        $this->manager->onEventActivated(new GameEventActivatedEvent($event));
    }

    public function testDespawnWorldBossOnCompletion(): void
    {
        $event = $this->createBossSpawnEvent([
            'monster_slug' => 'ancient_wyrm',
            'map_id' => 2,
            'coordinates' => '15.10',
        ]);

        $mob = $this->createMock(Mob::class);
        $mob->method('getName')->willReturn('Wyrm Ancien');
        $mob->method('getCoordinates')->willReturn('15.10');
        $mob->method('getMap')->willReturn($this->createMap(2));

        $mobRepo = $this->createMock(EntityRepository::class);
        $mobRepo->method('findBy')->with([
            'gameEvent' => $event,
            'isWorldBoss' => true,
        ])->willReturn([$mob]);

        $this->em->method('getRepository')->with(Mob::class)->willReturn($mobRepo);

        $this->em->expects($this->once())->method('remove')->with($mob);
        $this->em->expects($this->once())->method('flush');
        $this->hub->expects($this->once())->method('publish');

        $this->manager->onEventCompleted(new GameEventCompletedEvent($event));
    }

    public function testDespawnNothingWhenNoBossExists(): void
    {
        $event = $this->createBossSpawnEvent([
            'monster_slug' => 'ancient_wyrm',
            'map_id' => 2,
            'coordinates' => '15.10',
        ]);

        $mobRepo = $this->createMock(EntityRepository::class);
        $mobRepo->method('findBy')->willReturn([]);

        $this->em->method('getRepository')->with(Mob::class)->willReturn($mobRepo);
        $this->em->expects($this->never())->method('remove');
        $this->em->expects($this->never())->method('flush');

        $this->manager->onEventCompleted(new GameEventCompletedEvent($event));
    }

    public function testSpawnSeasonBossOnClimaxBeatActivation(): void
    {
        $monster = $this->createMonster('forest_guardian', 'Gardien de la Forêt', 400, 20);
        $map = $this->createMap(3);

        // Beat de climax de type CUSTOM (NAR-08) portant des parametres de boss (NAR-10).
        $event = $this->createSeasonClimaxEvent([
            'monster_slug' => 'forest_guardian',
            'map_id' => 3,
            'coordinates' => '20.20',
        ]);

        $monsterRepo = $this->createMock(EntityRepository::class);
        $monsterRepo->method('findOneBy')->with(['slug' => 'forest_guardian'])->willReturn($monster);

        $mapRepo = $this->createMock(EntityRepository::class);
        $mapRepo->method('find')->with(3)->willReturn($map);

        $this->em->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Monster::class => $monsterRepo,
                Map::class => $mapRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $this->em->expects($this->once())->method('persist')
            ->with($this->callback(function (Mob $mob) use ($monster, $event): bool {
                $this->assertSame($monster, $mob->getMonster());
                $this->assertTrue($mob->isWorldBoss());
                $this->assertSame($event, $mob->getGameEvent());
                $this->assertTrue($mob->isSeasonBoss(), 'Le boss de climax doit etre un boss de saison.');

                // Simule l'attribution d'un ID par Doctrine apres persist
                // (le publish Mercure lit getId(): int).
                $ref = new \ReflectionProperty(Mob::class, 'id');
                $ref->setValue($mob, 77);

                return true;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->manager->onEventActivated(new GameEventActivatedEvent($event));
    }

    public function testDoesNotSpawnOnNonClimaxCustomBeat(): void
    {
        // Beat de montee (CUSTOM, non climax) : pas de spawn de boss.
        $event = $this->createSeasonBeatEvent(GameEvent::BEAT_MONTEE, [
            'monster_slug' => 'forest_guardian',
            'map_id' => 3,
            'coordinates' => '20.20',
        ]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $this->manager->onEventActivated(new GameEventActivatedEvent($event));
    }

    private function createSeasonClimaxEvent(array $params): GameEvent
    {
        return $this->createSeasonBeatEvent(GameEvent::BEAT_CLIMAX, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function createSeasonBeatEvent(string $beat, array $params): GameEvent
    {
        $event = new GameEvent();
        $event->setName('Season Beat Test');
        $event->setType(GameEvent::TYPE_CUSTOM);
        $event->setStatus(GameEvent::STATUS_ACTIVE);
        $event->setStartsAt(new \DateTime('-1 hour'));
        $event->setEndsAt(new \DateTime('+1 hour'));
        $event->setSeason(new InfluenceSeason());
        $event->setBeat($beat);
        $event->setParameters($params);

        return $event;
    }

    private function createBossSpawnEvent(array $params): GameEvent
    {
        $event = new GameEvent();
        $event->setName('World Boss Test');
        $event->setType(GameEvent::TYPE_BOSS_SPAWN);
        $event->setStatus(GameEvent::STATUS_ACTIVE);
        $event->setStartsAt(new \DateTime('-1 hour'));
        $event->setEndsAt(new \DateTime('+1 hour'));
        $event->setParameters($params);

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
