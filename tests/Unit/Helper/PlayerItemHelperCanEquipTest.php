<?php

namespace App\Tests\Unit\Helper;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\GameEngine\Fight\CombatSkillResolver;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerItemHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlayerItemHelperCanEquipTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private InventoryHelper&MockObject $inventoryHelper;
    private CombatSkillResolver&MockObject $combatSkillResolver;
    private TranslatorInterface&MockObject $translator;
    private PlayerItemHelper $helper;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        $this->combatSkillResolver = $this->createMock(CombatSkillResolver::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->helper = new PlayerItemHelper(
            $this->playerHelper,
            $this->inventoryHelper,
            $this->combatSkillResolver,
            $this->translator,
        );
    }

    public function testCanEquipItemWithNoRequirements(): void
    {
        $item = $this->createGearItem([]);
        $playerItem = $this->createPlayerItem($item);

        $player = $this->createPlayerWithSkills([]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->inventoryHelper->method('hasItem')->willReturn(true);

        $this->assertTrue($this->helper->canBeEquipped($playerItem));
    }

    public function testCannotEquipItemWhenMissingRequiredSkill(): void
    {
        $requiredSkill = $this->createSkill('berserk-weapon-t2');
        $item = $this->createGearItem([$requiredSkill]);
        $playerItem = $this->createPlayerItem($item);

        $player = $this->createPlayerWithSkills([]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->inventoryHelper->method('hasItem')->willReturn(true);

        $this->assertFalse($this->helper->canBeEquipped($playerItem));
    }

    public function testCanEquipItemWhenPlayerHasRequiredSkill(): void
    {
        $requiredSkill = $this->createSkill('berserk-weapon-t2');
        $item = $this->createGearItem([$requiredSkill]);
        $playerItem = $this->createPlayerItem($item);

        $player = $this->createPlayerWithSkills([$requiredSkill]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->inventoryHelper->method('hasItem')->willReturn(true);

        $this->assertTrue($this->helper->canBeEquipped($playerItem));
    }

    public function testCannotEquipItemWhenPlayerHasOnlyPartialSkills(): void
    {
        $skill1 = $this->createSkill('soldier-weapon-t2');
        $skill2 = $this->createSkill('soldier-weapon-t3');
        $item = $this->createGearItem([$skill1, $skill2]);
        $playerItem = $this->createPlayerItem($item);

        $player = $this->createPlayerWithSkills([$skill1]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->inventoryHelper->method('hasItem')->willReturn(true);

        $this->assertFalse($this->helper->canBeEquipped($playerItem));
    }

    public function testCannotEquipItemNotInInventory(): void
    {
        $item = $this->createGearItem([]);
        $playerItem = $this->createPlayerItem($item);

        $player = $this->createPlayerWithSkills([]);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->inventoryHelper->method('hasItem')->willReturn(false);

        $this->assertFalse($this->helper->canBeEquipped($playerItem));
    }

    private function createSkill(string $slug): Skill
    {
        $skill = new Skill();
        $skill->setSlug($slug);
        $skill->setTitle('Test Skill ' . $slug);
        $skill->setDescription('Test');
        $skill->setRequiredPoints(10);

        return $skill;
    }

    private function createGearItem(array $requirements): Item
    {
        $item = new Item();
        $item->setName('Test Weapon');
        $item->setSlug('test-weapon');
        $item->setDescription('A test weapon');
        $item->setType('gear');
        $item->setGearLocation('main_weapon');
        $item->setRequirements($requirements);

        return $item;
    }

    private function createPlayerItem(Item $genericItem): PlayerItem&MockObject
    {
        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($genericItem);

        return $playerItem;
    }

    private function createPlayerWithSkills(array $skills): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getSkills')->willReturn(new ArrayCollection($skills));

        return $player;
    }
}
