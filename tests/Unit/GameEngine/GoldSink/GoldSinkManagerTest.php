<?php

namespace App\Tests\Unit\GameEngine\GoldSink;

use App\Entity\App\Inventory;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use App\Entity\Game\Item;
use App\GameEngine\GoldSink\GoldSinkManager;
use App\Repository\PlayerVisitedRegionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GoldSinkManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerVisitedRegionRepository&MockObject $visitedRegionRepository;
    private GoldSinkManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->visitedRegionRepository = $this->createMock(PlayerVisitedRegionRepository::class);
        $this->manager = new GoldSinkManager($this->entityManager, $this->visitedRegionRepository);
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

    public function testGetAvailableDestinationsKeepsOnlyVisitedRegionsExceptCurrent(): void
    {
        $currentRegion = $this->createRegion(1, 'capital_a');
        $visitedRegion = $this->createRegion(2, 'capital_b');
        $unvisitedRegion = $this->createRegion(3, 'capital_c');
        $regionWithoutCapital = $this->createRegion(4, null);

        $regionRepository = $this->createMock(EntityRepository::class);
        $regionRepository->method('findAll')->willReturn([
            $currentRegion,
            $visitedRegion,
            $unvisitedRegion,
            $regionWithoutCapital,
        ]);
        $this->entityManager->method('getRepository')->with(Region::class)->willReturn($regionRepository);

        $player = $this->createPlayerOnRegion($currentRegion);

        $this->visitedRegionRepository->method('findVisitedRegionIds')->with($player)->willReturn([1, 2]);

        $destinations = $this->manager->getAvailableDestinations($player);

        $this->assertCount(1, $destinations);
        $this->assertSame($visitedRegion, $destinations[0]);
    }

    public function testFastTravelRejectsUnvisitedRegion(): void
    {
        $currentRegion = $this->createRegion(1, 'capital_a');
        $destination = $this->createRegion(2, 'capital_b');

        $player = $this->createPlayerOnRegion($currentRegion);
        $player->setGils(500);

        $this->visitedRegionRepository->method('hasVisited')->with($player, $destination)->willReturn(false);
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->manager->fastTravel($player, $destination);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('decouvrir', $result['message']);
        $this->assertSame(500, $player->getGils());
    }

    public function testFastTravelSucceedsWhenDestinationVisited(): void
    {
        $currentRegion = $this->createRegion(1, 'capital_a');
        $destinationCapital = new Map();
        $destinationCapital->setAreaWidth(20);
        $destinationCapital->setAreaHeight(10);
        $destination = $this->createRegion(2, 'capital_b', $destinationCapital);

        $player = $this->createPlayerOnRegion($currentRegion);
        $player->setGils(500);

        $this->visitedRegionRepository->method('hasVisited')->with($player, $destination)->willReturn(true);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->manager->fastTravel($player, $destination);

        $this->assertTrue($result['success']);
        $this->assertSame($destinationCapital, $player->getMap());
        $this->assertSame('10.5', $player->getCoordinates());
        $this->assertSame(400, $player->getGils());
    }

    public function testFastTravelStillRejectsCurrentRegionEvenIfVisited(): void
    {
        $currentRegion = $this->createRegion(1, 'capital_a');
        $player = $this->createPlayerOnRegion($currentRegion);
        $player->setGils(500);

        $this->visitedRegionRepository->expects($this->never())->method('hasVisited');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->manager->fastTravel($player, $currentRegion);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('deja dans cette region', $result['message']);
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

    private function createRegion(int $id, ?string $capitalName, ?Map $capitalMap = null): Region
    {
        $region = new Region();
        $reflection = new \ReflectionClass($region);
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($region, $id);

        $region->setName('region-' . $id);
        $region->setSlug('region-' . $id);

        if ($capitalMap !== null) {
            $region->setCapitalMap($capitalMap);
        } elseif ($capitalName !== null) {
            $map = new Map();
            $map->setAreaWidth(10);
            $map->setAreaHeight(10);
            $region->setCapitalMap($map);
        }

        return $region;
    }

    private function createPlayerOnRegion(Region $region): Player
    {
        $map = new Map();
        $map->setAreaWidth(10);
        $map->setAreaHeight(10);
        $map->setRegion($region);

        $player = new Player();
        $player->setName('TestPlayer');
        $player->setMaxLife(100);
        $player->setLife(100);
        $player->setEnergy(50);
        $player->setMaxEnergy(50);
        $player->setClassType('warrior');
        $player->setCoordinates('5.5');
        $player->setLastCoordinates('5.5');
        $player->setMap($map);

        return $player;
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
