<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerBestiary;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Zone;
use App\Entity\Game\Monster;
use App\GameEngine\Fight\Handler\FightHandler;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\HuntService;
use App\GameEngine\Zone\ZoneActionException;
use App\GameEngine\Zone\ZoneTravelService;
use App\Repository\MobRepository;
use App\Repository\PlayerBestiaryRepository;
use App\Repository\PlayerJournalEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HuntServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private ActionEnergyManager&MockObject $actionEnergyManager;
    private ZoneTravelService&MockObject $zoneTravelService;
    private MobRepository&MockObject $mobRepository;
    private FightHandler&MockObject $fightHandler;
    private PlayerBestiaryRepository&MockObject $bestiaryRepository;
    private PlayerJournalEntryRepository&MockObject $journalRepository;
    private HuntService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $this->parameterRepository],
        ]);

        $this->actionEnergyManager = $this->createMock(ActionEnergyManager::class);
        $this->zoneTravelService = $this->createMock(ZoneTravelService::class);
        $this->mobRepository = $this->createMock(MobRepository::class);
        $this->fightHandler = $this->createMock(FightHandler::class);
        $this->bestiaryRepository = $this->createMock(PlayerBestiaryRepository::class);
        $this->journalRepository = $this->createMock(PlayerJournalEntryRepository::class);

        $this->service = new HuntService(
            $this->entityManager,
            $this->actionEnergyManager,
            $this->zoneTravelService,
            $this->mobRepository,
            $this->fightHandler,
            $this->bestiaryRepository,
            $this->journalRepository,
        );
    }

    private function buildZone(string $slug, bool $safe = false): Zone
    {
        return (new Zone())->setSlug($slug)->setName(ucfirst($slug))->setIsSafe($safe);
    }

    private function buildPlayerIn(Zone $zone): Player
    {
        $player = new Player();
        $player->setCurrentZone($zone);

        return $player;
    }

    private function buildMonster(int $id, string $name, string $slug): Monster
    {
        $monster = new Monster();
        $monster->setName($name);
        $monster->setSlug($slug);
        $reflection = new \ReflectionProperty(Monster::class, 'id');
        $reflection->setValue($monster, $id);

        return $monster;
    }

    private function buildMob(Monster $monster): Mob
    {
        $mob = new Mob();
        $mob->setMonster($monster);

        return $mob;
    }

    public function testRefusesWhileTraveling(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('foret'));
        $player->setTravelToZone($this->buildZone('mines'));

        $this->expectException(ZoneActionException::class);
        $this->expectExceptionMessage('game.zone.hunt.error.traveling');
        $this->service->hunt($player, $this->buildMonster(1, 'Loup', 'wolf'));
    }

    public function testRefusesDuringFight(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('foret'));
        $player->setFight($this->createMock(Fight::class));

        $this->expectExceptionMessage('game.zone.hunt.error.in_fight');
        $this->service->hunt($player, $this->buildMonster(1, 'Loup', 'wolf'));
    }

    public function testRefusesWithoutZone(): void
    {
        $this->expectExceptionMessage('game.zone.hunt.error.no_zone');
        $this->service->hunt(new Player(), $this->buildMonster(1, 'Loup', 'wolf'));
    }

    public function testRefusesInSafeZone(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('village', true));

        $this->expectExceptionMessage('game.zone.hunt.error.safe_zone');
        $this->service->hunt($player, $this->buildMonster(1, 'Loup', 'wolf'));
    }

    public function testRefusesUnknownTargetNotInBestiary(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('foret'));
        $monster = $this->buildMonster(1, 'Loup', 'wolf');
        $this->bestiaryRepository->method('findOneByPlayerAndMonster')->willReturn(null);
        $this->actionEnergyManager->expects($this->never())->method('spend');

        $this->expectExceptionMessage('game.zone.hunt.error.unknown_target');
        $this->service->hunt($player, $monster);
    }

    public function testRefusesWhenPreyUnavailable(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('foret'));
        $monster = $this->buildMonster(1, 'Loup', 'wolf');
        $this->bestiaryRepository->method('findOneByPlayerAndMonster')
            ->willReturn($this->createMock(PlayerBestiary::class));
        $this->mobRepository->method('findAvailableInZoneForMonster')->willReturn(null);
        $this->actionEnergyManager->expects($this->never())->method('spend');

        $this->expectExceptionMessage('game.zone.hunt.error.no_prey');
        $this->service->hunt($player, $monster);
    }

    public function testSpendsEnergyStartsFightAndWritesJournal(): void
    {
        $zone = $this->buildZone('foret');
        $player = $this->buildPlayerIn($zone);
        $monster = $this->buildMonster(1, 'Loup des bois', 'forest_wolf');
        $mob = $this->buildMob($monster);
        $fight = $this->createMock(Fight::class);

        $this->bestiaryRepository->method('findOneByPlayerAndMonster')
            ->willReturn($this->createMock(PlayerBestiary::class));
        $this->mobRepository->method('findAvailableInZoneForMonster')->with($zone, $monster)->willReturn($mob);

        $this->actionEnergyManager->expects($this->once())
            ->method('spend')->with($player, HuntService::DEFAULT_COST, false);
        $this->fightHandler->expects($this->once())->method('startFight')->with($player, $mob)->willReturn($fight);
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(PlayerJournalEntry::class));
        $this->entityManager->expects($this->once())->method('flush');
        $this->journalRepository->expects($this->once())->method('enforceEntryLimit')->with($player);

        $result = $this->service->hunt($player, $monster);

        $this->assertSame($fight, $result);
    }

    public function testGetHuntTargetsReturnsEncounteredMonstersPresentInZone(): void
    {
        $zone = $this->buildZone('foret');
        $player = $this->buildPlayerIn($zone);

        $wolf = $this->buildMonster(1, 'Loup', 'wolf');
        $bear = $this->buildMonster(2, 'Ours', 'bear');
        $slime = $this->buildMonster(3, 'Slime', 'slime');

        // Deux mobs de loup (doublon), un ours, un slime.
        $this->mobRepository->method('findAvailableInZone')->with($zone)->willReturn([
            $this->buildMob($wolf),
            $this->buildMob($wolf),
            $this->buildMob($bear),
            $this->buildMob($slime),
        ]);
        // Seuls loup et ours sont au bestiaire (slime jamais rencontre).
        $this->bestiaryRepository->method('findMonsterIdsByPlayer')->with($player)->willReturn([1, 2]);

        $targets = $this->service->getHuntTargets($player, $zone);

        $slugs = array_map(static fn (Monster $m): string => $m->getSlug(), $targets);
        // Distinct + tri alphabetique par nom : Loup avant Ours.
        $this->assertSame(['wolf', 'bear'], $slugs);
    }

    public function testGetHuntTargetsEmptyInSafeZone(): void
    {
        $zone = $this->buildZone('village', true);
        $player = $this->buildPlayerIn($zone);
        $this->mobRepository->expects($this->never())->method('findAvailableInZone');

        $this->assertSame([], $this->service->getHuntTargets($player, $zone));
    }

    public function testGetHuntTargetsEmptyWhenNothingEncountered(): void
    {
        $zone = $this->buildZone('foret');
        $player = $this->buildPlayerIn($zone);
        $this->mobRepository->method('findAvailableInZone')->willReturn([$this->buildMob($this->buildMonster(1, 'Loup', 'wolf'))]);
        $this->bestiaryRepository->method('findMonsterIdsByPlayer')->willReturn([]);

        $this->assertSame([], $this->service->getHuntTargets($player, $zone));
    }

    public function testHuntCostReadsParameterOverride(): void
    {
        $parameter = new Parameter();
        $parameter->setName(HuntService::PARAM_COST);
        $parameter->setValue('7');
        $this->parameterRepository->method('findOneBy')->willReturn($parameter);

        $this->assertSame(7, $this->service->getHuntCost());
    }

    public function testHuntCostFallsBackToDefault(): void
    {
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        $this->assertSame(HuntService::DEFAULT_COST, $this->service->getHuntCost());
    }
}
