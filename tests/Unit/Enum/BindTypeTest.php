<?php

namespace App\Tests\Unit\Enum;

use App\Enum\BindType;
use PHPUnit\Framework\TestCase;

/**
 * ECO-01 : fondation de l'economie de production joueur.
 */
class BindTypeTest extends TestCase
{
    public function testLegacyFlagMapsToPickupBinding(): void
    {
        // L'ancien booleen `Item.boundToPlayer` ne savait exprimer que
        // « lie des l'obtention » : la migration doit conserver ce sens.
        $this->assertSame(BindType::BindOnPickup, BindType::fromLegacyFlag(true));
        $this->assertSame(BindType::None, BindType::fromLegacyFlag(false));
    }

    public function testOnlyPickupBindingBlocksTradeBeforeUse(): void
    {
        $this->assertTrue(BindType::None->isTradableBeforeUse());
        // Un objet lie a l'equipement circule tant qu'il n'a pas ete porte :
        // c'est precisement ce qui le distingue du lie-a-l'obtention.
        $this->assertTrue(BindType::BindOnEquip->isTradableBeforeUse());
        $this->assertFalse(BindType::BindOnPickup->isTradableBeforeUse());
    }

    public function testValuesAreStableForPersistence(): void
    {
        // Les valeurs sont ecrites en base (colonne `bind_type`) : les changer
        // casserait les donnees existantes.
        $this->assertSame('none', BindType::None->value);
        $this->assertSame('bind_on_equip', BindType::BindOnEquip->value);
        $this->assertSame('bind_on_pickup', BindType::BindOnPickup->value);
    }
}
