<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Item;
use App\Enum\Element;
use App\GameEngine\Fight\LinkedMateriaResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkedMateriaResolverTest extends TestCase
{
    private LinkedMateriaResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new LinkedMateriaResolver();
    }

    private function createGenericItem(Element $element = Element::None): Item&MockObject
    {
        $item = $this->createMock(Item::class);
        $item->method('getElement')->willReturn($element);
        $item->method('isMateria')->willReturn(true);

        return $item;
    }

    private function createMateria(Element $element = Element::Fire): PlayerItem&MockObject
    {
        $genericItem = $this->createGenericItem($element);
        $materia = $this->createMock(PlayerItem::class);
        $materia->method('isMateria')->willReturn(true);
        $materia->method('getGenericItem')->willReturn($genericItem);

        return $materia;
    }

    private function createSlot(?PlayerItem $materia = null, ?Slot $linkedSlot = null): Slot&MockObject
    {
        $slot = $this->createMock(Slot::class);
        $slot->method('getItemSet')->willReturn($materia);
        $slot->method('getLinkedSlot')->willReturn($linkedSlot);

        return $slot;
    }

    public function testHasLinkedBonusWithSameElement(): void
    {
        $materia1 = $this->createMateria(Element::Fire);
        $materia2 = $this->createMateria(Element::Fire);

        $linkedSlot = $this->createSlot($materia2);
        $slot = $this->createSlot($materia1, $linkedSlot);

        $this->assertTrue($this->resolver->hasLinkedBonus($slot));
    }

    public function testNoLinkedBonusWithDifferentElements(): void
    {
        $materia1 = $this->createMateria(Element::Fire);
        $materia2 = $this->createMateria(Element::Water);

        $linkedSlot = $this->createSlot($materia2);
        $slot = $this->createSlot($materia1, $linkedSlot);

        $this->assertFalse($this->resolver->hasLinkedBonus($slot));
    }

    public function testNoLinkedBonusWithoutLinkedSlot(): void
    {
        $materia = $this->createMateria(Element::Fire);
        $slot = $this->createSlot($materia, null);

        $this->assertFalse($this->resolver->hasLinkedBonus($slot));
    }

    public function testNoLinkedBonusWithEmptyLinkedSlot(): void
    {
        $materia = $this->createMateria(Element::Fire);
        $linkedSlot = $this->createSlot(null);
        $slot = $this->createSlot($materia, $linkedSlot);

        $this->assertFalse($this->resolver->hasLinkedBonus($slot));
    }

    public function testNoLinkedBonusWithEmptyMainSlot(): void
    {
        $materia = $this->createMateria(Element::Fire);
        $linkedSlot = $this->createSlot($materia);
        $slot = $this->createSlot(null, $linkedSlot);

        $this->assertFalse($this->resolver->hasLinkedBonus($slot));
    }

    public function testNoLinkedBonusWithNoneElement(): void
    {
        $materia1 = $this->createMateria(Element::None);
        $materia2 = $this->createMateria(Element::None);

        $linkedSlot = $this->createSlot($materia2);
        $slot = $this->createSlot($materia1, $linkedSlot);

        $this->assertFalse($this->resolver->hasLinkedBonus($slot));
    }

    public function testDamageMultiplierWithLinkedBonus(): void
    {
        $materia1 = $this->createMateria(Element::Fire);
        $materia2 = $this->createMateria(Element::Fire);

        $linkedSlot = $this->createSlot($materia2);
        $slot = $this->createSlot($materia1, $linkedSlot);

        $this->assertEqualsWithDelta(1.15, $this->resolver->getDamageMultiplier($slot), 0.001);
    }

    public function testDamageMultiplierWithoutLinkedBonus(): void
    {
        $materia = $this->createMateria(Element::Fire);
        $slot = $this->createSlot($materia, null);

        $this->assertEqualsWithDelta(1.0, $this->resolver->getDamageMultiplier($slot), 0.001);
    }

    public function testLinkedBonusWithAllElements(): void
    {
        $elements = [Element::Fire, Element::Water, Element::Earth, Element::Air, Element::Light, Element::Dark, Element::Metal, Element::Beast];

        foreach ($elements as $element) {
            $materia1 = $this->createMateria($element);
            $materia2 = $this->createMateria($element);

            $linkedSlot = $this->createSlot($materia2);
            $slot = $this->createSlot($materia1, $linkedSlot);

            $this->assertTrue($this->resolver->hasLinkedBonus($slot), "Linked bonus should apply for element: {$element->value}");
        }
    }
}
