<?php

namespace App\Tests\Unit\Helper;

use App\Entity\App\Inventory;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Enum\Element;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GearHelperTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private GearHelper $gearHelper;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->gearHelper = new GearHelper($this->playerHelper);
    }

    public function testElementalBonusWithNoEquipment(): void
    {
        $inventory = $this->createInventoryWithItems([]);
        $this->playerHelper->method('getInventory')->willReturn($inventory);

        $bonus = $this->gearHelper->getEquippedElementalDamageBonus(Element::Fire);

        $this->assertSame(0.0, $bonus);
    }

    public function testElementalBonusWithNoneElementReturnsZero(): void
    {
        $inventory = $this->createInventoryWithItems([]);
        $this->playerHelper->method('getInventory')->willReturn($inventory);

        $bonus = $this->gearHelper->getEquippedElementalDamageBonus(Element::None);

        $this->assertSame(0.0, $bonus);
    }

    public function testElementalBonusWithOneMatchingPiece(): void
    {
        $items = [
            $this->createEquippedItem(PlayerItem::GEAR_HEAD, Element::Fire, '{"action":"elemental_damage_boost","element":"fire","amount":10}'),
        ];
        $inventory = $this->createInventoryWithItems($items);
        $this->playerHelper->method('getInventory')->willReturn($inventory);

        $bonus = $this->gearHelper->getEquippedElementalDamageBonus(Element::Fire);

        $this->assertEqualsWithDelta(0.10, $bonus, 0.001);
    }

    public function testElementalBonusWithMultipleMatchingPieces(): void
    {
        $items = [
            $this->createEquippedItem(PlayerItem::GEAR_HEAD, Element::Fire, '{"action":"elemental_damage_boost","element":"fire","amount":10}'),
            $this->createEquippedItem(PlayerItem::GEAR_CHEST, Element::Fire, '{"action":"elemental_damage_boost","element":"fire","amount":10}'),
            $this->createEquippedItem(PlayerItem::GEAR_FOOT, Element::Fire, '{"action":"elemental_damage_boost","element":"fire","amount":10}'),
        ];
        $inventory = $this->createInventoryWithItems($items);
        $this->playerHelper->method('getInventory')->willReturn($inventory);

        $bonus = $this->gearHelper->getEquippedElementalDamageBonus(Element::Fire);

        $this->assertEqualsWithDelta(0.30, $bonus, 0.001);
    }

    public function testElementalBonusIgnoresNonMatchingElement(): void
    {
        $items = [
            $this->createEquippedItem(PlayerItem::GEAR_HEAD, Element::Water, '{"action":"elemental_damage_boost","element":"water","amount":10}'),
        ];
        $inventory = $this->createInventoryWithItems($items);
        $this->playerHelper->method('getInventory')->willReturn($inventory);

        $bonus = $this->gearHelper->getEquippedElementalDamageBonus(Element::Fire);

        $this->assertSame(0.0, $bonus);
    }

    public function testElementalBonusIgnoresUnequippedItems(): void
    {
        $items = [
            $this->createEquippedItem(0, Element::Fire, '{"action":"elemental_damage_boost","element":"fire","amount":10}'),
        ];
        $inventory = $this->createInventoryWithItems($items);
        $this->playerHelper->method('getInventory')->willReturn($inventory);

        $bonus = $this->gearHelper->getEquippedElementalDamageBonus(Element::Fire);

        $this->assertSame(0.0, $bonus);
    }

    public function testElementalBonusIgnoresItemsWithoutEffect(): void
    {
        $items = [
            $this->createEquippedItem(PlayerItem::GEAR_HEAD, Element::Fire, null),
        ];
        $inventory = $this->createInventoryWithItems($items);
        $this->playerHelper->method('getInventory')->willReturn($inventory);

        $bonus = $this->gearHelper->getEquippedElementalDamageBonus(Element::Fire);

        $this->assertSame(0.0, $bonus);
    }

    /**
     * @param PlayerItem[] $items
     */
    private function createInventoryWithItems(array $items): Inventory&MockObject
    {
        $inventory = $this->createMock(Inventory::class);
        $inventory->method('getItems')->willReturn(new ArrayCollection($items));

        return $inventory;
    }

    private function createEquippedItem(int $gearBitmask, Element $element, ?string $effect): PlayerItem&MockObject
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('getElement')->willReturn($element);
        $genericItem->method('getEffect')->willReturn($effect);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGear')->willReturn($gearBitmask);
        $playerItem->method('getGenericItem')->willReturn($genericItem);

        return $playerItem;
    }
}
