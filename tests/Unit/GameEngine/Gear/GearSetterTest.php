<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Gear;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\GameEngine\Gear\GearSetter;
use App\GameEngine\Realtime\Avatar\AvatarUpdatedPublisher;
use App\Helper\GearHelper;
use App\Service\Avatar\AvatarHashGenerator;
use App\Service\Avatar\AvatarHashRecalculator;
use App\Service\Avatar\ItemAvatarSheetResolver;
use App\Service\Avatar\PlayerAvatarPayloadBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GearSetterTest extends TestCase
{
    private GearHelper&MockObject $gearHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private AvatarHashRecalculator $avatarHashRecalculator;
    private GearSetter $gearSetter;

    protected function setUp(): void
    {
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->avatarHashRecalculator = new AvatarHashRecalculator(
            new PlayerAvatarPayloadBuilder(
                new AvatarHashGenerator(),
                $this->gearHelper,
                new ItemAvatarSheetResolver(),
            ),
            $this->entityManager,
            $this->createMock(AvatarUpdatedPublisher::class),
        );
        $this->gearSetter = new GearSetter(
            $this->gearHelper,
            $this->entityManager,
            $this->avatarHashRecalculator,
        );
    }

    public function testSetGearRecalculatesAvatarHashForPlayerWithAvatar(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
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

        $this->assertNull($player->getAvatarHash());

        $this->gearSetter->setGear($gear);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $player->getAvatarHash());
    }

    public function testSetGearSkipsRecalculationForPlayerWithoutAvatar(): void
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

        $this->gearSetter->setGear($gear);

        $this->assertNull($player->getAvatarHash());
    }

    public function testUnsetGearRecalculatesAvatarHashWhenFlushing(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $player->setAvatarHash(str_repeat('0', 64));

        $inventory = new Inventory();
        $inventory->setPlayer($player);

        $gear = $this->createMock(PlayerItem::class);
        $gear->method('getInventory')->willReturn($inventory);

        $this->gearHelper->method('isEquipped')->willReturn(true);
        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);

        $this->gearSetter->unsetGear($gear, true);

        $this->assertNotSame(str_repeat('0', 64), $player->getAvatarHash());
    }

    public function testUnsetGearSkipsRecalculationWhenNotFlushing(): void
    {
        $player = new Player();
        $player->setAvatarAppearance(['body' => 'human_m_light']);
        $unchangedHash = str_repeat('0', 64);
        $player->setAvatarHash($unchangedHash);

        $inventory = new Inventory();
        $inventory->setPlayer($player);

        $gear = $this->createMock(PlayerItem::class);
        $gear->method('getInventory')->willReturn($inventory);

        $this->gearHelper->method('isEquipped')->willReturn(true);

        $this->gearSetter->unsetGear($gear, false);

        $this->assertSame($unchangedHash, $player->getAvatarHash());
    }

    public function testSetGearSkipsRecalculationWhenInventoryHasNoPlayer(): void
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

        // Does not throw, recalculator short-circuits on null player.
        $this->gearSetter->setGear($gear);
        $this->addToAssertionCount(1);
    }
}
