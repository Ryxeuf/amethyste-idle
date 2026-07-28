<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Fight;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Zone;
use App\Entity\App\ZoneVein;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Event\Zone\ZoneGatherEvent;
use App\GameEngine\Economy\PurityDrawer;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Progression\ActionYieldResolver;
use App\GameEngine\World\WorldScaleService;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\GatherService;
use App\GameEngine\Zone\ZoneActionException;
use App\GameEngine\Zone\ZoneTravelService;
use App\Helper\InventoryHelper;
use App\Repository\PlayerJournalEntryRepository;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GatherServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private EntityRepository&MockObject $itemRepository;
    private ActionEnergyManager&MockObject $actionEnergyManager;
    private ZoneTravelService&MockObject $zoneTravelService;
    private ZoneVeinRepository&MockObject $veinRepository;
    private PlayerItemGenerator&MockObject $playerItemGenerator;
    private InventoryHelper&MockObject $inventoryHelper;
    private PlayerJournalEntryRepository&MockObject $journalRepository;
    private WorldScaleService&MockObject $worldScaleService;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    /** @var list<object> */
    private array $dispatched = [];

    /** @var GatherService&object{rolls: list<int>, currentTime: \DateTimeImmutable} */
    private GatherService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->itemRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $this->parameterRepository],
            [Item::class, $this->itemRepository],
        ]);

        $this->actionEnergyManager = $this->createMock(ActionEnergyManager::class);
        $this->zoneTravelService = $this->createMock(ZoneTravelService::class);
        $this->veinRepository = $this->createMock(ZoneVeinRepository::class);
        $this->playerItemGenerator = $this->createMock(PlayerItemGenerator::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        $this->journalRepository = $this->createMock(PlayerJournalEntryRepository::class);
        // FOY-17b : monde a l'echelle 1 par defaut — ces tests portent sur la
        // recolte, pas sur le dimensionnement.
        $this->worldScaleService = $this->createMock(WorldScaleService::class);
        $this->worldScaleService->method('current')->willReturn(1.0);
        $this->dispatched = [];
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
            $this->dispatched[] = $event;

            return $event;
        });

        $this->service = new class($this->entityManager, $this->actionEnergyManager, $this->zoneTravelService, $this->veinRepository, $this->playerItemGenerator, $this->inventoryHelper, $this->journalRepository, new ActionYieldResolver(), $this->worldScaleService, $this->eventDispatcher, $this->purityDrawer()) extends GatherService {
            /** @var list<int> */
            public array $rolls = [];
            public \DateTimeImmutable $currentTime;
            private int $rollIndex = 0;

            protected function roll(int $max): int
            {
                return $this->rolls[$this->rollIndex++] ?? 1;
            }

            protected function now(): \DateTimeImmutable
            {
                return $this->currentTime;
            }
        };
        $this->service->currentTime = new \DateTimeImmutable('2026-07-24 12:00:00');
    }

    /**
     * @param list<array<string, mixed>> $resources
     */
    private function buildZone(array $resources = [], bool $safe = false): Zone
    {
        $config = [] === $resources ? null : ['resources' => $resources];

        return (new Zone())->setSlug('mines')->setName('Mines profondes')->setIsSafe($safe)->setGatherConfig($config);
    }

    /**
     * @param list<array<string, mixed>> $resources
     */
    private function buildPlayerIn(array $resources = []): Player
    {
        $player = new Player();
        $player->setCurrentZone($this->buildZone($resources));

        return $player;
    }

    private function buildItem(int $id, string $slug, string $name): Item
    {
        $item = new Item();
        $item->setName($name);
        $item->setSlug($slug);
        $ref = new \ReflectionProperty(Item::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($item, $id);

        return $item;
    }

    private function ironResource(): array
    {
        return ['slug' => 'filon-de-fer', 'item' => 'ore-iron', 'profession' => 'mining', 'capacity' => 20, 'respawn_seconds' => 900, 'yield_min' => 1, 'yield_max' => 3];
    }

    public function testRefusesWhileTraveling(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $player->setTravelToZone($this->buildZone());

        $this->actionEnergyManager->expects($this->never())->method('spend');
        $this->expectException(ZoneActionException::class);
        $this->expectExceptionMessage('game.zone.gather.error.traveling');
        $this->service->gather($player, 'filon-de-fer');
    }

    public function testRefusesDuringFight(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $player->setFight($this->createMock(Fight::class));

        $this->expectExceptionMessage('game.zone.gather.error.in_fight');
        $this->service->gather($player, 'filon-de-fer');
    }

    public function testRefusesWithoutZone(): void
    {
        $this->expectExceptionMessage('game.zone.gather.error.no_zone');
        $this->service->gather(new Player(), 'filon-de-fer');
    }

    public function testRefusesUnknownResource(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);

        $this->actionEnergyManager->expects($this->never())->method('spend');
        $this->expectExceptionMessage('game.zone.gather.error.unknown_resource');
        $this->service->gather($player, 'filon-inexistant');
    }

    /**
     * ZON-37 — **une recolte n'echoue jamais** (GAME_ZONE_ACTIONS, loi 5).
     *
     * Le service refusait ici. La loi dit que la vitalite module le
     * **rendement**, pas l'acces, avec un plancher d'une unite : un filon a sec
     * rend peu, il ne ferme pas la porte. C'est ce qui protege le joueur
     * occasionnel de la saturation par les habitues.
     */
    public function testADepletedVeinStillYieldsTheFloorOfOneUnit(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $zone = $player->getCurrentZone();

        // Filon vide, epuise il y a 10 s : trop tot pour rendre une unite
        // (respawn 900 s pour 20 unites, soit 45 s par unite).
        $vein = new ZoneVein($zone, 'filon-de-fer', 0);
        $vein->setDepletedAt($this->service->currentTime->modify('-10 seconds'));
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn($vein);
        $this->itemRepository->method('findOneBy')->willReturn($this->buildItem(1, 'ore-iron', 'Minerai de fer'));

        $this->actionEnergyManager->expects($this->once())->method('spend');

        $result = $this->service->gather($player, 'filon-de-fer');

        $this->assertSame(1, $result->quantity, 'Le plancher d\'une unite s\'applique.');
    }

    public function testGatherGrantsItemsDecrementsSharedStockAndWritesJournal(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $item = $this->buildItem(7, 'ore-iron', 'Minerai de fer');
        $this->itemRepository->method('findOneBy')->with(['slug' => 'ore-iron'])->willReturn($item);

        // Aucun filon existant : cree paresseusement a la capacite (20).
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn(null);
        $this->service->rolls = [3]; // yield = 1 + (3 - 1) = 3

        $this->actionEnergyManager->expects($this->once())
            ->method('spend')->with($player, GatherService::DEFAULT_COST, false);
        $this->playerItemGenerator->expects($this->exactly(3))
            ->method('generateFromItemId')->with(7)->willReturn(new PlayerItem());
        $this->inventoryHelper->expects($this->exactly(3))->method('addItem');
        $this->entityManager->expects($this->atLeastOnce())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        $this->journalRepository->expects($this->once())->method('enforceEntryLimit')->with($player);

        $result = $this->service->gather($player, 'filon-de-fer');

        $this->assertSame(3, $result->quantity);
        $this->assertSame(17, $result->remainingStock);
        $this->assertSame('Minerai de fer', $result->itemName);
        $this->assertSame('game.zone.gather.result.success', $result->messageKey);
        $this->assertSame(3, $result->messageParams['%count%']);
    }

    /**
     * ZON-38 : la recolte redevient observable.
     *
     * L'evenement est ce qui rebranche l'influence de guilde — et, demain, le
     * sediment des foyers. Sans lui, la boucle la plus jouee du jeu ne
     * rapportait rien a la guilde, en silence, depuis le pivot.
     */
    public function testGatherAnnouncesTheHarvest(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $this->itemRepository->method('findOneBy')->willReturn($this->buildItem(7, 'ore-iron', 'Minerai de fer'));
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn(null);
        $this->playerItemGenerator->method('generateFromItemId')->willReturn(new PlayerItem());
        $this->service->rolls = [3];

        $this->service->gather($player, 'filon-de-fer');

        $events = array_values(array_filter(
            $this->dispatched,
            static fn (object $e): bool => $e instanceof ZoneGatherEvent,
        ));

        self::assertCount(1, $events);
        self::assertSame($player, $events[0]->getPlayer());
        self::assertSame($player->getCurrentZone(), $events[0]->getZone());
        self::assertSame('filon-de-fer', $events[0]->getVeinSlug());
        self::assertSame('ore-iron', $events[0]->getItemSlug());
        self::assertSame(3, $events[0]->getQuantity());
    }

    /**
     * Rendement par point d'energie : le budget d'energie reste egalitaire, c'est
     * ce qu'une action rapporte qui recompense l'investissement.
     */
    public function testGatherAppliesThePlayerYieldBonus(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $skill = new Skill();
        $skill->setActions(['yield' => ['gather_percent' => 50]]);
        $player->addSkill($skill);

        $this->itemRepository->method('findOneBy')->willReturn($this->buildItem(7, 'ore-iron', 'Minerai de fer'));
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn(null);
        $this->service->rolls = [3]; // yield brut 3, +50 % -> 4.5 arrondi a 5 (au plus proche)

        $this->inventoryHelper->expects($this->exactly(5))->method('addItem');

        $result = $this->service->gather($player, 'filon-de-fer');

        $this->assertSame(5, $result->quantity);
        $this->assertSame(15, $result->remainingStock);
    }

    /**
     * Le bonus augmente ce qu'une action rapporte, il ne permet pas de prendre
     * plus que ce que le filon contient : le stock partage reste le point de
     * tension de la ressource.
     */
    public function testYieldBonusCannotExceedTheSharedStock(): void
    {
        // Petit filon (capacite 3) : c'est la borne de stock qui mord ici, pas
        // la modulation par la vitalite.
        $player = $this->buildPlayerIn([['slug' => 'filon-de-fer', 'item' => 'ore-iron', 'profession' => 'mining', 'capacity' => 3, 'respawn_seconds' => 900, 'yield_min' => 1, 'yield_max' => 3]]);
        $skill = new Skill();
        $skill->setActions(['yield' => ['gather_percent' => 100]]);
        $player->addSkill($skill);

        $zone = $player->getCurrentZone();
        $vein = new ZoneVein($zone, 'filon-de-fer', 2); // deux restants sur trois
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn($vein);
        $this->itemRepository->method('findOneBy')->willReturn($this->buildItem(7, 'ore-iron', 'Minerai de fer'));
        $this->service->rolls = [3]; // 3 double a 6, module a 4 par la vitalite, borne a 2 par le stock

        $result = $this->service->gather($player, 'filon-de-fer');

        $this->assertSame(2, $result->quantity);
        $this->assertSame(0, $result->remainingStock);
    }

    /**
     * ZON-37 — la vitalite module le rendement (GAME_ZONE_ACTIONS, loi 5).
     *
     * Le stock ne servait qu'a **plafonner** : un filon a 18/20 et un filon a
     * 3/20 rendaient autant, et la rarete ne se voyait qu'au moment ou l'acces
     * se fermait. C'est ce signal continu que liront la purete (ECO-22) et la
     * Paleur (FOY-11) — sans lui, les deux jalons tourneraient a vide.
     */
    public function testAPressedVeinYieldsLessThanARestedOne(): void
    {
        $rested = $this->gatherFromVeinAt(18);
        $pressed = $this->gatherFromVeinAt(4);

        $this->assertGreaterThan(
            $pressed,
            $rested,
            'Un filon repose doit rendre davantage qu\'un filon presse, a tirage egal.',
        );
        $this->assertGreaterThanOrEqual(1, $pressed, 'Le plancher d\'une unite tient toujours.');
    }

    /**
     * Recolte une fois sur un filon dont la vitalite est fixee, avec des mocks
     * neufs : configurer deux fois le meme laisserait la premiere reponse
     * gagner, et les deux mesures seraient identiques par construction.
     */
    private function gatherFromVeinAt(int $stock): int
    {
        $veinRepository = $this->createMock(ZoneVeinRepository::class);
        $itemRepository = $this->createMock(EntityRepository::class);
        $parameterRepository = $this->createMock(EntityRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $parameterRepository],
            [Item::class, $itemRepository],
        ]);

        $worldScale = $this->createMock(WorldScaleService::class);
        $worldScale->method('current')->willReturn(1.0);

        $service = new class($entityManager, $this->createMock(ActionEnergyManager::class), $this->createMock(ZoneTravelService::class), $veinRepository, $this->createMock(PlayerItemGenerator::class), $this->createMock(InventoryHelper::class), $this->createMock(PlayerJournalEntryRepository::class), new ActionYieldResolver(), $worldScale, $this->createMock(EventDispatcherInterface::class), $this->purityDrawer()) extends GatherService {
            /** @var list<int> */
            public array $rolls = [];
            public \DateTimeImmutable $currentTime;
            private int $rollIndex = 0;

            protected function roll(int $max): int
            {
                return $this->rolls[$this->rollIndex++] ?? 1;
            }

            protected function now(): \DateTimeImmutable
            {
                return $this->currentTime;
            }
        };
        $service->currentTime = new \DateTimeImmutable('2026-07-24 12:00:00');
        $service->rolls = [3];

        $player = $this->buildPlayerIn([$this->ironResource()]);
        $vein = new ZoneVein($player->getCurrentZone(), 'filon-de-fer', $stock);
        $veinRepository->method('findOneByZoneAndSlug')->willReturn($vein);
        $itemRepository->method('findOneBy')->willReturn($this->buildItem(7, 'ore-iron', 'Minerai de fer'));

        return $service->gather($player, 'filon-de-fer')->quantity;
    }

    public function testGatherBoundsYieldByRemainingStockAndMarksDepleted(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $zone = $player->getCurrentZone();
        $vein = new ZoneVein($zone, 'filon-de-fer', 1); // un seul restant
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn($vein);
        $this->itemRepository->method('findOneBy')->willReturn($this->buildItem(7, 'ore-iron', 'Minerai de fer'));
        $this->service->rolls = [3]; // tirage 3 -> yield brut 3, borne a 1 par le stock

        $this->inventoryHelper->expects($this->once())->method('addItem');

        $result = $this->service->gather($player, 'filon-de-fer');

        $this->assertSame(1, $result->quantity);
        $this->assertSame(0, $result->remainingStock);
        $this->assertSame(0, $vein->getStock());
        $this->assertNotNull($vein->getDepletedAt());
    }

    public function testDepletedVeinRespawnsAfterWindow(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $zone = $player->getCurrentZone();
        // Epuise il y a 1000 s (respawn 900 s) : reconstitue a la capacite.
        $vein = new ZoneVein($zone, 'filon-de-fer', 0);
        $vein->setDepletedAt($this->service->currentTime->modify('-1000 seconds'));
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn($vein);
        $this->itemRepository->method('findOneBy')->willReturn($this->buildItem(7, 'ore-iron', 'Minerai de fer'));
        $this->service->rolls = [1]; // yield 1

        $result = $this->service->gather($player, 'filon-de-fer');

        $this->assertSame(1, $result->quantity);
        $this->assertSame(19, $result->remainingStock);
        $this->assertNull($vein->getDepletedAt());
    }

    public function testGetGatherablesReportsStockCapacityAndRespawn(): void
    {
        $zone = $this->buildZone([
            $this->ironResource(),
            ['slug' => 'filon-de-cuivre', 'item' => 'ore-copper', 'profession' => 'mining', 'capacity' => 10, 'respawn_seconds' => 600, 'yield_min' => 1, 'yield_max' => 2],
        ]);

        $this->itemRepository->method('findOneBy')->willReturnCallback(function (array $criteria): Item {
            return $this->buildItem(1, $criteria['slug'], strtoupper($criteria['slug']));
        });

        // Fer : jamais recolte -> plein. Cuivre : vide depuis 30 s. Capacite 10
        // pour 600 s de repousse, soit une unite toutes les 60 s (ZON-37) : il
        // reste donc 30 s avant que la prochaine tombe.
        $copperVein = new ZoneVein($zone, 'filon-de-cuivre', 0);
        $copperVein->setDepletedAt($this->service->currentTime->modify('-30 seconds'));
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturnCallback(function (Zone $z, string $slug) use ($copperVein): ?ZoneVein {
            return 'filon-de-cuivre' === $slug ? $copperVein : null;
        });

        $gatherables = $this->service->getGatherables($zone);

        $this->assertCount(2, $gatherables);
        $this->assertSame('filon-de-fer', $gatherables[0]->slug);
        $this->assertSame(20, $gatherables[0]->stock);
        $this->assertSame(20, $gatherables[0]->capacity);
        $this->assertFalse($gatherables[0]->isDepleted());
        $this->assertSame(0, $gatherables[0]->respawnRemaining);

        $this->assertSame('filon-de-cuivre', $gatherables[1]->slug);
        $this->assertSame(0, $gatherables[1]->stock);
        $this->assertTrue($gatherables[1]->isDepleted());
        $this->assertSame(30, $gatherables[1]->respawnRemaining, 'Le compte a rebours porte sur la prochaine unite, plus sur un retour a plein.');
    }

    /**
     * FOY-17b — le facteur de monde grossit le filon sans l'accelerer.
     *
     * « Un serveur plus peuple a des filons plus **gros**, pas plus
     * **rapides** » (BALANCE § 22.4). Le debit soutenu suit mecaniquement, mais
     * la cadence de repousse — le rythme de la maree, en fiction — reste la meme
     * pour tout le monde.
     */
    public function testWorldScaleMultipliesCapacityButNeverTheRespawn(): void
    {
        $service = $this->buildServiceWithScale(2.0);
        $zone = $this->buildZone([$this->ironResource()]);

        $this->itemRepository->method('findOneBy')->willReturnCallback(
            fn (array $criteria): Item => $this->buildItem(1, $criteria['slug'], strtoupper($criteria['slug'])),
        );

        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn(null);

        $gatherables = $service->getGatherables($zone);

        // Capacite declaree 20 : le tampon double, le `respawn_seconds` du YAML
        // n'est jamais touche (le temps de remplissage complet reste le meme —
        // cf. testFullRefillAlwaysTakesTheDeclaredRespawnPeriod).
        $this->assertSame(40, $gatherables[0]->capacity, 'La capacite suit le facteur de monde.');
    }

    /**
     * Un monde qui se resserre reduit l'ampleur, jamais en dessous d'une unite :
     * un filon ne devient pas inexistant parce que le serveur est petit.
     */
    public function testAContractedWorldStillLeavesAtLeastOneUnitOfCapacity(): void
    {
        $service = $this->buildServiceWithScale(0.01);
        $zone = $this->buildZone([$this->ironResource()]);

        $this->itemRepository->method('findOneBy')->willReturnCallback(
            fn (array $criteria): Item => $this->buildItem(1, $criteria['slug'], strtoupper($criteria['slug'])),
        );
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn(null);

        $this->assertSame(1, $service->getGatherables($zone)[0]->capacity);
    }

    /**
     * @return GatherService&object{rolls: list<int>, currentTime: \DateTimeImmutable}
     */
    private function buildServiceWithScale(float $scale): GatherService
    {
        $worldScale = $this->createMock(WorldScaleService::class);
        $worldScale->method('current')->willReturn($scale);

        $service = new class($this->entityManager, $this->actionEnergyManager, $this->zoneTravelService, $this->veinRepository, $this->playerItemGenerator, $this->inventoryHelper, $this->journalRepository, new ActionYieldResolver(), $worldScale, $this->createMock(EventDispatcherInterface::class), $this->purityDrawer()) extends GatherService {
            /** @var list<int> */
            public array $rolls = [];
            public \DateTimeImmutable $currentTime;
            private int $rollIndex = 0;

            protected function roll(int $max): int
            {
                return $this->rolls[$this->rollIndex++] ?? 1;
            }

            protected function now(): \DateTimeImmutable
            {
                return $this->currentTime;
            }
        };
        $service->currentTime = new \DateTimeImmutable('2026-07-24 12:00:00');

        return $service;
    }

    public function testGetGatherablesSkipsResourceWithUnknownItem(): void
    {
        $zone = $this->buildZone([$this->ironResource()]);
        $this->itemRepository->method('findOneBy')->willReturn(null); // item introuvable

        $this->assertSame([], $this->service->getGatherables($zone));
    }

    public function testGatherCostReadsParameterOverride(): void
    {
        $parameter = (new Parameter())->setName(GatherService::PARAM_COST)->setValue('4');
        $this->parameterRepository->method('findOneBy')->willReturn($parameter);

        $this->assertSame(4, $this->service->getGatherCost());
    }

    public function testGatherCostFallsBackToDefault(): void
    {
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        $this->assertSame(GatherService::DEFAULT_COST, $this->service->getGatherCost());
    }

    /**
     * ECO-22 : ces tests portent sur la recolte, pas sur la purete. Un tireur
     * qui ne rend rien laisse les lots sans bande — l'etat normal de tout ce qui
     * est hors perimetre, c'est-a-dire de l'immense majorite des matieres.
     */
    private function purityDrawer(): PurityDrawer
    {
        $drawer = $this->createMock(PurityDrawer::class);
        $drawer->method('draw')->willReturn(null);
        $drawer->method('coversSlug')->willReturn(false);

        return $drawer;
    }
}
