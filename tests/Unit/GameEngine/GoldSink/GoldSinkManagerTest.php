<?php

namespace App\Tests\Unit\GameEngine\GoldSink;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\GameEngine\GoldSink\GoldSinkManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GoldSinkManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private GoldSinkManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->manager = new GoldSinkManager($this->entityManager);
    }

    public function testRenameItemSuccess(): void
    {
        $player = $this->createPlayerWithGils(100);
        $playerItem = $this->createPlayerItem();

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->manager->renameItem($player, $playerItem, 'Excalibur');

        $this->assertTrue($result['success']);
        $this->assertSame('Excalibur', $playerItem->getCustomName());
        $this->assertSame(50, $player->getGils());
    }

    public function testRenameItemInsufficientGils(): void
    {
        $player = $this->createPlayerWithGils(10);
        $playerItem = $this->createPlayerItem();

        $result = $this->manager->renameItem($player, $playerItem, 'Excalibur');

        $this->assertFalse($result['success']);
        $this->assertNull($playerItem->getCustomName());
    }

    public function testRenameItemInvalidName(): void
    {
        $player = $this->createPlayerWithGils(100);
        $playerItem = $this->createPlayerItem();

        $result = $this->manager->renameItem($player, $playerItem, '');
        $this->assertFalse($result['success']);

        $result = $this->manager->renameItem($player, $playerItem, '<script>alert(1)</script>');
        $this->assertFalse($result['success']);
    }

    public function testRepairItemSuccess(): void
    {
        $player = $this->createPlayerWithGils(500);
        $item = $this->createItem(100, 'common');
        $playerItem = $this->createPlayerItemWithDurability($item, 50);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->manager->repairItem($player, $playerItem);

        $this->assertTrue($result['success']);
        $this->assertSame(100, $playerItem->getCurrentDurability());
    }

    public function testRepairCostScalesWithRarity(): void
    {
        $commonItem = $this->createItem(100, 'common');
        $epicItem = $this->createItem(100, 'epic');

        $commonPi = $this->createPlayerItemWithDurability($commonItem, 50);
        $epicPi = $this->createPlayerItemWithDurability($epicItem, 50);

        $commonCost = $this->manager->getRepairCost($commonPi);
        $epicCost = $this->manager->getRepairCost($epicPi);

        $this->assertGreaterThan($commonCost, $epicCost);
    }

    public function testRepairItemAlreadyFull(): void
    {
        $player = $this->createPlayerWithGils(500);
        $item = $this->createItem(100, 'common');
        $playerItem = $this->createPlayerItemWithDurability($item, 100);

        $result = $this->manager->repairItem($player, $playerItem);

        $this->assertFalse($result['success']);
    }

    public function testDegradeEquippedItems(): void
    {
        $item = $this->createItem(100, 'common');
        $equippedPi = $this->createPlayerItemWithDurability($item, 100);
        $equippedPi->setGear(PlayerItem::GEAR_MAIN_WEAPON);

        $unequippedPi = $this->createPlayerItemWithDurability($item, 100);
        $unequippedPi->setGear(0);

        $inventory = $this->createMock(Inventory::class);
        $inventory->method('getItems')->willReturn(new ArrayCollection([$equippedPi, $unequippedPi]));

        $player = $this->createMock(Player::class);
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));

        $degraded = $this->manager->degradeEquippedItems($player);

        $this->assertSame(1, $degraded);
        $this->assertSame(90, $equippedPi->getCurrentDurability());
        $this->assertSame(100, $unequippedPi->getCurrentDurability());
    }

    public function testDisplayNameReturnsCustomNameIfSet(): void
    {
        $item = $this->createItem(100, 'common');
        $item->setName('Epee en fer');

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($item);

        $this->assertSame('Epee en fer', $playerItem->getDisplayName());

        $playerItem->setCustomName('Ma lame');
        $this->assertSame('Ma lame', $playerItem->getDisplayName());
    }

    private function createPlayerWithGils(int $gils): Player
    {
        $player = new Player();
        $player->setGils($gils);
        $player->setName('TestPlayer');
        $player->setMaxLife(100);
        $player->setLife(100);
        $player->setEnergy(50);
        $player->setMaxEnergy(50);
        $player->setClassType('warrior');
        $player->setCoordinates('5.5');
        $player->setLastCoordinates('5.5');

        return $player;
    }

    private function createPlayerItem(): PlayerItem
    {
        $item = new Item();
        $item->setName('Test Item');
        $item->setSlug('test-item');
        $item->setDescription('Test');
        $item->setType(Item::TYPE_STUFF);

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($item);

        return $playerItem;
    }

    private function createItem(int $durability, string $rarity): Item
    {
        $item = new Item();
        $item->setName('Test Item');
        $item->setSlug('test-item');
        $item->setDescription('Test');
        $item->setType(Item::TYPE_TOOL);
        $item->setDurability($durability);
        $item->setRarity($rarity);

        return $item;
    }

    private function createPlayerItemWithDurability(Item $item, int $currentDurability): PlayerItem
    {
        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($item);
        $playerItem->setCurrentDurability($currentDurability);

        return $playerItem;
    }
}
