<?php

namespace App\Tests\Unit\GameEngine\Event;

use App\Entity\App\DomainExperience;
use App\Entity\App\GameEvent;
use App\Entity\App\Inventory;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Quest;
use App\Entity\Game\Recipe;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Crafting\QualityCalculator;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Player\PlayerActionHelper;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventBonusIntegrationTest extends TestCase
{
    public function testCraftingXpIsMultipliedByEventBonus(): void
    {
        $domain = $this->createMock(Domain::class);
        $domain->method('getTitle')->willReturn('Forge');

        $domainExp = new DomainExperience();
        $domainExp->setDomain($domain);
        $domainExp->setTotalExperience(100);

        $map = $this->createMock(Map::class);

        $player = $this->createPlayerWithBagItems(['iron-ore' => 3]);
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection([$domainExp]));
        $player->method('getMap')->willReturn($map);

        $resultItem = $this->createMock(Item::class);
        $resultItem->method('getId')->willReturn(42);
        $resultItem->method('getName')->willReturn('Barre de fer');

        $recipe = new Recipe();
        $recipe->setName('Iron Bar');
        $recipe->setSlug('iron-bar');
        $recipe->setCraft('forge');
        $recipe->setIngredients([['slug' => 'iron-ore', 'quantity' => 2]]);
        $recipe->setResult($resultItem);
        $recipe->setResultQuantity(1);
        $recipe->setXpReward(25);

        // XP bonus x2 active
        $bonusProvider = $this->createMock(GameEventBonusProvider::class);
        $bonusProvider->method('getXpMultiplier')->with($map)->willReturn(2.0);

        $em = $this->createMock(EntityManagerInterface::class);
        $playerItemGen = $this->createMock(PlayerItemGenerator::class);
        $playerItemGen->method('generateFromItemId')->willReturn($this->createMock(PlayerItem::class));

        $craftingManager = new CraftingManager(
            $em,
            $playerItemGen,
            $this->createMock(InventoryHelper::class),
            $this->createMock(PlayerHelper::class),
            $this->createStub(QualityCalculator::class),
            $this->createStub(EventDispatcherInterface::class),
            $bonusProvider,
            $this->createMock(\App\Helper\GearHelper::class),
            $this->createMock(PlayerActionHelper::class),
        );

        $result = $craftingManager->craft($player, $recipe);

        $this->assertTrue($result['success']);
        // 100 (base) + 25 * 2 (bonus) = 150
        $this->assertEquals(150, $domainExp->getTotalExperience());
        $this->assertStringContainsString('50 XP', $result['message']);
    }

    public function testCraftingXpNotMultipliedWithoutEvent(): void
    {
        $domain = $this->createMock(Domain::class);
        $domain->method('getTitle')->willReturn('Forge');

        $domainExp = new DomainExperience();
        $domainExp->setDomain($domain);
        $domainExp->setTotalExperience(100);

        $player = $this->createPlayerWithBagItems(['iron-ore' => 3]);
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection([$domainExp]));
        $player->method('getMap')->willReturn(null);

        $resultItem = $this->createMock(Item::class);
        $resultItem->method('getId')->willReturn(1);
        $resultItem->method('getName')->willReturn('Barre');

        $recipe = new Recipe();
        $recipe->setName('Iron Bar');
        $recipe->setSlug('iron-bar');
        $recipe->setCraft('forge');
        $recipe->setIngredients([['slug' => 'iron-ore', 'quantity' => 1]]);
        $recipe->setResult($resultItem);
        $recipe->setResultQuantity(1);
        $recipe->setXpReward(20);

        $em = $this->createMock(EntityManagerInterface::class);
        $playerItemGen = $this->createMock(PlayerItemGenerator::class);
        $playerItemGen->method('generateFromItemId')->willReturn($this->createMock(PlayerItem::class));

        // No event bonus: 1.0x
        $bonusProvider = $this->createMock(GameEventBonusProvider::class);
        $bonusProvider->method('getXpMultiplier')->willReturn(1.0);

        $manager = new CraftingManager(
            $em,
            $playerItemGen,
            $this->createMock(InventoryHelper::class),
            $this->createMock(PlayerHelper::class),
            $this->createStub(QualityCalculator::class),
            $this->createStub(EventDispatcherInterface::class),
            $bonusProvider,
            $this->createMock(\App\Helper\GearHelper::class),
            $this->createMock(PlayerActionHelper::class),
        );

        $result = $manager->craft($player, $recipe);

        $this->assertTrue($result['success']);
        // Base XP = 20, multiplier = 1.0 -> 20 XP granted
        $this->assertSame(120, $domainExp->getTotalExperience()); // 100 + 20
    }

    public function testEventQuestOnlyAvailableWhenEventActive(): void
    {
        $activeEvent = new GameEvent();
        $activeEvent->setName('Festival');
        $activeEvent->setType(GameEvent::TYPE_XP_BONUS);
        $activeEvent->setStatus(GameEvent::STATUS_ACTIVE);
        $activeEvent->setStartsAt(new \DateTime('-1 day'));
        $activeEvent->setEndsAt(new \DateTime('+1 day'));

        $expiredEvent = new GameEvent();
        $expiredEvent->setName('Old Event');
        $expiredEvent->setType(GameEvent::TYPE_DROP_BONUS);
        $expiredEvent->setStatus(GameEvent::STATUS_COMPLETED);
        $expiredEvent->setStartsAt(new \DateTime('-10 days'));
        $expiredEvent->setEndsAt(new \DateTime('-5 days'));

        $activeQuest = new Quest();
        $activeQuest->setName('Active Event Quest');
        $activeQuest->setDescription('A quest tied to an active event');
        $activeQuest->setRequirements([]);
        $activeQuest->setRewards([]);
        $activeQuest->setGameEvent($activeEvent);

        $expiredQuest = new Quest();
        $expiredQuest->setName('Expired Event Quest');
        $expiredQuest->setDescription('A quest tied to an expired event');
        $expiredQuest->setRequirements([]);
        $expiredQuest->setRewards([]);
        $expiredQuest->setGameEvent($expiredEvent);

        $normalQuest = new Quest();
        $normalQuest->setName('Normal Quest');
        $normalQuest->setDescription('A regular quest');
        $normalQuest->setRequirements([]);
        $normalQuest->setRewards([]);

        // Active event quest should be visible
        $this->assertTrue($activeQuest->isEventQuest());
        $this->assertTrue($activeQuest->isEventActive());

        // Expired event quest should not be visible
        $this->assertTrue($expiredQuest->isEventQuest());
        $this->assertFalse($expiredQuest->isEventActive());

        // Normal quest is not an event quest, always active
        $this->assertFalse($normalQuest->isEventQuest());
        $this->assertTrue($normalQuest->isEventActive());
    }

    public function testItemCosmeticFlag(): void
    {
        $item = new Item();
        $item->setName('Crown');
        $item->setSlug('crown');
        $item->setDescription('A crown');

        $this->assertFalse($item->isCosmetic());

        $item->setIsCosmetic(true);
        $this->assertTrue($item->isCosmetic());
    }

    private function createPlayerWithBagItems(array $itemCounts): Player&MockObject
    {
        $items = [];
        foreach ($itemCounts as $slug => $count) {
            for ($i = 0; $i < $count; ++$i) {
                $genericItem = $this->createMock(Item::class);
                $genericItem->method('getSlug')->willReturn($slug);

                $playerItem = $this->createMock(PlayerItem::class);
                $playerItem->method('getGenericItem')->willReturn($genericItem);

                $items[] = $playerItem;
            }
        }

        $bagInventory = $this->createMock(Inventory::class);
        $bagInventory->method('isBag')->willReturn(true);
        $bagInventory->method('getItems')->willReturn(new ArrayCollection($items));

        $player = $this->createMock(Player::class);
        $player->method('getInventories')->willReturn(new ArrayCollection([$bagInventory]));

        return $player;
    }
}
