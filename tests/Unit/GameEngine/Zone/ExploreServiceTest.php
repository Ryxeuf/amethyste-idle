<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\Game\Monster;
use App\GameEngine\Fight\Handler\FightHandler;
use App\GameEngine\Progression\ActionYieldResolver;
use App\GameEngine\World\GameTimeService;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\ExploreResult;
use App\GameEngine\Zone\ExploreService;
use App\GameEngine\Zone\ZoneActionException;
use App\GameEngine\Zone\ZoneTravelService;
use App\Repository\MobRepository;
use App\Repository\PlayerJournalEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExploreServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private EntityRepository&MockObject $pnjRepository;
    private ActionEnergyManager&MockObject $actionEnergyManager;
    private ZoneTravelService&MockObject $zoneTravelService;
    private MobRepository&MockObject $mobRepository;
    private FightHandler&MockObject $fightHandler;
    private PlayerJournalEntryRepository&MockObject $journalRepository;
    private GameTimeService&MockObject $gameTimeService;
    private bool $night = false;

    /** @var ExploreService&object{rolls: list<int>} */
    private ExploreService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->pnjRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $this->parameterRepository],
            [Pnj::class, $this->pnjRepository],
        ]);

        $this->actionEnergyManager = $this->createMock(ActionEnergyManager::class);
        $this->zoneTravelService = $this->createMock(ZoneTravelService::class);
        $this->mobRepository = $this->createMock(MobRepository::class);
        $this->fightHandler = $this->createMock(FightHandler::class);
        $this->journalRepository = $this->createMock(PlayerJournalEntryRepository::class);
        $this->gameTimeService = $this->createMock(GameTimeService::class);
        $this->gameTimeService->method('isNight')->willReturnCallback(fn (): bool => $this->night);

        $this->service = new class($this->entityManager, $this->actionEnergyManager, $this->zoneTravelService, $this->mobRepository, $this->fightHandler, $this->journalRepository, $this->gameTimeService, new ActionYieldResolver()) extends ExploreService {
            /** @var list<int> */
            public array $rolls = [];
            private int $rollIndex = 0;

            protected function roll(int $max): int
            {
                return $this->rolls[$this->rollIndex++] ?? 1;
            }
        };
    }

    private function buildZone(string $slug, bool $safe = false, ?array $config = null): Zone
    {
        return (new Zone())->setSlug($slug)->setName(ucfirst($slug))->setIsSafe($safe)->setExploreConfig($config);
    }

    private function buildPlayerIn(Zone $zone): Player
    {
        $player = new Player();
        $player->setCurrentZone($zone);
        $player->setGils(0);

        return $player;
    }

    private function buildMob(string $slug): Mob
    {
        $monster = new Monster();
        $monster->setName(ucfirst($slug));
        $monster->setSlug($slug);
        $mob = new Mob();
        $mob->setMonster($monster);

        return $mob;
    }

    public function testRefusesWhileTraveling(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('foret'));
        $player->setTravelToZone($this->buildZone('mines'));

        $this->expectException(ZoneActionException::class);
        $this->expectExceptionMessage('game.zone.explore.error.traveling');
        $this->service->explore($player);
    }

    public function testRefusesDuringFight(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('foret'));
        $player->setFight($this->createMock(Fight::class));

        $this->expectExceptionMessage('game.zone.explore.error.in_fight');
        $this->service->explore($player);
    }

    public function testRefusesWithoutZone(): void
    {
        $this->expectExceptionMessage('game.zone.explore.error.no_zone');
        $this->service->explore(new Player());
    }

    public function testSpendsEnergyBeforeDrawing(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('foret'));
        $this->service->rolls = [100]; // nothing (81..100 avec les poids par defaut)

        $this->actionEnergyManager->expects($this->once())
            ->method('spend')->with($player, ExploreService::DEFAULT_COST, false);

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_NOTHING, $result->event);
    }

    public function testMobEncounterStartsFightAndWritesJournal(): void
    {
        $zone = $this->buildZone('foret');
        $player = $this->buildPlayerIn($zone);
        $this->service->rolls = [1, 1]; // mob (1..50), puis 1er mob de la liste

        $monster = new Monster();
        $monster->setName('Loup des bois');
        $monster->setSlug('forest_wolf');
        $mob = new Mob();
        $mob->setMonster($monster);

        $fight = $this->createMock(Fight::class);
        $this->mobRepository->method('findAvailableInZone')->with($zone)->willReturn([$mob]);
        $this->fightHandler->expects($this->once())->method('startFight')->with($player, $mob)->willReturn($fight);
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(PlayerJournalEntry::class));
        $this->journalRepository->expects($this->once())->method('enforceEntryLimit')->with($player);

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_MOB, $result->event);
        $this->assertSame($fight, $result->fight);
        $this->assertSame('Loup des bois', $result->messageParams['%monster%']);
    }

    public function testMobEncounterFallsBackToNothingWhenNoMobAvailable(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('foret'));
        $this->service->rolls = [1]; // mob

        $this->mobRepository->method('findAvailableInZone')->willReturn([]);
        $this->fightHandler->expects($this->never())->method('startFight');

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_NOTHING, $result->event);
        $this->assertNull($result->fight);
    }

    public function testChestGrantsGilsWithinRange(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('foret'));
        $this->service->rolls = [55, 7]; // chest (51..60), puis 5 + (7-1) = 11 gils

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_CHEST, $result->event);
        $this->assertSame(11, $result->messageParams['%gils%']);
        $this->assertSame(11, $player->getGils());
    }

    public function testPnjEncounterUsesZonePnjs(): void
    {
        $zone = $this->buildZone('village', false, ['weights' => ['mob' => 0, 'chest' => 0, 'harvest' => 0, 'pnj' => 100, 'nothing' => 0]]);
        $player = $this->buildPlayerIn($zone);
        $this->service->rolls = [1, 1];

        $pnj = new Pnj();
        $pnj->setName('Chloe l\'Exploratrice');
        $this->pnjRepository->method('findBy')->with(['zone' => $zone])->willReturn([$pnj]);

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_PNJ, $result->event);
        $this->assertSame('Chloe l\'Exploratrice', $result->messageParams['%pnj%']);
    }

    public function testSafeZoneNeverDrawsMobEncounter(): void
    {
        // Config degeneree 100% mob sur une zone sure : poids mob force a 0 -> nothing.
        $zone = $this->buildZone('village', true, ['weights' => ['mob' => 100, 'chest' => 0, 'harvest' => 0, 'pnj' => 0, 'nothing' => 0]]);
        $player = $this->buildPlayerIn($zone);
        $this->service->rolls = [1];

        $this->mobRepository->expects($this->never())->method('findAvailableInZone');

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_NOTHING, $result->event);
    }

    public function testZoneConfigOverridesDefaultWeights(): void
    {
        $zone = $this->buildZone('foret', false, ['weights' => ['mob' => 0, 'chest' => 100, 'harvest' => 0, 'pnj' => 0, 'nothing' => 0], 'chest_gils_min' => 50, 'chest_gils_max' => 50]);
        $player = $this->buildPlayerIn($zone);
        $this->service->rolls = [1];

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_CHEST, $result->event);
        $this->assertSame(50, $player->getGils());
    }

    public function testNightVariantOverridesWeightsAndChestGils(): void
    {
        // Jour : coffre 10 gils. Nuit : le sous-bloc `night` force encore un
        // coffre mais a 40 gils (ZON-17).
        $config = [
            'weights' => ['mob' => 0, 'chest' => 100, 'harvest' => 0, 'pnj' => 0, 'nothing' => 0],
            'chest_gils_min' => 10, 'chest_gils_max' => 10,
            'night' => [
                'weights' => ['mob' => 0, 'chest' => 100, 'harvest' => 0, 'pnj' => 0, 'nothing' => 0],
                'chest_gils_min' => 40, 'chest_gils_max' => 40,
            ],
        ];

        $this->night = true;
        $zone = $this->buildZone('foret', false, $config);
        $player = $this->buildPlayerIn($zone);
        $this->service->rolls = [1];

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_CHEST, $result->event);
        $this->assertSame(40, $player->getGils());
    }

    public function testDayIgnoresNightVariant(): void
    {
        $config = [
            'weights' => ['mob' => 0, 'chest' => 100, 'harvest' => 0, 'pnj' => 0, 'nothing' => 0],
            'chest_gils_min' => 10, 'chest_gils_max' => 10,
            'night' => ['chest_gils_min' => 40, 'chest_gils_max' => 40],
        ];

        $this->night = false;
        $zone = $this->buildZone('foret', false, $config);
        $player = $this->buildPlayerIn($zone);
        $this->service->rolls = [1];

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_CHEST, $result->event);
        $this->assertSame(10, $player->getGils());
    }

    public function testNightMobPoolRestrictsEncounters(): void
    {
        // Vivier de la zone : loup (jour) + squelette (nuit). La nuit, le pool
        // `mob_slugs: [skeleton]` restreint la rencontre au squelette.
        $wolf = $this->buildMob('wolf');
        $skeleton = $this->buildMob('skeleton');
        $this->mobRepository->method('findAvailableInZone')->willReturn([$wolf, $skeleton]);
        $this->fightHandler->method('startFight')->willReturn($this->createMock(Fight::class));

        $config = [
            'weights' => ['mob' => 100, 'chest' => 0, 'harvest' => 0, 'pnj' => 0, 'nothing' => 0],
            'night' => ['mob_slugs' => ['skeleton']],
        ];

        $this->night = true;
        $zone = $this->buildZone('foret', false, $config);
        $player = $this->buildPlayerIn($zone);
        $this->service->rolls = [1, 1]; // event mob, puis 1er mob du pool filtre

        $result = $this->service->explore($player);

        $this->assertSame(ExploreResult::EVENT_MOB, $result->event);
        $this->assertSame('Skeleton', $result->messageParams['%monster%']);
    }

    public function testExploreCostReadsParameterOverride(): void
    {
        $parameter = new Parameter();
        $parameter->setName(ExploreService::PARAM_COST);
        $parameter->setValue('8');
        $this->parameterRepository->method('findOneBy')->willReturn($parameter);

        $this->assertSame(8, $this->service->getExploreCost());
    }

    public function testExploreCostFallsBackToDefault(): void
    {
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        $this->assertSame(ExploreService::DEFAULT_COST, $this->service->getExploreCost());
    }
}
