<?php

namespace App\Tests\Unit\GameEngine\Job;

use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\MonsterItem;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Job\ButcheringManager;
use App\Helper\GearHelper;
use App\Helper\InventoryHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ButcheringManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerItemGenerator&MockObject $playerItemGenerator;
    private InventoryHelper&MockObject $inventoryHelper;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private GearHelper&MockObject $gearHelper;
    private GameEventBonusProvider&MockObject $gameEventBonusProvider;
    private ButcheringManager $butcheringManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->playerItemGenerator = $this->createMock(PlayerItemGenerator::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->gameEventBonusProvider = $this->createMock(GameEventBonusProvider::class);
        $this->gameEventBonusProvider->method('getGatheringMultiplier')->willReturn(1.0);

        $this->butcheringManager = new ButcheringManager(
            $this->entityManager,
            $this->playerItemGenerator,
            $this->inventoryHelper,
            $this->eventDispatcher,
            $this->gearHelper,
            $this->gameEventBonusProvider,
        );
    }

    public function testCanButcherWithFunctionalKnife(): void
    {
        $knife = $this->createMock(PlayerItem::class);
        $knife->method('getCurrentDurability')->willReturn(10);
        $this->gearHelper->method('getEquippedToolByType')->with(Item::TOOL_TYPE_SKINNING_KNIFE)->willReturn($knife);

        $this->assertTrue($this->butcheringManager->canButcher($this->createMock(Player::class)));
    }

    public function testCannotButcherWithoutKnife(): void
    {
        $this->gearHelper->method('getEquippedToolByType')->willReturn(null);

        $this->assertFalse($this->butcheringManager->canButcher($this->createMock(Player::class)));
    }

    public function testCannotButcherWithBrokenKnife(): void
    {
        $knife = $this->createMock(PlayerItem::class);
        $knife->method('getCurrentDurability')->willReturn(0);
        $this->gearHelper->method('getEquippedToolByType')->willReturn($knife);

        $this->assertFalse($this->butcheringManager->canButcher($this->createMock(Player::class)));
    }

    public function testButcherFailsWithoutKnife(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('hasToolSlot')->willReturn(true);
        $mob = $this->createMock(Mob::class);

        $this->gearHelper->method('getEquippedToolByType')->willReturn(null);

        $result = $this->butcheringManager->butcher($player, $mob);

        $this->assertFalse($result['success']);
        $this->assertSame([], $result['items']);
    }

    public function testButcherAppliesGatheringMultiplierToDrops(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('getMap')->willReturn(null);

        // Knife: tier 1, 100% drop chance with random roll
        $knifeItem = $this->createMock(Item::class);
        $knifeItem->method('getToolTier')->willReturn(1);
        $knife = $this->createMock(PlayerItem::class);
        $knife->method('getCurrentDurability')->willReturn(10);
        $knife->method('getGenericItem')->willReturn($knifeItem);
        $knife->method('reduceDurability')->willReturn(false);
        $this->gearHelper->method('getEquippedToolByType')->willReturn($knife);

        // Monster with one drop at 100% probability (post-tooltier adjustment guarantees roll <= chance)
        $dropItem = $this->createMock(Item::class);
        $dropItem->method('getId')->willReturn(42);

        $monsterItem = $this->createMock(MonsterItem::class);
        $monsterItem->method('getProbability')->willReturn(1.0);
        $monsterItem->method('getItem')->willReturn($dropItem);

        $monster = $this->createMock(Monster::class);
        $monster->method('getMonsterItems')->willReturn(new ArrayCollection([$monsterItem]));

        $mob = $this->createMock(Mob::class);
        $mob->method('getMonster')->willReturn($monster);

        // Multiplier 2.0 -> chaque drop reussi est duplique 2x
        $bonusProvider = $this->createMock(GameEventBonusProvider::class);
        $bonusProvider->method('getGatheringMultiplier')->with(null)->willReturn(2.0);

        $manager = new ButcheringManager(
            $this->entityManager,
            $this->playerItemGenerator,
            $this->inventoryHelper,
            $this->eventDispatcher,
            $this->gearHelper,
            $bonusProvider,
        );

        $generated = $this->createMock(PlayerItem::class);
        $generatedItem = $this->createMock(Item::class);
        $generatedItem->method('getName')->willReturn('Cuir epais');
        $generatedItem->method('getSlug')->willReturn('thick-leather');
        $generated->method('getGenericItem')->willReturn($generatedItem);

        $this->playerItemGenerator->expects($this->exactly(2))
            ->method('generateFromItemId')
            ->with(42)
            ->willReturn($generated);

        $this->inventoryHelper->expects($this->exactly(2))->method('addItem');

        $result = $manager->butcher($player, $mob);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['items']);
    }
}
