<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Fight;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerExpedition;
use App\Entity\App\PlayerItem;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Notification\NotificationService;
use App\GameEngine\Zone\ExpeditionService;
use App\GameEngine\Zone\ZoneActionException;
use App\GameEngine\Zone\ZoneTravelService;
use App\Helper\InventoryHelper;
use App\Repository\PlayerExpeditionRepository;
use App\Repository\PlayerJournalEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ExpeditionServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private EntityRepository&MockObject $itemRepository;
    private PlayerExpeditionRepository&MockObject $expeditionRepository;
    private ZoneTravelService&MockObject $zoneTravelService;
    private PlayerItemGenerator&MockObject $playerItemGenerator;
    private InventoryHelper&MockObject $inventoryHelper;
    private PlayerJournalEntryRepository&MockObject $journalRepository;
    private NotificationService&MockObject $notificationService;
    private EventDispatcherInterface&MockObject $eventDispatcher;

    private ExpeditionService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->itemRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $this->parameterRepository],
            [Item::class, $this->itemRepository],
        ]);
        $this->parameterRepository->method('findOneBy')->willReturn(null);

        $this->expeditionRepository = $this->createMock(PlayerExpeditionRepository::class);
        $this->zoneTravelService = $this->createMock(ZoneTravelService::class);
        $this->playerItemGenerator = $this->createMock(PlayerItemGenerator::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        $this->journalRepository = $this->createMock(PlayerJournalEntryRepository::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        // ONB-12a : lancer une expedition est un geste annonce.
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new class($this->entityManager, $this->expeditionRepository, $this->zoneTravelService, $this->playerItemGenerator, $this->inventoryHelper, $this->journalRepository, $this->notificationService, $this->eventDispatcher) extends ExpeditionService {
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
        $this->service->currentTime = new \DateTimeImmutable('2026-07-25 12:00:00');
    }

    private function buildZone(bool $safe = false): Zone
    {
        return (new Zone())
            ->setSlug('foret-des-murmures')
            ->setName('Foret des Murmures')
            ->setIsSafe($safe)
            ->setExploreConfig(['chest_gils_min' => 10, 'chest_gils_max' => 10])
            ->setGatherConfig(['resources' => [['slug' => 'chene', 'item' => 'bois-de-chene', 'yield_min' => 2, 'yield_max' => 2]]]);
    }

    private function buildPlayer(?Zone $zone): Player
    {
        $player = new Player();
        if (null !== $zone) {
            $player->setCurrentZone($zone);
        }

        return $player;
    }

    private function buildItem(int $id, string $slug): Item
    {
        $item = new Item();
        $item->setName(ucfirst($slug));
        $item->setSlug($slug);
        $ref = new \ReflectionProperty(Item::class, 'id');
        $ref->setValue($item, $id);

        return $item;
    }

    public function testGetDurationsFallsBackToDefaults(): void
    {
        $durations = $this->service->getDurations();

        $this->assertSame(3600, $durations[ExpeditionService::DURATION_SHORT]);
        $this->assertSame(14400, $durations[ExpeditionService::DURATION_MEDIUM]);
        $this->assertSame(43200, $durations[ExpeditionService::DURATION_LONG]);
    }

    public function testStartCreatesExpedition(): void
    {
        $player = $this->buildPlayer($this->buildZone());
        $this->expeditionRepository->method('findForPlayer')->willReturn(null);

        $persisted = null;
        $this->entityManager->expects($this->once())->method('persist')
            ->willReturnCallback(function ($e) use (&$persisted) { $persisted = $e; });
        $this->entityManager->expects($this->once())->method('flush');

        $expedition = $this->service->start($player, ExpeditionService::DURATION_MEDIUM);

        $this->assertInstanceOf(PlayerExpedition::class, $expedition);
        $this->assertSame($persisted, $expedition);
        $this->assertSame(ExpeditionService::DURATION_MEDIUM, $expedition->getDurationKey());
        // 4 h apres l'instant courant.
        $this->assertEquals($this->service->currentTime->modify('+14400 seconds'), $expedition->getEndsAt());
    }

    public function testStartRejectsUnknownDuration(): void
    {
        $this->expectException(ZoneActionException::class);
        $this->service->start($this->buildPlayer($this->buildZone()), 'epic');
    }

    public function testStartRejectsWhenAlreadyActive(): void
    {
        $player = $this->buildPlayer($this->buildZone());
        $existing = new PlayerExpedition($player, $this->buildZone(), ExpeditionService::DURATION_SHORT, $this->service->currentTime, $this->service->currentTime);
        $this->expeditionRepository->method('findForPlayer')->willReturn($existing);

        $this->expectException(ZoneActionException::class);
        $this->service->start($player, ExpeditionService::DURATION_SHORT);
    }

    public function testStartRejectsInFight(): void
    {
        $player = $this->buildPlayer($this->buildZone());
        $player->setFight(new Fight());
        $this->expeditionRepository->method('findForPlayer')->willReturn(null);

        $this->expectException(ZoneActionException::class);
        $this->service->start($player, ExpeditionService::DURATION_SHORT);
    }

    public function testStartRejectsSafeZone(): void
    {
        $player = $this->buildPlayer($this->buildZone(true));
        $this->expeditionRepository->method('findForPlayer')->willReturn(null);

        $this->expectException(ZoneActionException::class);
        $this->service->start($player, ExpeditionService::DURATION_SHORT);
    }

    public function testSettleNotifiesOnceWhenComplete(): void
    {
        $player = $this->buildPlayer($this->buildZone());
        $started = $this->service->currentTime->modify('-2 hours');
        $endsAt = $this->service->currentTime->modify('-1 hour');
        $expedition = new PlayerExpedition($player, $this->buildZone(), ExpeditionService::DURATION_SHORT, $started, $endsAt);
        $this->expeditionRepository->method('findForPlayer')->willReturn($expedition);

        $this->notificationService->expects($this->once())->method('notify');

        $this->service->settle($player);

        $this->assertNotNull($expedition->getNotifiedAt());
    }

    public function testSettleDoesNotNotifyTwice(): void
    {
        $player = $this->buildPlayer($this->buildZone());
        $expedition = new PlayerExpedition($player, $this->buildZone(), ExpeditionService::DURATION_SHORT, $this->service->currentTime->modify('-2 hours'), $this->service->currentTime->modify('-1 hour'));
        $expedition->setNotifiedAt($this->service->currentTime->modify('-30 minutes'));
        $this->expeditionRepository->method('findForPlayer')->willReturn($expedition);

        $this->notificationService->expects($this->never())->method('notify');

        $this->service->settle($player);
    }

    public function testSettleDoesNotNotifyWhileInProgress(): void
    {
        $player = $this->buildPlayer($this->buildZone());
        $expedition = new PlayerExpedition($player, $this->buildZone(), ExpeditionService::DURATION_LONG, $this->service->currentTime, $this->service->currentTime->modify('+2 hours'));
        $this->expeditionRepository->method('findForPlayer')->willReturn($expedition);

        $this->notificationService->expects($this->never())->method('notify');

        $this->service->settle($player);
    }

    public function testClaimGrantsRewardsAndRemovesExpedition(): void
    {
        $zone = $this->buildZone();
        $player = $this->buildPlayer($zone);
        // 1 h d'expedition => 1 tirage gils (10) + 1 objet (bois, x2).
        $started = $this->service->currentTime->modify('-2 hours');
        $endsAt = $this->service->currentTime->modify('-1 hour');
        $expedition = new PlayerExpedition($player, $zone, ExpeditionService::DURATION_SHORT, $started, $endsAt);
        $this->expeditionRepository->method('findForPlayer')->willReturn($expedition);

        $item = $this->buildItem(42, 'bois-de-chene');
        $this->itemRepository->method('findOneBy')->willReturn($item);
        $this->playerItemGenerator->method('generateFromItemId')->willReturn(new PlayerItem());

        $this->inventoryHelper->expects($this->exactly(2))->method('addItem');
        $this->entityManager->expects($this->once())->method('remove')->with($expedition);

        $result = $this->service->claim($player);

        $this->assertSame('Foret des Murmures', $result->zoneName);
        $this->assertSame(10, $result->gils);
        $this->assertSame(10, $player->getGils());
        $this->assertCount(1, $result->items);
        $this->assertSame(2, $result->items[0]['quantity']);
    }

    public function testClaimRejectsWhenNone(): void
    {
        $this->expeditionRepository->method('findForPlayer')->willReturn(null);

        $this->expectException(ZoneActionException::class);
        $this->service->claim($this->buildPlayer($this->buildZone()));
    }

    public function testClaimRejectsWhenNotComplete(): void
    {
        $player = $this->buildPlayer($this->buildZone());
        $expedition = new PlayerExpedition($player, $this->buildZone(), ExpeditionService::DURATION_LONG, $this->service->currentTime, $this->service->currentTime->modify('+2 hours'));
        $this->expeditionRepository->method('findForPlayer')->willReturn($expedition);

        $this->expectException(ZoneActionException::class);
        $this->service->claim($player);
    }
}
