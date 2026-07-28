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

        $this->service = new class($this->entityManager, $this->actionEnergyManager, $this->zoneTravelService, $this->veinRepository, $this->playerItemGenerator, $this->inventoryHelper, $this->journalRepository, new ActionYieldResolver(), $this->worldScaleService) extends GatherService {
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

    public function testRefusesDepletedVeinWithoutSpendingEnergy(): void
    {
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $zone = $player->getCurrentZone();

        // Filon vide, epuise il y a 10 s (respawn 900 s) : pas encore reconstitue.
        $vein = new ZoneVein($zone, 'filon-de-fer', 0);
        $vein->setDepletedAt($this->service->currentTime->modify('-10 seconds'));
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn($vein);
        $this->itemRepository->method('findOneBy')->willReturn($this->buildItem(1, 'ore-iron', 'Minerai de fer'));

        $this->actionEnergyManager->expects($this->never())->method('spend');
        $this->expectExceptionMessage('game.zone.gather.error.depleted');
        $this->service->gather($player, 'filon-de-fer');
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
        $player = $this->buildPlayerIn([$this->ironResource()]);
        $skill = new Skill();
        $skill->setActions(['yield' => ['gather_percent' => 100]]);
        $player->addSkill($skill);

        $zone = $player->getCurrentZone();
        $vein = new ZoneVein($zone, 'filon-de-fer', 2); // deux restants
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn($vein);
        $this->itemRepository->method('findOneBy')->willReturn($this->buildItem(7, 'ore-iron', 'Minerai de fer'));
        $this->service->rolls = [3]; // 3 doubles a 6, mais le stock plafonne a 2

        $result = $this->service->gather($player, 'filon-de-fer');

        $this->assertSame(2, $result->quantity);
        $this->assertSame(0, $result->remainingStock);
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

        // Fer : jamais recolte -> plein. Cuivre : vide depuis 60 s (respawn 600) -> epuise, 540 s restantes.
        $copperVein = new ZoneVein($zone, 'filon-de-cuivre', 0);
        $copperVein->setDepletedAt($this->service->currentTime->modify('-60 seconds'));
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
        $this->assertSame(540, $gatherables[1]->respawnRemaining);
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

        $vein = new ZoneVein($zone, 'filon-de-fer', 0);
        $vein->setDepletedAt($service->currentTime->modify('-60 seconds'));
        $this->veinRepository->method('findOneByZoneAndSlug')->willReturn($vein);

        $gatherables = $service->getGatherables($zone);

        // Capacite declaree 20, respawn declare 900.
        $this->assertSame(40, $gatherables[0]->capacity, 'La capacite suit le facteur de monde.');
        $this->assertSame(840, $gatherables[0]->respawnRemaining, 'La repousse, elle, ne bouge pas.');
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

        $service = new class($this->entityManager, $this->actionEnergyManager, $this->zoneTravelService, $this->veinRepository, $this->playerItemGenerator, $this->inventoryHelper, $this->journalRepository, new ActionYieldResolver(), $worldScale) extends GatherService {
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
}
