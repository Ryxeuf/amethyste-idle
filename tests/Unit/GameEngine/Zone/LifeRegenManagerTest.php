<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Fight;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\GameEngine\Zone\LifeRegenManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LifeRegenManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private LifeRegenManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->with(Parameter::class)->willReturn($this->parameterRepository);
        $this->manager = new LifeRegenManager($this->entityManager);
    }

    private function buildPlayer(int $life, int $maxLife = 100, ?\DateTimeImmutable $updatedAt = null): Player
    {
        $player = new Player();
        $player->setMaxLife($maxLife);
        $player->setLife($life);
        $player->setLifeUpdatedAt($updatedAt);

        return $player;
    }

    public function testRefreshInitializesAnchorOnFirstCall(): void
    {
        $player = $this->buildPlayer(50);

        $this->assertSame(0, $this->manager->refresh($player));
        $this->assertNotNull($player->getLifeUpdatedAt());
        $this->assertSame(50, $player->getLife());
    }

    public function testRefreshGrantsElapsedPoints(): void
    {
        // 120 s ecoulees a 12 s/PV = 10 points.
        $player = $this->buildPlayer(50, 100, new \DateTimeImmutable('-120 seconds'));

        $granted = $this->manager->refresh($player);

        $this->assertSame(10, $granted);
        $this->assertSame(60, $player->getLife());
    }

    public function testRefreshKeepsRemainder(): void
    {
        // 30 s ecoulees a 12 s/PV = 2 points + reliquat 6 s.
        $player = $this->buildPlayer(50, 100, new \DateTimeImmutable('-30 seconds'));

        $granted = $this->manager->refresh($player);

        $this->assertSame(2, $granted);
        $this->assertSame(52, $player->getLife());
        // Reliquat conserve : l'ancre avance de 2 * 12 s exactement.
        $this->assertEqualsWithDelta(time() - 6, $player->getLifeUpdatedAt()->getTimestamp(), 2);
    }

    public function testRefreshCapsAtMaxLife(): void
    {
        $player = $this->buildPlayer(98, 100, new \DateTimeImmutable('-3600 seconds'));

        $granted = $this->manager->refresh($player);

        $this->assertSame(2, $granted);
        $this->assertSame(100, $player->getLife());
    }

    public function testRefreshDoesNothingWhenFull(): void
    {
        $player = $this->buildPlayer(100, 100, new \DateTimeImmutable('-3600 seconds'));

        $this->assertSame(0, $this->manager->refresh($player));
        $this->assertSame(100, $player->getLife());
        // Ancre remise a maintenant : la regen ne s'accumule pas au-dela du plein.
        $this->assertEqualsWithDelta(time(), $player->getLifeUpdatedAt()->getTimestamp(), 2);
    }

    public function testRefreshDoesNothingInCombat(): void
    {
        $player = $this->buildPlayer(30, 100, new \DateTimeImmutable('-3600 seconds'));
        $player->setFight(new Fight());

        $this->assertSame(0, $this->manager->refresh($player));
        $this->assertSame(30, $player->getLife());
    }

    public function testRefreshDoesNothingWhenDead(): void
    {
        $player = $this->buildPlayer(0, 100, new \DateTimeImmutable('-3600 seconds'));

        $this->assertSame(0, $this->manager->refresh($player));
        $this->assertSame(0, $player->getLife());
    }

    public function testAnchorResetsTimestampToNow(): void
    {
        $player = $this->buildPlayer(30, 100, new \DateTimeImmutable('-3600 seconds'));

        $this->manager->anchor($player);

        $this->assertEqualsWithDelta(time(), $player->getLifeUpdatedAt()->getTimestamp(), 2);
    }

    public function testSecondsUntilNextPoint(): void
    {
        $this->assertNull($this->manager->secondsUntilNextPoint($this->buildPlayer(100)));

        // 10 s ecoulees a 12 s/PV : 2 s avant le prochain point.
        $player = $this->buildPlayer(50, 100, new \DateTimeImmutable('-10 seconds'));
        $this->assertEqualsWithDelta(2, $this->manager->secondsUntilNextPoint($player), 2);
    }

    public function testSecondsUntilNextPointNullInCombat(): void
    {
        $player = $this->buildPlayer(50, 100, new \DateTimeImmutable('-10 seconds'));
        $player->setFight(new Fight());

        $this->assertNull($this->manager->secondsUntilNextPoint($player));
    }

    public function testSecondsUntilFull(): void
    {
        $this->assertNull($this->manager->secondsUntilFull($this->buildPlayer(100)));

        // 40 PV manquants a 12 s/PV, ancre fraiche : ~ 12 + 39*12 = 480 s.
        $player = $this->buildPlayer(60, 100, new \DateTimeImmutable());
        $this->assertEqualsWithDelta(480, $this->manager->secondsUntilFull($player), 13);
    }

    public function testRegenSecondsReadsParameterOverride(): void
    {
        $parameter = new Parameter();
        $parameter->setName(LifeRegenManager::PARAM_REGEN_SECONDS);
        $parameter->setValue('60');
        $this->parameterRepository->method('findOneBy')->willReturn($parameter);

        $this->assertSame(60, $this->manager->getRegenSeconds());

        // 120 s ecoulees a 60 s/PV = 2 points.
        $player = $this->buildPlayer(10, 100, new \DateTimeImmutable('-120 seconds'));
        $this->assertSame(2, $this->manager->refresh($player));
    }

    public function testRegenSecondsFallsBackToDefault(): void
    {
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        $this->assertSame(LifeRegenManager::DEFAULT_REGEN_SECONDS, $this->manager->getRegenSeconds());
    }

    /**
     * MJ : le plein est refait des la lecture, sans attendre la regen.
     */
    public function testGameMasterIsHealedInstantly(): void
    {
        $player = $this->buildPlayer(4, 100, new \DateTimeImmutable('-1 second'));
        $player->setGameMaster(true);

        $granted = $this->manager->refresh($player);

        $this->assertSame(96, $granted);
        $this->assertSame(100, $player->getLife());
        $this->assertNull($this->manager->secondsUntilNextPoint($player));
        $this->assertNull($this->manager->secondsUntilFull($player));
    }

    /**
     * Les deux exclusions valent pour le MJ comme pour les autres : en combat
     * les PV appartiennent au combat, et la mort passe par le respawn.
     */
    public function testGameMasterIsNotHealedInFightNorWhenDead(): void
    {
        $inFight = $this->buildPlayer(10, 100, new \DateTimeImmutable('-1 hour'));
        $inFight->setGameMaster(true);
        $inFight->setFight(new Fight());

        $this->assertSame(0, $this->manager->refresh($inFight));
        $this->assertSame(10, $inFight->getLife());

        $dead = $this->buildPlayer(0, 100, new \DateTimeImmutable('-1 hour'));
        $dead->setGameMaster(true);

        $this->assertSame(0, $this->manager->refresh($dead));
        $this->assertSame(0, $dead->getLife());
    }
}
