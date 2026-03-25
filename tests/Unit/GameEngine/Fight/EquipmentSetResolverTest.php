<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\EquipmentSet;
use App\Entity\Game\EquipmentSetBonus;
use App\Entity\Game\Item;
use App\GameEngine\Fight\EquipmentSetResolver;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EquipmentSetResolverTest extends TestCase
{
    private GearHelper&MockObject $gearHelper;
    private EquipmentSetResolver $resolver;

    protected function setUp(): void
    {
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->resolver = new EquipmentSetResolver($this->gearHelper);
    }

    public function testNoActiveSetsWhenNothingEquipped(): void
    {
        $player = $this->createPlayerWithEquippedItems([]);

        $result = $this->resolver->getActiveSets($player);

        $this->assertEmpty($result);
    }

    public function testNoActiveSetsWhenEquippedItemsHaveNoSet(): void
    {
        $item = $this->createEquippedPlayerItem(null);
        $player = $this->createPlayerWithEquippedItems([$item]);

        $result = $this->resolver->getActiveSets($player);

        $this->assertEmpty($result);
    }

    public function testSingleSetPieceDetected(): void
    {
        $set = $this->createSet('guardian', 'Set du Gardien', 3);
        $this->addBonus($set, 2, 'protection', 3);

        $item = $this->createEquippedPlayerItem($set);
        $player = $this->createPlayerWithEquippedItems([$item]);

        $result = $this->resolver->getActiveSets($player);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('guardian', $result);
        $this->assertEquals(1, $result['guardian']['equippedCount']);
        $this->assertEmpty($result['guardian']['activeBonuses']);
        $this->assertCount(1, $result['guardian']['inactiveBonuses']);
    }

    public function testTwoPiecesActivateFirstBonus(): void
    {
        $set = $this->createSet('guardian', 'Set du Gardien', 4);
        $this->addBonus($set, 2, 'protection', 3);
        $this->addBonus($set, 3, 'life', 15);

        $item1 = $this->createEquippedPlayerItem($set);
        $item2 = $this->createEquippedPlayerItem($set);
        $player = $this->createPlayerWithEquippedItems([$item1, $item2]);

        $result = $this->resolver->getActiveSets($player);

        $this->assertEquals(2, $result['guardian']['equippedCount']);
        $this->assertCount(1, $result['guardian']['activeBonuses']);
        $this->assertCount(1, $result['guardian']['inactiveBonuses']);
        $this->assertEquals('protection', $result['guardian']['activeBonuses'][0]->getBonusType());
        $this->assertEquals(3, $result['guardian']['activeBonuses'][0]->getBonusValue());
    }

    public function testAllBonusesActiveWithEnoughPieces(): void
    {
        $set = $this->createSet('guardian', 'Set du Gardien', 4);
        $this->addBonus($set, 2, 'protection', 3);
        $this->addBonus($set, 3, 'life', 15);

        $items = [];
        for ($i = 0; $i < 3; $i++) {
            $items[] = $this->createEquippedPlayerItem($set);
        }
        $player = $this->createPlayerWithEquippedItems($items);

        $result = $this->resolver->getActiveSets($player);

        $this->assertEquals(3, $result['guardian']['equippedCount']);
        $this->assertCount(2, $result['guardian']['activeBonuses']);
        $this->assertEmpty($result['guardian']['inactiveBonuses']);
    }

    public function testGetSetBonusesSumCorrectly(): void
    {
        $set = $this->createSet('guardian', 'Set du Gardien', 4);
        $this->addBonus($set, 2, 'protection', 3);
        $this->addBonus($set, 3, 'life', 15);

        $items = [];
        for ($i = 0; $i < 3; $i++) {
            $items[] = $this->createEquippedPlayerItem($set);
        }
        $player = $this->createPlayerWithEquippedItems($items);

        $bonuses = $this->resolver->getSetBonuses($player);

        $this->assertEquals(3, $bonuses['protection']);
        $this->assertEquals(15, $bonuses['life']);
        $this->assertEquals(0, $bonuses['damage']);
        $this->assertEquals(0, $bonuses['heal']);
        $this->assertEquals(0, $bonuses['hit']);
        $this->assertEquals(0, $bonuses['critical']);
    }

    public function testMultipleSetsTrackedSeparately(): void
    {
        $set1 = $this->createSet('guardian', 'Set du Gardien', 3);
        $this->addBonus($set1, 2, 'protection', 3);

        $set2 = $this->createSet('shadow', "Set de l'Ombre", 3);
        $this->addBonus($set2, 2, 'damage', 5);

        $item1a = $this->createEquippedPlayerItem($set1);
        $item1b = $this->createEquippedPlayerItem($set1);
        $item2a = $this->createEquippedPlayerItem($set2);
        $player = $this->createPlayerWithEquippedItems([$item1a, $item1b, $item2a]);

        $result = $this->resolver->getActiveSets($player);

        $this->assertCount(2, $result);
        $this->assertEquals(2, $result['guardian']['equippedCount']);
        $this->assertCount(1, $result['guardian']['activeBonuses']);
        $this->assertEquals(1, $result['shadow']['equippedCount']);
        $this->assertEmpty($result['shadow']['activeBonuses']);

        $bonuses = $this->resolver->getSetBonuses($player);
        $this->assertEquals(3, $bonuses['protection']);
        $this->assertEquals(0, $bonuses['damage']); // Only 1 piece, need 2
    }

    /**
     * @param PlayerItem[] $equippedItems
     */
    private function createPlayerWithEquippedItems(array $equippedItems): Player&MockObject
    {
        $inventory = $this->createMock(Inventory::class);
        $inventory->method('isBag')->willReturn(true);
        $inventory->method('getItems')->willReturn(new ArrayCollection($equippedItems));

        $player = $this->createMock(Player::class);
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));

        // Configure gearHelper to mark all passed items as equipped
        $this->gearHelper->method('isEquipped')->willReturnCallback(
            fn (PlayerItem $item) => \in_array($item, $equippedItems, true)
        );

        return $player;
    }

    private function createEquippedPlayerItem(?EquipmentSet $set): PlayerItem&MockObject
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('getEquipmentSet')->willReturn($set);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($genericItem);

        return $playerItem;
    }

    private function createSet(string $slug, string $name, int $totalPieces): EquipmentSet
    {
        $set = new EquipmentSet();
        $set->setSlug($slug);
        $set->setName($name);
        $set->setDescription('Test set');

        // Populate items collection for totalPieces count
        for ($i = 0; $i < $totalPieces; $i++) {
            $item = new Item();
            $item->setName('piece_' . $i);
            $item->setDescription('');
            $item->setSlug($slug . '-piece-' . $i);
            $set->addItem($item);
        }

        return $set;
    }

    private function addBonus(EquipmentSet $set, int $requiredPieces, string $bonusType, int $bonusValue): void
    {
        $bonus = new EquipmentSetBonus();
        $bonus->setRequiredPieces($requiredPieces);
        $bonus->setBonusType($bonusType);
        $bonus->setBonusValue($bonusValue);
        $set->addBonus($bonus);
    }
}
