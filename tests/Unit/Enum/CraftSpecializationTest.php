<?php

namespace App\Tests\Unit\Enum;

use App\Enum\CraftSpecialization;
use PHPUnit\Framework\TestCase;

class CraftSpecializationTest extends TestCase
{
    public function testAllSpecializationsExist(): void
    {
        $this->assertCount(4, CraftSpecialization::cases());
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
