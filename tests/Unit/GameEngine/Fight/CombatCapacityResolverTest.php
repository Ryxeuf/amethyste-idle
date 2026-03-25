<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\Enum\Element;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\LinkedMateriaResolver;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombatCapacityResolverTest extends TestCase
{
    private CombatCapacityResolver $resolver;
    private CombatSkillResolver&MockObject $combatSkillResolver;

    protected function setUp(): void
    {
        $this->combatSkillResolver = $this->createMock(CombatSkillResolver::class);
        // By default, all spells are unlocked
        $this->combatSkillResolver->method('getUnlockedMateriaSpellSlugs')->willReturn(['fireball', 'ice_bolt']);
        $this->resolver = new CombatCapacityResolver($this->combatSkillResolver, new LinkedMateriaResolver());
    }

    private function createSpell(string $slug, Element $element = Element::Fire): Spell&MockObject
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getSlug')->willReturn($slug);
        $spell->method('getElement')->willReturn($element);

        return $spell;
    }

    private function createGenericItem(?Spell $spell = null, Element $element = Element::None): Item&MockObject
    {
        $item = $this->createMock(Item::class);
        $item->method('getSpell')->willReturn($spell);
        $item->method('getElement')->willReturn($element);
        $item->method('isMateria')->willReturn(true);

        return $item;
    }

    private function createMateria(?Spell $spell = null, Element $element = Element::Fire): PlayerItem&MockObject
    {
        $genericItem = $this->createGenericItem($spell, $element);
        $materia = $this->createMock(PlayerItem::class);
        $materia->method('isMateria')->willReturn(true);
        $materia->method('getGenericItem')->willReturn($genericItem);

        return $materia;
    }

    private function createSlot(?PlayerItem $materia = null, ?Element $element = null): Slot&MockObject
    {
        $slot = $this->createMock(Slot::class);
        $slot->method('getItemSet')->willReturn($materia);
        $slot->method('getElement')->willReturn($element);

        return $slot;
    }

    private function createEquipment(array $slots, int $gear = PlayerItem::GEAR_CHEST): PlayerItem&MockObject
    {
        $equipment = $this->createMock(PlayerItem::class);
        $equipment->method('getGear')->willReturn($gear);
        $equipment->method('getSlots')->willReturn(new ArrayCollection($slots));
        $equipment->method('isMateria')->willReturn(false);

        return $equipment;
    }

    private function createPlayer(array $items): Player&MockObject
    {
        $inventory = $this->createMock(Inventory::class);
        $inventory->method('getItems')->willReturn(new ArrayCollection($items));

        $player = $this->createMock(Player::class);
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));

        return $player;
    }

    public function testNoMateriaEquippedReturnsEmptyArray(): void
    {
        $equipment = $this->createEquipment([]);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertEmpty($result);
    }

    public function testMateriaWithSpellReturnsSpell(): void
    {
        $spell = $this->createSpell('fireball', Element::Fire);
        $materia = $this->createMateria($spell, Element::Fire);
        $slot = $this->createSlot($materia, Element::Fire);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('fireball', $result);
        $this->assertSame($spell, $result['fireball']['spell']);
        $this->assertSame($materia, $result['fireball']['materia']);
        $this->assertTrue($result['fireball']['elementMatch']);
    }

    public function testMateriaWithoutSpellIsSkipped(): void
    {
        $materia = $this->createMateria(null, Element::Fire);
        $slot = $this->createSlot($materia, Element::Fire);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertEmpty($result);
    }

    public function testElementMatchBonusApplied(): void
    {
        $spell = $this->createSpell('fireball', Element::Fire);
        $materia = $this->createMateria($spell, Element::Fire);
        $slot = $this->createSlot($materia, Element::Fire);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertTrue($result['fireball']['elementMatch']);
    }

    public function testElementMismatchNoBonus(): void
    {
        $spell = $this->createSpell('fireball', Element::Fire);
        $materia = $this->createMateria($spell, Element::Fire);
        $slot = $this->createSlot($materia, Element::Water);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertFalse($result['fireball']['elementMatch']);
    }

    public function testDuplicateSpellsDeduplicated(): void
    {
        $spell = $this->createSpell('fireball', Element::Fire);
        $materia1 = $this->createMateria($spell, Element::Fire);
        $materia2 = $this->createMateria($spell, Element::Fire);
        $slot1 = $this->createSlot($materia1, Element::Water);
        $slot2 = $this->createSlot($materia2, Element::Fire);
        $equipment = $this->createEquipment([$slot1, $slot2]);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertCount(1, $result);
        // Should keep the one with element match
        $this->assertTrue($result['fireball']['elementMatch']);
    }

    public function testHasMateriaSpellReturnsTrue(): void
    {
        $spell = $this->createSpell('fireball', Element::Fire);
        $materia = $this->createMateria($spell, Element::Fire);
        $slot = $this->createSlot($materia, Element::Fire);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $this->assertTrue($this->resolver->hasMateriaSpell($player, 'fireball'));
    }

    public function testHasMateriaSpellReturnsFalse(): void
    {
        $equipment = $this->createEquipment([]);
        $player = $this->createPlayer([$equipment]);

        $this->assertFalse($this->resolver->hasMateriaSpell($player, 'fireball'));
    }

    public function testEmptySlotSkipped(): void
    {
        $slot = $this->createSlot(null, Element::Fire);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertEmpty($result);
    }

    public function testUnequippedGearIsSkipped(): void
    {
        $spell = $this->createSpell('fireball', Element::Fire);
        $materia = $this->createMateria($spell, Element::Fire);
        $slot = $this->createSlot($materia, Element::Fire);
        $equipment = $this->createEquipment([$slot], gear: 0);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertEmpty($result);
    }

    public function testNonMateriaItemInSlotIsSkipped(): void
    {
        $nonMateria = $this->createMock(PlayerItem::class);
        $nonMateria->method('isMateria')->willReturn(false);

        $slot = $this->createSlot($nonMateria, Element::Fire);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertEmpty($result);
    }

    public function testIsElementMatchWithNullSlotElement(): void
    {
        $materia = $this->createMateria(null, Element::Fire);
        $slot = $this->createSlot($materia, null);

        $this->assertFalse($this->resolver->isElementMatch($slot, $materia));
    }

    public function testIsElementMatchWithNoneElement(): void
    {
        $materia = $this->createMateria(null, Element::None);
        $slot = $this->createSlot($materia, Element::None);

        $this->assertFalse($this->resolver->isElementMatch($slot, $materia));
    }

    public function testGetElementMatchDamageMultiplierWithMatch(): void
    {
        $materia = $this->createMateria(null, Element::Fire);
        $slot = $this->createSlot($materia, Element::Fire);

        $this->assertEqualsWithDelta(1.25, $this->resolver->getElementMatchDamageMultiplier($slot, $materia), 0.001);
    }

    public function testGetElementMatchDamageMultiplierWithoutMatch(): void
    {
        $materia = $this->createMateria(null, Element::Fire);
        $slot = $this->createSlot($materia, Element::Water);

        $this->assertEqualsWithDelta(1.0, $this->resolver->getElementMatchDamageMultiplier($slot, $materia), 0.001);
    }

    public function testFindMateriaSpellReturnsEntry(): void
    {
        $spell = $this->createSpell('fireball', Element::Fire);
        $materia = $this->createMateria($spell, Element::Fire);
        $slot = $this->createSlot($materia, Element::Fire);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $entry = $this->resolver->findMateriaSpell($player, 'fireball');

        $this->assertNotNull($entry);
        $this->assertSame($spell, $entry['spell']);
    }

    public function testFindMateriaSpellReturnsNullWhenNotFound(): void
    {
        $equipment = $this->createEquipment([]);
        $player = $this->createPlayer([$equipment]);

        $entry = $this->resolver->findMateriaSpell($player, 'fireball');

        $this->assertNull($entry);
    }

    public function testMultipleMateriaOnDifferentGearPieces(): void
    {
        $spell1 = $this->createSpell('fireball', Element::Fire);
        $spell2 = $this->createSpell('ice_bolt', Element::Water);

        $materia1 = $this->createMateria($spell1, Element::Fire);
        $materia2 = $this->createMateria($spell2, Element::Water);

        $slot1 = $this->createSlot($materia1, Element::Fire);
        $slot2 = $this->createSlot($materia2, Element::Water);

        $chest = $this->createEquipment([$slot1], PlayerItem::GEAR_CHEST);
        $weapon = $this->createEquipment([$slot2], PlayerItem::GEAR_MAIN_WEAPON);

        $player = $this->createPlayer([$chest, $weapon]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('fireball', $result);
        $this->assertArrayHasKey('ice_bolt', $result);
    }

    public function testUnlockedSpellHasLockedFalse(): void
    {
        $spell = $this->createSpell('fireball', Element::Fire);
        $materia = $this->createMateria($spell, Element::Fire);
        $slot = $this->createSlot($materia, Element::Fire);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $result = $this->resolver->getEquippedMateriaSpells($player);

        $this->assertFalse($result['fireball']['locked']);
    }

    public function testLockedSpellWhenSkillMissing(): void
    {
        // Override the default mock to return no unlocked slugs
        $combatSkillResolver = $this->createMock(CombatSkillResolver::class);
        $combatSkillResolver->method('getUnlockedMateriaSpellSlugs')->willReturn([]);
        $resolver = new CombatCapacityResolver($combatSkillResolver, new LinkedMateriaResolver());

        $spell = $this->createSpell('fireball', Element::Fire);
        $materia = $this->createMateria($spell, Element::Fire);
        $slot = $this->createSlot($materia, Element::Fire);
        $equipment = $this->createEquipment([$slot]);
        $player = $this->createPlayer([$equipment]);

        $result = $resolver->getEquippedMateriaSpells($player);

        $this->assertCount(1, $result);
        $this->assertTrue($result['fireball']['locked']);
    }

    public function testPartiallyUnlockedSpells(): void
    {
        // Only fireball is unlocked, not ice_bolt
        $combatSkillResolver = $this->createMock(CombatSkillResolver::class);
        $combatSkillResolver->method('getUnlockedMateriaSpellSlugs')->willReturn(['fireball']);
        $resolver = new CombatCapacityResolver($combatSkillResolver, new LinkedMateriaResolver());

        $spell1 = $this->createSpell('fireball', Element::Fire);
        $spell2 = $this->createSpell('ice_bolt', Element::Water);

        $materia1 = $this->createMateria($spell1, Element::Fire);
        $materia2 = $this->createMateria($spell2, Element::Water);

        $slot1 = $this->createSlot($materia1, Element::Fire);
        $slot2 = $this->createSlot($materia2, Element::Water);

        $equipment = $this->createEquipment([$slot1, $slot2]);
        $player = $this->createPlayer([$equipment]);

        $result = $resolver->getEquippedMateriaSpells($player);

        $this->assertCount(2, $result);
        $this->assertFalse($result['fireball']['locked']);
        $this->assertTrue($result['ice_bolt']['locked']);
    }
}
