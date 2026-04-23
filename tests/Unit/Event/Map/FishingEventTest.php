<?php

namespace App\Tests\Unit\Event\Map;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Event\Map\FishingEvent;
use PHPUnit\Framework\TestCase;

class FishingEventTest extends TestCase
{
    public function testIsPerfectWhenFlagAndCatch(): void
    {
        $event = new FishingEvent(
            $this->createMock(Player::class),
            $this->createMock(ObjectLayer::class),
            $this->createMock(PlayerItem::class),
            true,
        );

        $this->assertTrue($event->isSuccess());
        $this->assertTrue($event->isPerfect());
    }

    public function testIsNotPerfectWithoutFlag(): void
    {
        $event = new FishingEvent(
            $this->createMock(Player::class),
            $this->createMock(ObjectLayer::class),
            $this->createMock(PlayerItem::class),
        );

        $this->assertTrue($event->isSuccess());
        $this->assertFalse($event->isPerfect());
    }

    public function testIsNotPerfectWithoutCatchEvenIfFlagged(): void
    {
        $event = new FishingEvent(
            $this->createMock(Player::class),
            $this->createMock(ObjectLayer::class),
            null,
            true,
        );

        $this->assertFalse($event->isSuccess());
        $this->assertFalse($event->isPerfect());
    }
}
