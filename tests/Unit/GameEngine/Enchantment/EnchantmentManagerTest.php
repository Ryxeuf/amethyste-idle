<?php

namespace App\Tests\Unit\GameEngine\Enchantment;

use App\Entity\App\Enchantment;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\EnchantmentDefinition;
use App\Entity\Game\Item;
use App\Enum\Element;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Enchantment\EnchantmentManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnchantmentManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private CraftingManager&MockObject $craftingManager;
    private EntityRepository&MockObject $enchantmentRepo;
    private EntityRepository&MockObject $definitionRepo;
    private EnchantmentManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->craftingManager = $this->createMock(CraftingManager::class);
        $this->enchantmentRepo = $this->createMock(EntityRepository::class);
        $this->definitionRepo = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function (string $class) {
                if ($class === Enchantment::class) {
                    return $this->enchantmentRepo;
                }
                if ($class === EnchantmentDefinition::class) {
                    return $this->definitionRepo;
                }

                return $this->createMock(EntityRepository::class);
            });

        $this->manager = new EnchantmentManager($this->entityManager, $this->craftingManager);
    }

    public function testGetAvailableDefinitionsFiltersbyLevel(): void
    {
        $def1 = $this->createDefinition('fire-blade', 1);
        $def2 = $this->createDefinition('ice-protection', 1);
        $def3 = $this->createDefinition('earth-fortitude', 3);

        $this->definitionRepo->method('findAll')->willReturn([$def1, $def2, $def3]);
        $this->craftingManager->method('getCraftingLevel')->willReturn(2);

        $player = $this->createMock(Player::class);
        $result = $this->manager->getAvailableDefinitions($player);

        $this->assertCount(2, $result);
    }

    public function testCanEnchantRejectsNonGearItem(): void
    {
        $player = $this->createMock(Player::class);
        $playerItem = $this->createPlayerItem(isGear: false, gearSlot: 0);
        $definition = $this->createDefinition('fire-blade', 1);

        $result = $this->manager->canEnchant($player, $playerItem, $definition);

        $this->assertFalse($result['possible']);
        $this->assertStringContainsString('equipements', $result['reason']);
    }

    public function testCanEnchantRejectsUnequippedItem(): void
    {
        $player = $this->createMock(Player::class);
        $playerItem = $this->createPlayerItem(isGear: true, gearSlot: 0);
        $definition = $this->createDefinition('fire-blade', 1);

        $result = $this->manager->canEnchant($player, $playerItem, $definition);

        $this->assertFalse($result['possible']);
        $this->assertStringContainsString('equipe', $result['reason']);
    }

    public function testCanEnchantRejectsInsufficientLevel(): void
    {
        $player = $this->createMock(Player::class);
        $playerItem = $this->createPlayerItem(isGear: true, gearSlot: PlayerItem::GEAR_MAIN_WEAPON);
        $definition = $this->createDefinition('earth-fortitude', 5);

        $this->craftingManager->method('getCraftingLevel')->willReturn(2);
        $this->enchantmentRepo->method('findBy')->willReturn([]);

        $result = $this->manager->canEnchant($player, $playerItem, $definition);

        $this->assertFalse($result['possible']);
        $this->assertStringContainsString('alchimiste', $result['reason']);
    }

    public function testCanEnchantRejectsAlreadyEnchanted(): void
    {
        $player = $this->createMock(Player::class);
        $playerItem = $this->createPlayerItem(isGear: true, gearSlot: PlayerItem::GEAR_MAIN_WEAPON);
        $definition = $this->createDefinition('fire-blade', 1);

        $this->craftingManager->method('getCraftingLevel')->willReturn(5);

        $existingEnchantment = $this->createMock(Enchantment::class);
        $existingEnchantment->method('isExpired')->willReturn(false);
        $this->enchantmentRepo->method('findBy')->willReturn([$existingEnchantment]);

        $result = $this->manager->canEnchant($player, $playerItem, $definition);

        $this->assertFalse($result['possible']);
        $this->assertStringContainsString('deja', $result['reason']);
    }

    public function testCanEnchantRejectsMissingIngredients(): void
    {
        $player = $this->createPlayerWithBag([]);
        $playerItem = $this->createPlayerItem(isGear: true, gearSlot: PlayerItem::GEAR_MAIN_WEAPON);
        $definition = $this->createDefinition('fire-blade', 1, [
            ['slug' => 'plant-sage', 'quantity' => 2],
        ]);

        $this->craftingManager->method('getCraftingLevel')->willReturn(5);
        $this->enchantmentRepo->method('findBy')->willReturn([]);

        $result = $this->manager->canEnchant($player, $playerItem, $definition);

        $this->assertFalse($result['possible']);
        $this->assertStringContainsString('manquant', $result['reason']);
    }

    public function testCanEnchantSucceedsWithValidConditions(): void
    {
        $player = $this->createPlayerWithBag(['plant-sage' => 3, 'magic-crystal' => 1]);
        $playerItem = $this->createPlayerItem(isGear: true, gearSlot: PlayerItem::GEAR_MAIN_WEAPON);
        $definition = $this->createDefinition('fire-blade', 1, [
            ['slug' => 'plant-sage', 'quantity' => 2],
            ['slug' => 'magic-crystal', 'quantity' => 1],
        ], 50);

        $this->craftingManager->method('getCraftingLevel')->willReturn(5);
        $this->enchantmentRepo->method('findBy')->willReturn([]);

        $result = $this->manager->canEnchant($player, $playerItem, $definition);

        $this->assertTrue($result['possible']);
    }

    public function testApplyCreatesEnchantment(): void
    {
        $player = $this->createPlayerWithBag(['plant-sage' => 3, 'magic-crystal' => 1]);
        $playerItem = $this->createPlayerItem(isGear: true, gearSlot: PlayerItem::GEAR_MAIN_WEAPON);
        $definition = $this->createDefinition('fire-blade', 1, [
            ['slug' => 'plant-sage', 'quantity' => 2],
            ['slug' => 'magic-crystal', 'quantity' => 1],
        ], 50);

        $this->craftingManager->method('getCraftingLevel')->willReturn(5);
        $this->enchantmentRepo->method('findBy')->willReturn([]);

        $persisted = [];
        $this->entityManager->method('persist')
            ->willReturnCallback(function ($entity) use (&$persisted) {
                $persisted[] = $entity;
            });

        $result = $this->manager->apply($player, $playerItem, $definition);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['enchantment']);
        $this->assertInstanceOf(Enchantment::class, $result['enchantment']);

        $enchantments = array_filter($persisted, fn ($e) => $e instanceof Enchantment);
        $this->assertNotEmpty($enchantments);
    }

    public function testCleanExpiredRemovesExpiredEnchantments(): void
    {
        $expired = $this->createMock(Enchantment::class);
        $expired->method('isExpired')->willReturn(true);

        $active = $this->createMock(Enchantment::class);
        $active->method('isExpired')->willReturn(false);

        $this->enchantmentRepo->method('findAll')->willReturn([$expired, $active]);

        $removed = [];
        $this->entityManager->method('remove')
            ->willReturnCallback(function ($entity) use (&$removed) {
                $removed[] = $entity;
            });

        $count = $this->manager->cleanExpired();

        $this->assertSame(1, $count);
        $this->assertCount(1, $removed);
    }

    public function testGetEnchantmentBonusesAggregatesStats(): void
    {
        $def1 = $this->createDefinition('fire-blade', 1);
        $def1->setStatBonuses(['damage' => 5]);

        $def2 = $this->createDefinition('light-precision', 1);
        $def2->setStatBonuses(['hit' => 8, 'damage' => 2]);

        $enchant1 = $this->createMock(Enchantment::class);
        $enchant1->method('isExpired')->willReturn(false);
        $enchant1->method('getDefinition')->willReturn($def1);

        $enchant2 = $this->createMock(Enchantment::class);
        $enchant2->method('isExpired')->willReturn(false);
        $enchant2->method('getDefinition')->willReturn($def2);

        $playerItem1 = $this->createPlayerItem(isGear: true, gearSlot: PlayerItem::GEAR_MAIN_WEAPON);
        $playerItem2 = $this->createPlayerItem(isGear: true, gearSlot: PlayerItem::GEAR_CHEST);

        $this->enchantmentRepo->method('findBy')
            ->willReturnOnConsecutiveCalls([$enchant1], [$enchant2]);

        $inventory = $this->createMock(Inventory::class);
        $inventory->method('getItems')->willReturn(new ArrayCollection([$playerItem1, $playerItem2]));

        $player = $this->createMock(Player::class);
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));

        $bonuses = $this->manager->getEnchantmentBonuses($player);

        $this->assertSame(7, $bonuses['damage']);
        $this->assertSame(8, $bonuses['hit']);
    }

    // --- Helpers ---

    private function createDefinition(string $slug, int $requiredLevel, array $ingredients = [], int $cost = 0): EnchantmentDefinition
    {
        $def = new EnchantmentDefinition();
        $def->setSlug($slug);
        $def->setName(ucfirst(str_replace('-', ' ', $slug)));
        $def->setElement(Element::Fire);
        $def->setStatBonuses(['damage' => 5]);
        $def->setDuration(3600);
        $def->setIngredients($ingredients);
        $def->setRequiredLevel($requiredLevel);
        $def->setCost($cost);

        return $def;
    }

    private function createPlayerItem(bool $isGear, int $gearSlot): PlayerItem
    {
        $item = $this->createMock(Item::class);
        $item->method('isGear')->willReturn($isGear);
        $item->method('getName')->willReturn('Epee de test');

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('isGear')->willReturn($isGear);
        $playerItem->method('getGear')->willReturn($gearSlot);
        $playerItem->method('getGenericItem')->willReturn($item);
        $playerItem->method('getId')->willReturn(random_int(1, 1000));

        return $playerItem;
    }

    private function createPlayerWithBag(array $itemSlugs): Player
    {
        $items = new ArrayCollection();

        foreach ($itemSlugs as $slug => $count) {
            for ($i = 0; $i < $count; ++$i) {
                $genericItem = $this->createMock(Item::class);
                $genericItem->method('getSlug')->willReturn($slug);
                $genericItem->method('getName')->willReturn($slug);

                $playerItem = $this->createMock(PlayerItem::class);
                $playerItem->method('getGenericItem')->willReturn($genericItem);
                $playerItem->method('getGear')->willReturn(0);
                $playerItem->method('isGear')->willReturn(false);

                $items->add($playerItem);
            }
        }

        $inventory = $this->createMock(Inventory::class);
        $inventory->method('isBag')->willReturn(true);
        $inventory->method('getItems')->willReturn($items);

        $player = $this->createMock(Player::class);
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));
        $player->method('getGils')->willReturn(1000);

        return $player;
    }
}
