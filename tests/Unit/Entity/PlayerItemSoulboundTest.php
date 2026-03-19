<?php

namespace App\Tests\Unit\Entity;

use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
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

    public function testItemBoundToPlayerFlag(): void
    {
        $item = new Item();
        $this->assertFalse($item->isBoundToPlayer());

        $item->setBoundToPlayer(true);
        $this->assertTrue($item->isBoundToPlayer());
    }
}
