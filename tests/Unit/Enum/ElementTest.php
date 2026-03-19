<?php

namespace App\Tests\Unit\Enum;

use App\Enum\Element;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
    public function testAllElementsExist(): void
    {
        $this->assertSame('none', Element::None->value);
        $this->assertSame('fire', Element::Fire->value);
        $this->assertSame('water', Element::Water->value);
        $this->assertSame('earth', Element::Earth->value);
        $this->assertSame('air', Element::Air->value);
        $this->assertSame('light', Element::Light->value);
        $this->assertSame('dark', Element::Dark->value);
        $this->assertSame('metal', Element::Metal->value);
        $this->assertSame('beast', Element::Beast->value);
    }

    public function testCasesCountIs9(): void
    {
        $this->assertCount(9, Element::cases());
    }

    public function testLabelsAreFrench(): void
    {
        $this->assertSame('Aucun', Element::None->label());
        $this->assertSame('Feu', Element::Fire->label());
        $this->assertSame('Eau', Element::Water->label());
        $this->assertSame('Terre', Element::Earth->label());
        $this->assertSame('Air', Element::Air->label());
        $this->assertSame('Lumière', Element::Light->label());
        $this->assertSame('Ténèbres', Element::Dark->label());
        $this->assertSame('Métal', Element::Metal->label());
        $this->assertSame('Bête', Element::Beast->label());
    }

    public function testChoicesReturnsAllElements(): void
    {
        $choices = Element::choices();

        $this->assertCount(9, $choices);
        $this->assertSame(Element::None, $choices['Aucun']);
        $this->assertSame(Element::Fire, $choices['Feu']);
        $this->assertSame(Element::Metal, $choices['Métal']);
        $this->assertSame(Element::Beast, $choices['Bête']);
    }

    public function testFromStringValue(): void
    {
        $this->assertSame(Element::Fire, Element::from('fire'));
        $this->assertSame(Element::Metal, Element::from('metal'));
        $this->assertSame(Element::Beast, Element::from('beast'));
    }

    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(Element::tryFrom('invalid'));
        $this->assertNull(Element::tryFrom('life'));
        $this->assertNull(Element::tryFrom('death'));
        $this->assertNull(Element::tryFrom('nature'));
        $this->assertNull(Element::tryFrom('wind'));
    }
}
