<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\GameEngine\Retention\WeeklyAttendanceService;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\NotEnoughActionEnergyException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionEnergyManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private ActionEnergyManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->with(Parameter::class)->willReturn($this->parameterRepository);
        // RET-04 : l'assiduite se compte dans `spend()`. Ces tests portent sur
        // l'energie, pas sur la presence — un service muet suffit.
        $this->manager = new ActionEnergyManager($this->entityManager, $this->createMock(WeeklyAttendanceService::class));
    }

    private function buildPlayer(int $energy, int $max = 100, ?\DateTimeImmutable $updatedAt = null): Player
    {
        $player = new Player();
        $player->setActionEnergy($energy);
        $player->setMaxActionEnergy($max);
        $player->setActionEnergyUpdatedAt($updatedAt);

        return $player;
    }

    public function testRefreshInitializesTimestampOnFirstCall(): void
    {
        $player = $this->buildPlayer(50);

        $this->assertSame(0, $this->manager->refresh($player));
        $this->assertNotNull($player->getActionEnergyUpdatedAt());
        $this->assertSame(50, $player->getActionEnergy());
    }

    public function testRefreshGrantsElapsedPoints(): void
    {
        // 25 minutes ecoulees a 360 s/point = 4 points + reliquat 60 s.
        $player = $this->buildPlayer(50, 100, new \DateTimeImmutable('-1500 seconds'));

        $granted = $this->manager->refresh($player);

        $this->assertSame(4, $granted);
        $this->assertSame(54, $player->getActionEnergy());
        // Reliquat conserve : le curseur avance de 4 * 360 s exactement.
        $this->assertEqualsWithDelta(time() - 60, $player->getActionEnergyUpdatedAt()->getTimestamp(), 2);
    }

    public function testRefreshCapsAtMax(): void
    {
        $player = $this->buildPlayer(98, 100, new \DateTimeImmutable('-3600 seconds'));

        $granted = $this->manager->refresh($player);

        $this->assertSame(2, $granted);
        $this->assertSame(100, $player->getActionEnergy());
    }

    public function testRefreshDoesNothingWhenFull(): void
    {
        $player = $this->buildPlayer(100, 100, new \DateTimeImmutable('-3600 seconds'));

        $this->assertSame(0, $this->manager->refresh($player));
        $this->assertSame(100, $player->getActionEnergy());
        // Timer remis a maintenant : la regen ne s'accumule pas au-dela du plein.
        $this->assertEqualsWithDelta(time(), $player->getActionEnergyUpdatedAt()->getTimestamp(), 2);
    }

    public function testSpendConsumesEnergyAndStartsTimerFromFull(): void
    {
        $player = $this->buildPlayer(100, 100, new \DateTimeImmutable('-50 seconds'));

        $this->entityManager->expects($this->once())->method('flush');

        $this->manager->spend($player, 5);

        $this->assertSame(95, $player->getActionEnergy());
        $this->assertEqualsWithDelta(time(), $player->getActionEnergyUpdatedAt()->getTimestamp(), 2);
    }

    public function testSpendRefusesWhenNotEnough(): void
    {
        $player = $this->buildPlayer(3, 100, new \DateTimeImmutable('-10 seconds'));

        $this->expectException(NotEnoughActionEnergyException::class);
        $this->manager->spend($player, 5);
    }

    public function testSpendAppliesDueRegenBeforeChecking(): void
    {
        // 3 points au compteur mais 2 points regeneres dus (720 s a 360 s/pt) : 5 disponibles.
        $player = $this->buildPlayer(3, 100, new \DateTimeImmutable('-720 seconds'));

        $this->manager->spend($player, 5);

        $this->assertSame(0, $player->getActionEnergy());
    }

    public function testSpendRejectsNegativeCost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->spend($this->buildPlayer(50), -1);
    }

    public function testSecondsUntilNextPoint(): void
    {
        $this->assertNull($this->manager->secondsUntilNextPoint($this->buildPlayer(100)));

        $player = $this->buildPlayer(50, 100, new \DateTimeImmutable('-100 seconds'));
        $this->assertEqualsWithDelta(260, $this->manager->secondsUntilNextPoint($player), 2);
    }

    public function testRegenSecondsReadsParameterOverride(): void
    {
        $parameter = new Parameter();
        $parameter->setName(ActionEnergyManager::PARAM_REGEN_SECONDS);
        $parameter->setValue('60');
        $this->parameterRepository->method('findOneBy')->willReturn($parameter);

        $this->assertSame(60, $this->manager->getRegenSeconds());

        // 120 s ecoulees a 60 s/point = 2 points.
        $player = $this->buildPlayer(10, 100, new \DateTimeImmutable('-120 seconds'));
        $this->assertSame(2, $this->manager->refresh($player));
    }

    public function testRegenSecondsFallsBackToDefault(): void
    {
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        $this->assertSame(ActionEnergyManager::DEFAULT_REGEN_SECONDS, $this->manager->getRegenSeconds());
    }

    // -----------------------------------------------------------------
    // FOY-17 — la depense d'energie est le point de mesure de l'activite
    // -----------------------------------------------------------------

    /**
     * `spend()` est le passage oblige de toute action qui pese sur le monde.
     * C'est donc la, et nulle part ailleurs, que se datent l'activite et se
     * cumule la charge — se connecter ne passe pas par ici, et c'est le point
     * (BALANCE § 22.5 : on compte la charge, pas les tetes).
     */
    public function testSpendStampsActivityAndAccumulatesSpentEnergy(): void
    {
        $player = $this->buildPlayer(50);
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        $this->manager->spend($player, 3, false);

        $this->assertNotNull($player->getLastActivityAt());
        $this->assertSame(3, $player->getActionEnergySpentTotal());
    }

    public function testSpentEnergyAccumulatesAcrossActions(): void
    {
        $player = $this->buildPlayer(50);
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        $this->manager->spend($player, 3, false);
        $this->manager->spend($player, 5, false);
        $this->manager->spend($player, 2, false);

        $this->assertSame(10, $player->getActionEnergySpentTotal());
    }

    /**
     * Une depense nulle ne vaut pas activite : elle ne pese sur rien, et la
     * compter permettrait de se declarer actif sans jamais toucher au monde.
     */
    public function testAZeroCostActionIsNotActivity(): void
    {
        $player = $this->buildPlayer(50);
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        $this->manager->spend($player, 0, false);

        $this->assertNull($player->getLastActivityAt());
        $this->assertSame(0, $player->getActionEnergySpentTotal());
    }

    /**
     * Une action refusee faute d'energie ne compte pas non plus.
     */
    public function testARefusedActionLeavesActivityUntouched(): void
    {
        $player = $this->buildPlayer(1);
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        try {
            $this->manager->spend($player, 5, false);
            $this->fail('La depense aurait du etre refusee.');
        } catch (NotEnoughActionEnergyException) {
            // attendu
        }

        $this->assertNull($player->getLastActivityAt());
        $this->assertSame(0, $player->getActionEnergySpentTotal());
    }
}
