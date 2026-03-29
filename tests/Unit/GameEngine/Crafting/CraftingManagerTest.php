<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\Entity\App\DomainExperience;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Event\CraftEvent;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Crafting\QualityCalculator;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Player\PlayerActionHelper;
use App\Helper\GearHelper;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CraftingManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerItemGenerator&MockObject $playerItemGenerator;
    private InventoryHelper&MockObject $inventoryHelper;
    private PlayerHelper&MockObject $playerHelper;
    private QualityCalculator&MockObject $qualityCalculator;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private GameEventBonusProvider&MockObject $gameEventBonusProvider;
    private GearHelper&MockObject $gearHelper;
    private CraftingManager $craftingManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->playerItemGenerator = $this->createMock(PlayerItemGenerator::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->qualityCalculator = $this->createMock(QualityCalculator::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->gameEventBonusProvider = $this->createMock(GameEventBonusProvider::class);
        $this->gameEventBonusProvider->method('getXpMultiplier')->willReturn(1.0);
        $this->gearHelper = $this->createMock(GearHelper::class);

        $this->craftingManager = new CraftingManager(
            $this->entityManager,
            $this->playerItemGenerator,
            $this->inventoryHelper,
            $this->playerHelper,
            $this->qualityCalculator,
            $this->eventDispatcher,
            $this->gameEventBonusProvider,
            $this->gearHelper,
            $this->createMock(PlayerActionHelper::class),
        );
    }

    public function testCanCraftWithAllIngredients(): void
    {
        $player = $this->createPlayerWithBagItems(['iron-ore' => 3, 'coal' => 2]);

        $recipe = new Recipe();
        $recipe->setName('Iron Bar');
        $recipe->setSlug('iron-bar');
        $recipe->setCraft('forge');
        $recipe->setIngredients([
            ['slug' => 'iron-ore', 'quantity' => 2],
            ['slug' => 'coal', 'quantity' => 1],
        ]);

        $result = $this->craftingManager->canCraft($player, $recipe);

        $this->assertTrue($result['possible']);
        $this->assertEmpty($result['missing']);
    }

    public function testCanCraftWithMissingIngredients(): void
    {
        $player = $this->createPlayerWithBagItems(['iron-ore' => 1]);

        $recipe = new Recipe();
        $recipe->setName('Iron Bar');
        $recipe->setSlug('iron-bar');
        $recipe->setCraft('forge');
        $recipe->setIngredients([
            ['slug' => 'iron-ore', 'quantity' => 3],
            ['slug' => 'coal', 'quantity' => 1],
        ]);

        $result = $this->craftingManager->canCraft($player, $recipe);

        $this->assertFalse($result['possible']);
        $this->assertCount(2, $result['missing']);
        $this->assertEquals('iron-ore', $result['missing'][0]['slug']);
        $this->assertEquals(3, $result['missing'][0]['need']);
        $this->assertEquals(1, $result['missing'][0]['have']);
        $this->assertEquals('coal', $result['missing'][1]['slug']);
    }

    public function testCraftSuccessCreatesItem(): void
    {
        $resultItem = $this->createMock(Item::class);
        $resultItem->method('getId')->willReturn(42);
        $resultItem->method('getName')->willReturn('Barre de fer');

        $recipe = new Recipe();
        $recipe->setName('Iron Bar');
        $recipe->setSlug('iron-bar');
        $recipe->setCraft('forge');
        $recipe->setIngredients([
            ['slug' => 'iron-ore', 'quantity' => 2],
        ]);
        $recipe->setResult($resultItem);
        $recipe->setResultQuantity(1);
        $recipe->setXpReward(25);

        $player = $this->createPlayerWithBagItems(['iron-ore' => 3]);

        $createdPlayerItem = $this->createMock(PlayerItem::class);
        $this->playerItemGenerator->expects($this->once())
            ->method('generateFromItemId')
            ->with(42)
            ->willReturn($createdPlayerItem);

        $this->inventoryHelper->expects($this->once())
            ->method('addItem')
            ->with($createdPlayerItem, false);

        $this->qualityCalculator->method('calculateQuality')
            ->willReturn(QualityCalculator::QUALITY_NORMAL);

        $this->entityManager->expects($this->once())->method('flush');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(CraftEvent::class),
                CraftEvent::NAME
            );

        $result = $this->craftingManager->craft($player, $recipe);

        $this->assertTrue($result['success']);
        $this->assertSame($resultItem, $result['item']);
        $this->assertEquals(QualityCalculator::QUALITY_NORMAL, $result['quality']);
        $this->assertStringContainsString('Barre de fer', $result['message']);
        $this->assertStringContainsString('25 XP', $result['message']);
    }

    public function testCraftFailsWithMissingIngredients(): void
    {
        $recipe = new Recipe();
        $recipe->setName('Iron Bar');
        $recipe->setSlug('iron-bar');
        $recipe->setCraft('forge');
        $recipe->setIngredients([
            ['slug' => 'iron-ore', 'quantity' => 5],
        ]);

        $player = $this->createPlayerWithBagItems(['iron-ore' => 1]);

        $this->playerItemGenerator->expects($this->never())->method('generateFromItemId');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->craftingManager->craft($player, $recipe);

        $this->assertFalse($result['success']);
        $this->assertNull($result['item']);
        $this->assertStringContainsString('Ingredients manquants', $result['message']);
        $this->assertStringContainsString('iron-ore', $result['message']);
    }

    public function testCraftMultipleQuantityResult(): void
    {
        $resultItem = $this->createMock(Item::class);
        $resultItem->method('getId')->willReturn(10);
        $resultItem->method('getName')->willReturn('Flèche');

        $recipe = new Recipe();
        $recipe->setName('Flèches');
        $recipe->setSlug('arrows');
        $recipe->setCraft('forge');
        $recipe->setIngredients([
            ['slug' => 'wood', 'quantity' => 1],
        ]);
        $recipe->setResult($resultItem);
        $recipe->setResultQuantity(3);
        $recipe->setXpReward(10);

        $player = $this->createPlayerWithBagItems(['wood' => 2]);

        $this->playerItemGenerator->expects($this->exactly(3))
            ->method('generateFromItemId')
            ->with(10)
            ->willReturn($this->createMock(PlayerItem::class));

        $this->inventoryHelper->expects($this->exactly(3))->method('addItem');

        $this->qualityCalculator->method('calculateQuality')
            ->willReturn(QualityCalculator::QUALITY_NORMAL);

        $result = $this->craftingManager->craft($player, $recipe);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('x3', $result['message']);
    }

    public function testGetCraftingLevelFromDomainXp(): void
    {
        $domain = $this->createMock(Domain::class);
        $domain->method('getTitle')->willReturn('Forge');

        $domainExp = new DomainExperience();
        $domainExp->setDomain($domain);
        $domainExp->setTotalExperience(350);

        $player = $this->createMock(Player::class);
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection([$domainExp]));

        // 350 / 100 = 3.5, floor = 3, +1 = 4
        $level = $this->craftingManager->getCraftingLevel($player, 'forge');

        $this->assertEquals(4, $level);
    }

    public function testGetCraftingLevelDefaultsToOneWhenNoDomain(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection());

        $level = $this->craftingManager->getCraftingLevel($player, 'forge');

        $this->assertEquals(1, $level);
    }

    public function testGetAvailableRecipesFiltersbyLevel(): void
    {
        $recipe1 = new Recipe();
        $recipe1->setName('Basic');
        $recipe1->setSlug('basic');
        $recipe1->setCraft('forge');
        $recipe1->setRequiredLevel(1);

        $recipe2 = new Recipe();
        $recipe2->setName('Advanced');
        $recipe2->setSlug('advanced');
        $recipe2->setCraft('forge');
        $recipe2->setRequiredLevel(5);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findBy')->with(['craft' => 'forge'])->willReturn([$recipe1, $recipe2]);
        $this->entityManager->method('getRepository')
            ->with(Recipe::class)
            ->willReturn($repo);

        $player = $this->createMock(Player::class);
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection());

        // Level 1 (no domain XP) -> only recipe1 accessible
        $available = $this->craftingManager->getAvailableRecipes($player, 'forge');

        $this->assertCount(1, $available);
        $this->assertSame($recipe1, array_values($available)[0]);
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
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection());

        return $player;
    }
}
