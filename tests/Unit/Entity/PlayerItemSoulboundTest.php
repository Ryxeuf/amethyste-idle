<?php

namespace App\Tests\Unit\Entity;

use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Enum\BindType;
use PHPUnit\Framework\TestCase;

class PlayerItemSoulboundTest extends TestCase
{
    public function testIsBoundReturnsFalseByDefault(): void
    {
        $playerItem = new PlayerItem();
        $this->assertFalse($playerItem->isBound());
        $this->assertNull($playerItem->getBoundToPlayerId());
    }

    public function testIsBoundReturnsTrueWhenBound(): void
    {
        $playerItem = new PlayerItem();
        $playerItem->setBoundToPlayerId(42);

        $this->assertTrue($playerItem->isBound());
        $this->assertSame(42, $playerItem->getBoundToPlayerId());
    }

    public function testUnbindItem(): void
    {
        $playerItem = new PlayerItem();
        $playerItem->setBoundToPlayerId(42);

        $this->assertTrue($playerItem->isBound());

        $playerItem->setBoundToPlayerId(null);
        $this->assertFalse($playerItem->isBound());
    }

    public function testItemBindTypeDefaultsToTradable(): void
    {
        $item = new Item();

        $this->assertSame(BindType::None, $item->getBindType());
        $this->assertFalse($item->isBoundOnPickup());
        $this->assertFalse($item->isBoundOnEquip());
    }

    public function testItemBindTypeDistinguishesPickupFromEquip(): void
    {
        // ECO-01 : l'ancien booleen ne savait exprimer que « lie des l'obtention ».
        $item = new Item();

        $item->setBindType(BindType::BindOnPickup);
        $this->assertTrue($item->isBoundOnPickup());
        $this->assertFalse($item->isBoundOnEquip());

        $item->setBindType(BindType::BindOnEquip);
        $this->assertFalse($item->isBoundOnPickup());
        $this->assertTrue($item->isBoundOnEquip());
    }
}
