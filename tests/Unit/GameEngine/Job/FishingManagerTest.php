<?php

namespace App\Tests\Unit\GameEngine\Job;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Event\Map\FishingEvent;
use App\GameEngine\Generator\HarvestItemGenerator;
use App\GameEngine\Job\FishingManager;
use App\Helper\GearHelper;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FishingManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private HarvestItemGenerator&MockObject $harvestItemGenerator;
    private InventoryHelper&MockObject $inventoryHelper;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private GearHelper&MockObject $gearHelper;
    private FishingManager $fishingManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->harvestItemGenerator = $this->createMock(HarvestItemGenerator::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->gearHelper = $this->createMock(GearHelper::class);

        $this->fishingManager = new FishingManager(
            $this->entityManager,
            $this->harvestItemGenerator,
            $this->inventoryHelper,
            $this->eventDispatcher,
            $this->gearHelper,
        );
    }

    public function testCompleteFishingWithoutRodReturnsFailure(): void
    {
        $player = $this->createMock(Player::class);
        $spot = $this->createMock(ObjectLayer::class);

        $this->gearHelper->method('getEquippedToolByType')->with(Item::TOOL_TYPE_FISHING_ROD)->willReturn(null);
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->fishingManager->completeFishing($player, $spot, 50);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['perfect']);
        $this->assertNull($result['item']);
    }

    public function testCompleteFishingTooLowFails(): void
    {
        $player = $this->createMock(Player::class);
        $spot = $this->createMock(ObjectLayer::class);
        $rod = $this->createMock(PlayerItem::class);

        $this->gearHelper->method('getEquippedToolByType')->willReturn($rod);
        $rod->expects($this->never())->method('reduceDurability');
        $this->entityManager->expects($this->never())->method('flush');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(FishingEvent::class), FishingEvent::NAME);

        $result = $this->fishingManager->completeFishing($player, $spot, 10);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['perfect']);
        $this->assertNull($result['item']);
        $this->assertStringContainsString('faible', $result['message']);
    }

    public function testCompleteFishingTooHighDamagesRod(): void
    {
        $player = $this->createMock(Player::class);
        $spot = $this->createMock(ObjectLayer::class);
        $rod = $this->createMock(PlayerItem::class);

        $this->gearHelper->method('getEquippedToolByType')->willReturn($rod);
        $rod->expects($this->once())->method('reduceDurability')->with(2)->willReturn(false);
        $this->entityManager->expects($this->once())->method('flush');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(FishingEvent::class), FishingEvent::NAME);

        $result = $this->fishingManager->completeFishing($player, $spot, 85);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['perfect']);
        $this->assertNull($result['item']);
        $this->assertStringContainsString('fort', $result['message']);
    }

    public function testCompleteFishingNormalSuccessReducesDurability(): void
    {
        $player = $this->createMock(Player::class);
        $spot = $this->createMock(ObjectLayer::class);
        $rod = $this->createMock(PlayerItem::class);
        $caughtItem = $this->buildCaughtItem('Truite', 'trout');

        $this->gearHelper->method('getEquippedToolByType')->willReturn($rod);
        $this->harvestItemGenerator->method('generateHarvestItems')->willReturn([$caughtItem]);
        $this->inventoryHelper->expects($this->once())->method('addItem')->with($caughtItem, false);

        $rod->expects($this->once())->method('reduceDurability')->with(1);
        $spot->expects($this->once())->method('setUsedAt');
        $this->entityManager->expects($this->once())->method('flush');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(FishingEvent::class), FishingEvent::NAME);

        $result = $this->fishingManager->completeFishing($player, $spot, 35);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['perfect']);
        $this->assertSame(['name' => 'Truite', 'slug' => 'trout'], $result['item']);
        $this->assertStringNotContainsString('Parfait', $result['message']);
    }

    public function testCompleteFishingPerfectPreservesDurability(): void
    {
        $player = $this->createMock(Player::class);
        $spot = $this->createMock(ObjectLayer::class);
        $rod = $this->createMock(PlayerItem::class);
        $caughtItem = $this->buildCaughtItem('Saumon', 'salmon');

        $this->gearHelper->method('getEquippedToolByType')->willReturn($rod);
        $this->harvestItemGenerator->method('generateHarvestItems')->willReturn([$caughtItem]);
        $this->inventoryHelper->expects($this->once())->method('addItem')->with($caughtItem, false);

        $rod->expects($this->never())->method('reduceDurability');
        $spot->expects($this->once())->method('setUsedAt');
        $this->entityManager->expects($this->once())->method('flush');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(FishingEvent::class), FishingEvent::NAME);

        $result = $this->fishingManager->completeFishing($player, $spot, 50);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['perfect']);
        $this->assertSame(['name' => 'Saumon', 'slug' => 'salmon'], $result['item']);
        $this->assertStringStartsWith('Parfait !', $result['message']);
    }

    public function testCompleteFishingPerfectBoundariesAreInclusive(): void
    {
        $rodLow = $this->createMock(PlayerItem::class);
        $rodLow->expects($this->never())->method('reduceDurability');
        $this->runPerfectBoundary(FishingManager::PERFECT_MIN, $rodLow);

        $this->setUp();

        $rodHigh = $this->createMock(PlayerItem::class);
        $rodHigh->expects($this->never())->method('reduceDurability');
        $this->runPerfectBoundary(FishingManager::PERFECT_MAX, $rodHigh);
    }

    private function runPerfectBoundary(int $tension, PlayerItem&MockObject $rod): void
    {
        $player = $this->createMock(Player::class);
        $spot = $this->createMock(ObjectLayer::class);

        $this->gearHelper->method('getEquippedToolByType')->willReturn($rod);
        $this->harvestItemGenerator->method('generateHarvestItems')->willReturn([]);

        $result = $this->fishingManager->completeFishing($player, $spot, $tension);

        $this->assertTrue($result['success'], sprintf('Tension %d should succeed', $tension));
        $this->assertTrue($result['perfect'], sprintf('Tension %d should be perfect', $tension));
    }

    private function buildCaughtItem(string $name, string $slug): PlayerItem&MockObject
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('getName')->willReturn($name);
        $genericItem->method('getSlug')->willReturn($slug);

        $caughtItem = $this->createMock(PlayerItem::class);
        $caughtItem->method('getGenericItem')->willReturn($genericItem);

        return $caughtItem;
    }
}
