<?php

namespace App\Tests\Unit\Entity;

use App\Entity\App\PlayerItem;
use PHPUnit\Framework\TestCase;

/**
 * Couvre {@see PlayerItem::isExchangeable()} — la notion de « plancher T1
 * echangeable » de l'onboarding (NAR-04). Un objet est echangeable tant qu'il
 * n'est ni lie au joueur ni equipe.
 */
final class PlayerItemExchangeableTest extends TestCase
{
    public function testFreshItemIsExchangeable(): void
    {
        $item = new PlayerItem();

        // Non lie, non equipe (gear = 0) : echangeable.
        self::assertTrue($item->isExchangeable());
    }

    public function testBoundItemIsNotExchangeable(): void
    {
        $item = new PlayerItem();
        $item->setBoundToPlayerId(42);

        self::assertFalse($item->isExchangeable());
    }

    public function testEquippedItemIsNotExchangeable(): void
    {
        $item = new PlayerItem();
        $item->setGear(1);

        self::assertFalse($item->isExchangeable());
    }

    public function testEquippedAndBoundItemIsNotExchangeable(): void
    {
        $item = new PlayerItem();
        $item->setGear(1);
        $item->setBoundToPlayerId(7);

        self::assertFalse($item->isExchangeable());
    }

    public function testUnequippingRestoresExchangeability(): void
    {
        $item = new PlayerItem();
        $item->setGear(2);
        self::assertFalse($item->isExchangeable());

        $item->setGear(0);
        self::assertTrue($item->isExchangeable());
    }
}
