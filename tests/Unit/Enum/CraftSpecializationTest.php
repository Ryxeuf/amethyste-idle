<?php

namespace App\Tests\Unit\Enum;

use App\Enum\CraftSpecialization;
use PHPUnit\Framework\TestCase;

class CraftSpecializationTest extends TestCase
{
    /**
     * Les sept arbres d'artisanat, depuis DOM-04.
     *
     * Ils etaient quatre : cuisinier, charpentier et tailleur avaient des arbres
     * et des recettes depuis ECO-29/30/31, mais aucune facon de s'y specialiser.
     * Le tailleur pouvait etre le seul de la region a coudre des robes sans que
     * rien ne le dise.
     */
    public function testEveryCraftTreeIsDeclared(): void
    {
        $this->assertSame([
            'forgeron',
            'tanneur',
            'alchimiste',
            'joaillier',
            'cuisinier',
            'charpentier',
            'tailleur',
        ], array_map(static fn (CraftSpecialization $c): string => $c->value, CraftSpecialization::cases()));
    }

    public function testCraftSlugMatchesEnumValue(): void
    {
        foreach (CraftSpecialization::cases() as $spec) {
            $this->assertSame($spec->value, $spec->craftSlug());
        }
    }

    public function testLabelStartsWithMaitre(): void
    {
        foreach (CraftSpecialization::cases() as $spec) {
            $this->assertStringStartsWith('Maitre ', $spec->label());
        }
    }

    public function testDescriptionIsNotEmpty(): void
    {
        foreach (CraftSpecialization::cases() as $spec) {
            $this->assertNotEmpty($spec->description());
        }
    }

    public function testTryFromKnownCraft(): void
    {
        $this->assertSame(CraftSpecialization::Forgeron, CraftSpecialization::tryFrom('forgeron'));
        $this->assertSame(CraftSpecialization::Alchimiste, CraftSpecialization::tryFrom('alchimiste'));
        $this->assertNull(CraftSpecialization::tryFrom('inconnu'));
    }
}
