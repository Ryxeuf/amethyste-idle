<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Gear;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\GameEngine\Gear\GearSetter;
use App\Helper\GearHelper;
use App\Service\Avatar\AvatarHashRecalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GearSetterTest extends TestCase
{
    private GearHelper&MockObject $gearHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private AvatarHashRecalculator&MockObject $avatarHashRecalculator;
    private GearSetter $gearSetter;

    protected function setUp(): void
    {
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->avatarHashRecalculator = $this->createMock(AvatarHashRecalculator::class);
        $this->gearSetter = new GearSetter(
            $this->gearHelper,
            $this->entityManager,
            $this->avatarHashRecalculator,
        );
    }

    public function testSetGearRecalculatesAvatarHashForPlayer(): void
    {
        $player = new Player();
        $inventory = new Inventory();
        $inventory->setPlayer($player);

        $genericItem = $this->createMock(Item::class);
        $genericItem->method('getGearLocation')->willReturn(Item::GEAR_LOCATION_CHEST);

        $gear = $this->createMock(PlayerItem::class);
        $gear->method('isGear')->willReturn(true);
        $gear->method('getGenericItem')->willReturn($genericItem);
        $gear->method('getInventory')->willReturn($inventory);

        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);
        $this->gearHelper->method('getPlayerItemGearByLocation')->willReturn(PlayerItem::GEAR_CHEST);

        $this->avatarHashRecalculator->expects($this->once())
            ->method('recalculate')
            ->with($player);

        $this->gearSetter->setGear($gear);
    }

    public function testUnsetGearRecalculatesAvatarHashWhenFlushing(): void
    {
        $player = new Player();
        $inventory = new Inventory();
        $inventory->setPlayer($player);

        $gear = $this->createMock(PlayerItem::class);
        $gear->method('getInventory')->willReturn($inventory);

        $this->gearHelper->method('isEquipped')->willReturn(true);

        $this->avatarHashRecalculator->expects($this->once())
            ->method('recalculate')
            ->with($player);

        $this->gearSetter->unsetGear($gear, true);
    }

    public function testUnsetGearSkipsRecalculationWhenNotFlushing(): void
    {
        $gear = $this->createMock(PlayerItem::class);

        $this->gearHelper->method('isEquipped')->willReturn(true);

        $this->avatarHashRecalculator->expects($this->never())->method('recalculate');

        $this->gearSetter->unsetGear($gear, false);
    }

    public function testSetGearSkipsRecalculationWhenPlayerMissing(): void
    {
        $inventory = new Inventory();
        // no player attached

        $genericItem = $this->createMock(Item::class);
        $genericItem->method('getGearLocation')->willReturn(Item::GEAR_LOCATION_CHEST);

        $gear = $this->createMock(PlayerItem::class);
        $gear->method('isGear')->willReturn(true);
        $gear->method('getGenericItem')->willReturn($genericItem);
        $gear->method('getInventory')->willReturn($inventory);

        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);
        $this->gearHelper->method('getPlayerItemGearByLocation')->willReturn(PlayerItem::GEAR_CHEST);

        $this->avatarHashRecalculator->expects($this->never())->method('recalculate');

        $this->gearSetter->setGear($gear);
    }
}
