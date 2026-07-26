<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Player;
use App\Entity\App\TimeTrial;
use App\Entity\App\TimeTrialRun;
use App\Entity\App\Zone;
use App\Enum\TimeTrialStatus;
use App\Event\Zone\PlayerTraveledEvent;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\NotEnoughActionEnergyException;
use App\GameEngine\Zone\TimeTrialService;
use App\GameEngine\Zone\ZoneActionException;
use App\Repository\TimeTrialRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TimeTrialServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private TimeTrialRunRepository&MockObject $runRepository;
    private ActionEnergyManager&MockObject $energyManager;
    private TimeTrialService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->runRepository = $this->createMock(TimeTrialRunRepository::class);
        $this->energyManager = $this->createMock(ActionEnergyManager::class);

        $this->service = new TimeTrialService(
            $this->entityManager,
            $this->runRepository,
            $this->energyManager,
            new NullLogger(),
        );
    }

    private function zone(string $slug): Zone
    {
        return (new Zone())->setSlug($slug)->setName(ucfirst($slug));
    }

    /**
     * @param list<string> $checkpoints
     */
    private function trial(Zone $start, array $checkpoints = ['foret', 'mines']): TimeTrial
    {
        return (new TimeTrial())
            ->setSlug('parcours')
            ->setName('Parcours')
            ->setStartZone($start)
            ->setCheckpoints($checkpoints);
    }

    /**
     * Un joueur avec son identifiant : le service journalise `getId()`, non
     * nullable, et un joueur sans identifiant n'existe pas en production.
     */
    private function playerIn(Zone $zone, int $id = 42): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->setCurrentZone($zone);

        return $player;
    }

    public function testStartCreatesARunAndSpendsEnergy(): void
    {
        $hub = $this->zone('village');
        $player = $this->playerIn($hub);
        $trial = $this->trial($hub)->setEnergyCost(8);

        $this->runRepository->method('findRunning')->willReturn(null);
        $this->energyManager->expects($this->once())->method('spend')->with($player, 8, false);
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(TimeTrialRun::class));
        $this->entityManager->expects($this->once())->method('flush');

        $run = $this->service->start($player, $trial);

        $this->assertTrue($run->isRunning());
        $this->assertSame(0, $run->getReachedIndex());
        $this->assertSame('foret', $run->nextCheckpoint());
    }

    public function testStartRefusesAwayFromTheStartingLine(): void
    {
        $player = $this->playerIn($this->zone('marais'));

        $this->expectExceptionMessage('game.time_trial.error.wrong_zone');
        $this->service->start($player, $this->trial($this->zone('village')));
    }

    public function testStartRefusesWhileTraveling(): void
    {
        $hub = $this->zone('village');
        $player = $this->playerIn($hub);
        $player->setTravelToZone($this->zone('foret'));
        $player->setTravelArrivesAt(new \DateTimeImmutable('+5 minutes'));

        $this->expectExceptionMessage('game.time_trial.error.traveling');
        $this->service->start($player, $this->trial($hub));
    }

    public function testStartRefusesASecondRun(): void
    {
        $hub = $this->zone('village');
        $player = $this->playerIn($hub);
        $trial = $this->trial($hub);

        $this->runRepository->method('findRunning')->willReturn(new TimeTrialRun($player, $trial));

        $this->expectExceptionMessage('game.time_trial.error.already_running');
        $this->service->start($player, $trial);
    }

    public function testStartRefusesACourseWithoutCheckpoints(): void
    {
        $hub = $this->zone('village');

        $this->expectExceptionMessage('game.time_trial.error.no_checkpoint');
        $this->service->start($this->playerIn($hub), $this->trial($hub, []));
    }

    /**
     * L'energie part avant la creation : un depart qu'on ne peut pas payer ne
     * doit pas laisser de tentative ouverte derriere lui.
     */
    public function testNoRunIsCreatedWhenEnergyIsMissing(): void
    {
        $hub = $this->zone('village');
        $player = $this->playerIn($hub);

        $this->runRepository->method('findRunning')->willReturn(null);
        $this->energyManager->method('spend')->willThrowException(new NotEnoughActionEnergyException('game.zone.energy.error.not_enough'));
        $this->entityManager->expects($this->never())->method('persist');

        $this->expectException(NotEnoughActionEnergyException::class);
        $this->service->start($player, $this->trial($hub));
    }

    public function testArrivingAtTheExpectedCheckpointAdvancesTheRun(): void
    {
        $hub = $this->zone('village');
        $player = $this->playerIn($hub);
        $run = new TimeTrialRun($player, $this->trial($hub));

        $this->runRepository->method('findRunning')->willReturn($run);

        $this->service->onPlayerTraveled(new PlayerTraveledEvent($player, $this->zone('foret'), $hub));

        $this->assertSame(1, $run->getReachedIndex());
        $this->assertSame('mines', $run->nextCheckpoint());
        $this->assertTrue($run->isRunning());
    }

    /**
     * Le parcours impose un ordre, pas un itineraire : traverser une zone qui
     * n'est pas la prochaine etape est sans effet. C'est ce qui laisse au
     * joueur le choix de sa route.
     */
    public function testCrossingAnUnrelatedZoneChangesNothing(): void
    {
        $hub = $this->zone('village');
        $player = $this->playerIn($hub);
        $run = new TimeTrialRun($player, $this->trial($hub));

        $this->runRepository->method('findRunning')->willReturn($run);
        $this->entityManager->expects($this->never())->method('flush');

        $this->service->onPlayerTraveled(new PlayerTraveledEvent($player, $this->zone('marais'), $hub));

        $this->assertSame(0, $run->getReachedIndex());
    }

    public function testReachingTheLastCheckpointStopsTheClock(): void
    {
        $hub = $this->zone('village');
        $player = $this->playerIn($hub);
        $run = new TimeTrialRun($player, $this->trial($hub, ['foret']), new \DateTimeImmutable('-90 seconds'));

        $this->runRepository->method('findRunning')->willReturn($run);

        $this->service->onPlayerTraveled(new PlayerTraveledEvent($player, $this->zone('foret'), $hub));

        $this->assertSame(TimeTrialStatus::Finished, $run->getStatus());
        $this->assertNotNull($run->getFinishedAt());
        $this->assertEqualsWithDelta(90, $run->getElapsedSeconds(), 2);
    }

    public function testTravelingWithoutARunIsANoop(): void
    {
        $player = $this->playerIn($this->zone('village'));

        $this->runRepository->method('findRunning')->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $this->service->onPlayerTraveled(new PlayerTraveledEvent($player, $this->zone('foret'), null));
    }

    /**
     * Le depassement est constate paresseusement, comme l'arrivee de voyage :
     * une tentative oubliee ne doit pas exiger un cron pour liberer le joueur.
     */
    public function testAnOverdueRunExpiresOnTheNextLook(): void
    {
        $hub = $this->zone('village');
        $player = $this->playerIn($hub);
        $trial = $this->trial($hub)->setTimeLimitSeconds(60);
        $run = new TimeTrialRun($player, $trial, new \DateTimeImmutable('-2 hours'));

        $this->runRepository->method('findRunning')->willReturn($run);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertNull($this->service->settleRunning($player));
        $this->assertSame(TimeTrialStatus::Expired, $run->getStatus());
    }

    public function testAbandonClosesTheRun(): void
    {
        $hub = $this->zone('village');
        $player = $this->playerIn($hub);
        $run = new TimeTrialRun($player, $this->trial($hub));

        $this->runRepository->method('findRunning')->willReturn($run);

        $this->service->abandon($player);

        $this->assertSame(TimeTrialStatus::Abandoned, $run->getStatus());
        $this->assertNotNull($run->getFinishedAt());
    }

    public function testAbandonWithoutARunIsRefused(): void
    {
        $this->runRepository->method('findRunning')->willReturn(null);

        $this->expectException(ZoneActionException::class);
        $this->service->abandon($this->playerIn($this->zone('village')));
    }

    public function testTheServiceListensToArrivals(): void
    {
        $this->assertArrayHasKey(PlayerTraveledEvent::NAME, TimeTrialService::getSubscribedEvents());
    }
}
