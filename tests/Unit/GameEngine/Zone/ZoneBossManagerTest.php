<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\GameEvent;
use App\Entity\App\Zone;
use App\Entity\App\ZoneBoss;
use App\Entity\Game\Monster;
use App\Event\Game\GameEventActivatedEvent;
use App\GameEngine\Zone\ZoneBossManager;
use App\Repository\ZoneBossRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ZoneBossManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $monsterRepository;
    private ZoneBossRepository&MockObject $bossRepository;
    private ZoneBossManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->monsterRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Monster::class, $this->monsterRepository],
        ]);
        $this->bossRepository = $this->createMock(ZoneBossRepository::class);

        $this->manager = new ZoneBossManager(
            $this->entityManager,
            $this->bossRepository,
            $this->createMock(LoggerInterface::class),
        );
    }

    private function buildEvent(?Zone $zone, ?array $params): GameEvent
    {
        $event = new GameEvent();
        $event->setName('Reveil du colosse');
        $event->setZone($zone);
        $event->setParameters($params);

        return $event;
    }

    public function testCreatesBossOnZoneBossEventActivation(): void
    {
        $monster = new Monster();
        $monster->setName('Colosse');
        $monster->setSlug('colossus');
        $monster->setLife(500);

        $event = $this->buildEvent($this->buildZone(), ['monster_slug' => 'colossus', 'boss_hp' => 5000]);
        $this->bossRepository->method('findOneByGameEvent')->willReturn(null);
        $this->monsterRepository->method('findOneBy')->with(['slug' => 'colossus'])->willReturn($monster);

        $persisted = null;
        $this->entityManager->expects($this->once())->method('persist')
            ->willReturnCallback(function ($e) use (&$persisted) { $persisted = $e; });
        $this->entityManager->expects($this->once())->method('flush');

        $this->manager->onGameEventActivated(new GameEventActivatedEvent($event));

        $this->assertInstanceOf(ZoneBoss::class, $persisted);
        $this->assertSame(5000, $persisted->getHpMax());
        $this->assertSame($monster, $persisted->getMonster());
    }

    public function testDefaultsHpToMonsterLifeWhenUnspecified(): void
    {
        $monster = new Monster();
        $monster->setName('Colosse');
        $monster->setSlug('colossus');
        $monster->setLife(500);

        $event = $this->buildEvent($this->buildZone(), ['monster_slug' => 'colossus']);
        $this->bossRepository->method('findOneByGameEvent')->willReturn(null);
        $this->monsterRepository->method('findOneBy')->willReturn($monster);

        $persisted = null;
        $this->entityManager->method('persist')->willReturnCallback(function ($e) use (&$persisted) { $persisted = $e; });

        $this->manager->onGameEventActivated(new GameEventActivatedEvent($event));

        $this->assertSame(500, $persisted->getHpMax());
    }

    public function testIgnoresEventWithoutZone(): void
    {
        $event = $this->buildEvent(null, ['monster_slug' => 'colossus']);
        $this->entityManager->expects($this->never())->method('persist');

        $this->manager->onGameEventActivated(new GameEventActivatedEvent($event));
    }

    public function testIgnoresEventWithoutMonsterSlug(): void
    {
        $event = $this->buildEvent($this->buildZone(), ['boss_hp' => 1000]);
        $this->entityManager->expects($this->never())->method('persist');

        $this->manager->onGameEventActivated(new GameEventActivatedEvent($event));
    }

    public function testIdempotentWhenBossAlreadyExists(): void
    {
        $event = $this->buildEvent($this->buildZone(), ['monster_slug' => 'colossus']);
        $monster = new Monster();
        $monster->setSlug('colossus');
        $monster->setLife(1);
        $existing = new ZoneBoss($event, $monster, 1);
        $this->bossRepository->method('findOneByGameEvent')->willReturn($existing);
        $this->entityManager->expects($this->never())->method('persist');

        $this->manager->onGameEventActivated(new GameEventActivatedEvent($event));
    }

    private function buildZone(): Zone
    {
        return (new Zone())->setSlug('foret')->setName('Foret');
    }
}
