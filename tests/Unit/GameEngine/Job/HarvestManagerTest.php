<?php

namespace App\Tests\Unit\GameEngine\Job;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Event\Map\SpotHarvestEvent;
use App\GameEngine\Generator\HarvestItemGenerator;
use App\GameEngine\Job\HarvestManager;
use App\GameEngine\Player\PlayerActionHelper;
use App\Helper\GearHelper;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HarvestManagerTest extends TestCase
{
    private HarvestItemGenerator&MockObject $harvestItemGenerator;
    private EntityManagerInterface&MockObject $entityManager;
    private InventoryHelper&MockObject $inventoryHelper;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private GearHelper&MockObject $gearHelper;
    private PlayerActionHelper&MockObject $playerActionHelper;
    private HarvestManager $harvestManager;

    protected function setUp(): void
    {
        $this->harvestItemGenerator = $this->createMock(HarvestItemGenerator::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->playerActionHelper = $this->createMock(PlayerActionHelper::class);

        $this->harvestManager = new HarvestManager(
            $this->harvestItemGenerator,
            $this->entityManager,
            $this->inventoryHelper,
            $this->eventDispatcher,
            $this->gearHelper,
            $this->playerActionHelper,
        );
    }

    public function testCheckObjectLayerAvailableAndHarvestable(): void
    {
        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('isAvailable')->willReturn(true);
        $objectLayer->method('getActions')->willReturn([
            ['action' => PlayerActionHelper::HARVEST],
        ]);

        // Should not throw
        $this->harvestManager->checkObjectLayer($objectLayer);
        $this->addToAssertionCount(1);
    }

    public function testCheckObjectLayerNotAvailableThrows(): void
    {
        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('isAvailable')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->harvestManager->checkObjectLayer($objectLayer);
    }

    public function testCheckObjectLayerNotHarvestableThrows(): void
    {
        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('isAvailable')->willReturn(true);
        $objectLayer->method('getActions')->willReturn([
            ['action' => 'open'],
        ]);
        $objectLayer->method('getName')->willReturn('Coffre');

        $this->expectException(\RuntimeException::class);
        $this->harvestManager->checkObjectLayer($objectLayer);
    }

    public function testCheckToolRequirementNoToolRequired(): void
    {
        $player = $this->createMock(Player::class);
        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getRequiredToolType')->willReturn(null);

        $result = $this->harvestManager->checkToolRequirement($player, $objectLayer);

        $this->assertNull($result);
    }

    public function testCheckToolRequirementToolPresent(): void
    {
        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getCurrentDurability')->willReturn(10);

        $player = $this->createMock(Player::class);
        $player->method('hasToolSlot')->with(Item::TOOL_TYPE_PICKAXE)->willReturn(true);

        $this->gearHelper->method('getEquippedToolByType')
            ->with(Item::TOOL_TYPE_PICKAXE)
            ->willReturn($playerItem);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getRequiredToolType')->willReturn(Item::TOOL_TYPE_PICKAXE);

        $result = $this->harvestManager->checkToolRequirement($player, $objectLayer);

        $this->assertSame($playerItem, $result);
    }

    public function testCheckToolRequirementSlotNotUnlockedThrows(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasToolSlot')->with(Item::TOOL_TYPE_PICKAXE)->willReturn(false);

        $this->playerActionHelper->method('getUnlockedToolSlots')->willReturn([]);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getRequiredToolType')->willReturn(Item::TOOL_TYPE_PICKAXE);

        $this->expectException(\RuntimeException::class);
        $this->harvestManager->checkToolRequirement($player, $objectLayer);
    }

    public function testCheckToolRequirementSlotUnlockedViaSkillsAutoSyncs(): void
    {
        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getCurrentDurability')->willReturn(10);

        $player = $this->createMock(Player::class);
        $player->method('hasToolSlot')->with(Item::TOOL_TYPE_PICKAXE)->willReturn(false);
        $player->expects($this->once())->method('unlockToolSlot')->with(Item::TOOL_TYPE_PICKAXE);

        $this->playerActionHelper->method('getUnlockedToolSlots')->willReturn([Item::TOOL_TYPE_PICKAXE]);

        $this->gearHelper->method('getEquippedToolByType')
            ->with(Item::TOOL_TYPE_PICKAXE)
            ->willReturn($playerItem);

        $this->entityManager->expects($this->once())->method('flush');

        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getRequiredToolType')->willReturn(Item::TOOL_TYPE_PICKAXE);

        $result = $this->harvestManager->checkToolRequirement($player, $objectLayer);

        $this->assertSame($playerItem, $result);
    }

    public function testCheckToolRequirementToolNotEquippedThrows(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasToolSlot')->with(Item::TOOL_TYPE_PICKAXE)->willReturn(true);

        $this->gearHelper->method('getEquippedToolByType')
            ->with(Item::TOOL_TYPE_PICKAXE)
            ->willReturn(null);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getRequiredToolType')->willReturn(Item::TOOL_TYPE_PICKAXE);

        $this->expectException(\RuntimeException::class);
        $this->harvestManager->checkToolRequirement($player, $objectLayer);
    }

    public function testCheckToolRequirementBrokenToolThrows(): void
    {
        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getCurrentDurability')->willReturn(0);

        $player = $this->createMock(Player::class);
        $player->method('hasToolSlot')->with(Item::TOOL_TYPE_SICKLE)->willReturn(true);

        $this->gearHelper->method('getEquippedToolByType')
            ->with(Item::TOOL_TYPE_SICKLE)
            ->willReturn($playerItem);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getRequiredToolType')->willReturn(Item::TOOL_TYPE_SICKLE);

        $this->expectException(\RuntimeException::class);
        $this->harvestManager->checkToolRequirement($player, $objectLayer);
    }

    public function testHarvestResourcesSuccess(): void
    {
        $harvestedItem = $this->createMock(PlayerItem::class);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getId')->willReturn(1);
        $objectLayer->method('getRequiredToolType')->willReturn(null);

        $objectLayerRepo = $this->createMock(EntityRepository::class);
        $objectLayerRepo->method('find')->with(1)->willReturn($objectLayer);
        $this->entityManager->method('getRepository')
            ->with(ObjectLayer::class)
            ->willReturn($objectLayerRepo);

        $this->harvestItemGenerator->method('generateHarvestItems')
            ->with($objectLayer)
            ->willReturn([$harvestedItem]);

        $objectLayer->expects($this->once())->method('setUsedAt');

        $this->inventoryHelper->expects($this->once())
            ->method('addItem')
            ->with($harvestedItem, false);

        $this->entityManager->expects($this->once())->method('flush');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(SpotHarvestEvent::class),
                SpotHarvestEvent::NAME
            );

        $result = $this->harvestManager->harvestResources($objectLayer);

        $this->assertSame($objectLayer, $result['objectLayer']);
        $this->assertCount(1, $result['items']);
        $this->assertSame($harvestedItem, $result['items'][0]);
        $this->assertFalse($result['toolBroken']);
    }

    public function testHarvestResourcesReducesToolDurability(): void
    {
        $tool = $this->createMock(PlayerItem::class);
        $tool->method('getCurrentDurability')->willReturn(1);
        $tool->expects($this->once())->method('reduceDurability')->with(1)->willReturn(true);

        $player = $this->createMock(Player::class);

        $this->gearHelper->method('getEquippedToolByType')
            ->with(Item::TOOL_TYPE_PICKAXE)
            ->willReturn($tool);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $objectLayer->method('getId')->willReturn(1);
        $objectLayer->method('getRequiredToolType')->willReturn(Item::TOOL_TYPE_PICKAXE);

        $objectLayerRepo = $this->createMock(EntityRepository::class);
        $objectLayerRepo->method('find')->willReturn($objectLayer);
        $this->entityManager->method('getRepository')
            ->with(ObjectLayer::class)
            ->willReturn($objectLayerRepo);

        $this->harvestItemGenerator->method('generateHarvestItems')->willReturn([]);

        $result = $this->harvestManager->harvestResources($objectLayer, $player);

        $this->assertTrue($result['toolBroken']);
    }
}
