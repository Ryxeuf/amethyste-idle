<?php

namespace App\Tests\Unit\GameEngine\Item;

use App\Entity\App\Enchantment;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Enum\Element;
use App\GameEngine\Item\EnchantmentManager;
use App\Helper\GearHelper;
use App\Repository\EnchantmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnchantmentManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EnchantmentRepository&MockObject $enchantmentRepository;
    private GearHelper&MockObject $gearHelper;
    private EnchantmentManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->enchantmentRepository = $this->createMock(EnchantmentRepository::class);
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->manager = new EnchantmentManager(
            $this->entityManager,
            $this->enchantmentRepository,
            $this->gearHelper,
        );
    }

    public function testApplyCreatesEnchantment(): void
    {
        $playerItem = $this->createPlayerItem();
        $this->enchantmentRepository->method('findActiveByPlayerItem')->willReturn(null);

        $this->entityManager->expects($this->once())->method('persist');

        $enchantment = $this->manager->apply($playerItem, 'fire_edge');

        $this->assertSame($playerItem, $enchantment->getPlayerItem());
        $this->assertEquals('fire_edge', $enchantment->getType());
        $this->assertEquals('Tranchant de feu', $enchantment->getName());
        $this->assertEquals('damage', $enchantment->getStat());
        $this->assertEquals(5, $enchantment->getValue());
        $this->assertEquals(Element::Fire, $enchantment->getElement());
        $this->assertFalse($enchantment->isExpired());
        $this->assertGreaterThan(3500, $enchantment->getRemainingSeconds());
    }

    public function testApplyReplacesExistingEnchantment(): void
    {
        $playerItem = $this->createPlayerItem();
        $existing = new Enchantment();

        $this->enchantmentRepository->method('findActiveByPlayerItem')->willReturn($existing);

        $removed = false;
        $this->entityManager->expects($this->once())->method('remove')
            ->with($existing)
            ->willReturnCallback(function () use (&$removed): void { $removed = true; });
        $this->entityManager->expects($this->once())->method('persist');

        $enchantment = $this->manager->apply($playerItem, 'ice_ward');

        $this->assertTrue($removed);
        $this->assertEquals('ice_ward', $enchantment->getType());
        $this->assertEquals('Protection de glace', $enchantment->getName());
        $this->assertEquals('protection', $enchantment->getStat());
        $this->assertEquals(3, $enchantment->getValue());
        $this->assertEquals(Element::Water, $enchantment->getElement());
    }

    public function testApplyThrowsForUnknownType(): void
    {
        $playerItem = $this->createPlayerItem();

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->apply($playerItem, 'unknown_enchant');
    }

    public function testGetEquippedEnchantmentBonusesAggregates(): void
    {
        $player = $this->createMock(Player::class);

        $pi1 = $this->createPlayerItem();
        $pi2 = $this->createPlayerItem();

        $e1 = new Enchantment();
        $e1->setPlayerItem($pi1);
        $e1->setStat('damage');
        $e1->setValue(5);
        $e1->setExpiresAt(new \DateTime('+1 hour'));

        $e2 = new Enchantment();
        $e2->setPlayerItem($pi2);
        $e2->setStat('critical');
        $e2->setValue(3);
        $e2->setExpiresAt(new \DateTime('+1 hour'));

        $this->enchantmentRepository->method('findActiveByPlayer')->willReturn([$e1, $e2]);
        $this->gearHelper->method('isEquipped')->willReturn(true);

        $bonuses = $this->manager->getEquippedEnchantmentBonuses($player);

        $this->assertEquals(5, $bonuses['damage']);
        $this->assertEquals(3, $bonuses['critical']);
        $this->assertEquals(0, $bonuses['hit']);
        $this->assertEquals(0, $bonuses['heal']);
        $this->assertEquals(0, $bonuses['life']);
        $this->assertEquals(0, $bonuses['protection']);
    }

    public function testGetEquippedEnchantmentBonusesIgnoresUnequipped(): void
    {
        $player = $this->createMock(Player::class);

        $pi1 = $this->createPlayerItem();

        $e1 = new Enchantment();
        $e1->setPlayerItem($pi1);
        $e1->setStat('damage');
        $e1->setValue(5);
        $e1->setExpiresAt(new \DateTime('+1 hour'));

        $this->enchantmentRepository->method('findActiveByPlayer')->willReturn([$e1]);
        $this->gearHelper->method('isEquipped')->willReturn(false);

        $bonuses = $this->manager->getEquippedEnchantmentBonuses($player);

        $this->assertEquals(0, $bonuses['damage']);
    }

    public function testCanApplyWithSufficientIngredients(): void
    {
        $player = $this->createPlayerWithBagItems([
            'plant-dragonleaf' => 1,
            'ore-ruby' => 2,
        ]);

        $result = $this->manager->canApply($player, 'fire_edge');

        $this->assertTrue($result['possible']);
        $this->assertEmpty($result['missing']);
    }

    public function testCanApplyWithMissingIngredients(): void
    {
        $player = $this->createPlayerWithBagItems([
            'plant-dragonleaf' => 1,
        ]);

        $result = $this->manager->canApply($player, 'fire_edge');

        $this->assertFalse($result['possible']);
        $this->assertCount(1, $result['missing']);
        $this->assertEquals('ore-ruby', $result['missing'][0]['slug']);
        $this->assertEquals(0, $result['missing'][0]['have']);
        $this->assertEquals(1, $result['missing'][0]['need']);
    }

    public function testCanApplyReturnsFalseForUnknownType(): void
    {
        $player = $this->createMock(Player::class);

        $result = $this->manager->canApply($player, 'nonexistent');

        $this->assertFalse($result['possible']);
    }

    public function testIsValidTargetForWeapon(): void
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('isGear')->willReturn(true);
        $genericItem->method('getGearLocation')->willReturn('main_weapon');

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('isGear')->willReturn(true);
        $playerItem->method('getGenericItem')->willReturn($genericItem);

        $this->assertTrue($this->manager->isValidTarget($playerItem, 'fire_edge'));
        $this->assertFalse($this->manager->isValidTarget($playerItem, 'ice_ward'));
    }

    public function testIsValidTargetForArmor(): void
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('isGear')->willReturn(true);
        $genericItem->method('getGearLocation')->willReturn('chest');

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('isGear')->willReturn(true);
        $playerItem->method('getGenericItem')->willReturn($genericItem);

        $this->assertTrue($this->manager->isValidTarget($playerItem, 'ice_ward'));
        $this->assertFalse($this->manager->isValidTarget($playerItem, 'fire_edge'));
    }

    public function testIsValidTargetRejectNonGear(): void
    {
        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('isGear')->willReturn(false);

        $this->assertFalse($this->manager->isValidTarget($playerItem, 'fire_edge'));
    }

    public function testHasRequiredSkill(): void
    {
        $skill = $this->createMock(Skill::class);
        $skill->method('getSlug')->willReturn('alchi-buff-pot');

        $player = $this->createMock(Player::class);
        $player->method('getSkills')->willReturn(new ArrayCollection([$skill]));

        $this->assertTrue($this->manager->hasRequiredSkill($player, 'fire_edge'));
    }

    public function testHasRequiredSkillReturnsFalseWhenMissing(): void
    {
        $skill = $this->createMock(Skill::class);
        $skill->method('getSlug')->willReturn('alchi-health-pot');

        $player = $this->createMock(Player::class);
        $player->method('getSkills')->willReturn(new ArrayCollection([$skill]));

        $this->assertFalse($this->manager->hasRequiredSkill($player, 'fire_edge'));
    }

    public function testRemoveExpiredDelegates(): void
    {
        $this->enchantmentRepository->expects($this->once())->method('removeExpired')->willReturn(3);

        $result = $this->manager->removeExpired();

        $this->assertEquals(3, $result);
    }

    public function testEnchantmentExpiration(): void
    {
        $enchantment = new Enchantment();
        $enchantment->setExpiresAt(new \DateTime('-1 hour'));

        $this->assertTrue($enchantment->isExpired());
        $this->assertEquals(0, $enchantment->getRemainingSeconds());
    }

    public function testEnchantmentNotExpired(): void
    {
        $enchantment = new Enchantment();
        $enchantment->setExpiresAt(new \DateTime('+1 hour'));

        $this->assertFalse($enchantment->isExpired());
        $this->assertGreaterThan(3500, $enchantment->getRemainingSeconds());
    }

    private function createPlayerItem(): PlayerItem
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('isGear')->willReturn(true);
        $genericItem->method('getGearLocation')->willReturn('main_weapon');

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($genericItem);

        return $playerItem;
    }

    /**
     * @param array<string, int> $itemCounts
     */
    private function createPlayerWithBagItems(array $itemCounts): Player
    {
        $items = new ArrayCollection();

        foreach ($itemCounts as $slug => $count) {
            for ($i = 0; $i < $count; ++$i) {
                $genericItem = $this->createMock(Item::class);
                $genericItem->method('getSlug')->willReturn($slug);

                $playerItem = new PlayerItem();
                $playerItem->setGenericItem($genericItem);
                $items->add($playerItem);
            }
        }

        $inventory = $this->createMock(Inventory::class);
        $inventory->method('isBag')->willReturn(true);
        $inventory->method('getItems')->willReturn($items);

        $player = $this->createMock(Player::class);
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));

        return $player;
    }
}
