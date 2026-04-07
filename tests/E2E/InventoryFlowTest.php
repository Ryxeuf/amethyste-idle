<?php

namespace App\Tests\E2E;

/**
 * E2E : Parcours inventaire complet.
 * equiper item → stats changent → desequiper → stats reviennent.
 */
class InventoryFlowTest extends AbstractE2ETestCase
{
    public function testEquipmentPageIsAccessible(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/inventory');
        $this->waitForTurbo();
        $this->waitForSelector('.equip-stat-pill--atk');

        $this->assertSelectorExists('.equip-stat-pill--atk');
        $this->assertSelectorExists('.equip-stat-pill--def');
        $this->assertSelectorExists('.equip-stat-pill--spd');
    }

    public function testEquipItemChangesStats(): void
    {
        $this->login();

        // 1. Navigate to inventory equipment tab
        static::$pantherClient->request('GET', '/game/inventory');
        $this->waitForTurbo();
        $this->waitForSelector('.equip-stat-pill--atk');

        // 2. Read initial stats
        $initialStats = $this->readEquipmentStats();
        $this->assertNotNull($initialStats['atk'], 'Le stat ATK doit etre lisible');
        $this->assertNotNull($initialStats['def'], 'Le stat DEF doit etre lisible');

        // 3. Find an available gear item to equip (with DEF > 0)
        $equipFormAction = static::$pantherClient->executeScript(
            "const items = document.querySelectorAll('.equip-available-item');
             for (const item of items) {
                 const def = item.dataset.itemProtection;
                 if (def && parseInt(def) > 0) {
                     const form = item.querySelector('form[action*=\"equipment/equip\"]');
                     return form ? form.action : null;
                 }
             }
             return null;"
        );

        if (null === $equipFormAction) {
            $this->markTestSkipped('Aucun equipement avec DEF > 0 disponible dans les fixtures.');
        }

        // 4. Submit the equip form via JS (turbo frame)
        static::$pantherClient->executeScript(sprintf(
            "const form = document.querySelector('form[action*=\"equipment/equip\"][action$=\"%s\"]')
                ?? document.querySelector('.equip-available-item form[action*=\"equipment/equip\"]');
             if (form) form.requestSubmit();",
            substr($equipFormAction, strrpos($equipFormAction, '/'))
        ));

        // Wait for turbo frame update
        $this->waitForTurbo();
        $this->waitForSelector('.equip-stat-pill--def');

        // 5. Read new stats — DEF should have increased
        $afterEquipStats = $this->readEquipmentStats();
        $this->assertGreaterThanOrEqual(
            $initialStats['def'],
            $afterEquipStats['def'],
            'La DEF doit augmenter ou rester egale apres equipement'
        );
    }

    public function testEquipAndUnequipRestoresStats(): void
    {
        $this->login();

        // 1. Ensure we start on inventory page with equipment loaded
        static::$pantherClient->request('GET', '/game/inventory');
        $this->waitForTurbo();
        $this->waitForSelector('.equip-stat-pill--def');

        // 2. Check if there is already an equipped item we can unequip
        $hasEquippedItem = static::$pantherClient->executeScript(
            "return document.querySelector('.equip-unequip-btn') !== null;"
        );

        if (!$hasEquippedItem) {
            // Equip an item first
            $equipped = $this->equipFirstAvailableGear();
            if (!$equipped) {
                $this->markTestSkipped('Aucun equipement disponible dans les fixtures.');
            }
            $this->waitForTurbo();
            $this->waitForSelector('.equip-stat-pill--def');
        }

        // 3. Read stats with item equipped
        $equippedStats = $this->readEquipmentStats();

        // 4. Find and click the first unequip button
        static::$pantherClient->executeScript(
            "document.querySelector('.equip-unequip-btn').closest('form').requestSubmit();"
        );

        $this->waitForTurbo();
        $this->waitForSelector('.equip-stat-pill--def');

        // 5. Read stats after unequip
        $afterUnequipStats = $this->readEquipmentStats();

        // 6. DEF should have decreased (or stayed same if other items)
        $this->assertLessThanOrEqual(
            $equippedStats['def'],
            $afterUnequipStats['def'],
            'La DEF doit diminuer ou rester egale apres desequipement'
        );
    }

    public function testFullEquipUnequipCycle(): void
    {
        $this->login();

        // 1. Load inventory
        static::$pantherClient->request('GET', '/game/inventory');
        $this->waitForTurbo();
        $this->waitForSelector('.equip-stat-pill--def');

        // 2. Unequip all gear first to get a clean baseline
        for ($i = 0; $i < 12; ++$i) {
            $hasBtn = static::$pantherClient->executeScript(
                "const btn = document.querySelector('.equip-unequip-btn');
                 if (btn) { btn.closest('form').requestSubmit(); return true; }
                 return false;"
            );
            if (!$hasBtn) {
                break;
            }
            $this->waitForTurbo();
            $this->waitForSelector('.equip-stat-pill--def');
        }

        // 3. Read baseline stats (nothing equipped)
        $baselineStats = $this->readEquipmentStats();

        // 4. Equip an item with protection > 0
        $itemProtection = static::$pantherClient->executeScript(
            "const items = document.querySelectorAll('.equip-available-item');
             for (const item of items) {
                 const def = item.dataset.itemProtection;
                 if (def && parseInt(def) > 0) {
                     const form = item.querySelector('form[action*=\"equipment/equip\"]');
                     if (form) {
                         form.requestSubmit();
                         return parseInt(def);
                     }
                 }
             }
             return null;"
        );

        if (null === $itemProtection) {
            $this->markTestSkipped('Aucun equipement avec DEF > 0 pour le test de cycle complet.');
        }

        $this->waitForTurbo();
        $this->waitForSelector('.equip-stat-pill--def');

        // 5. Verify stats increased
        $afterEquipStats = $this->readEquipmentStats();
        $this->assertGreaterThan(
            $baselineStats['def'],
            $afterEquipStats['def'],
            'La DEF doit augmenter apres equipement'
        );

        // 6. Unequip the item
        static::$pantherClient->executeScript(
            "document.querySelector('.equip-unequip-btn').closest('form').requestSubmit();"
        );

        $this->waitForTurbo();
        $this->waitForSelector('.equip-stat-pill--def');

        // 7. Verify stats returned to baseline
        $restoredStats = $this->readEquipmentStats();
        $this->assertSame(
            $baselineStats['def'],
            $restoredStats['def'],
            'La DEF doit revenir a la valeur initiale apres desequipement'
        );
    }

    /**
     * Read current ATK/DEF/SPD stats from the equipment page.
     *
     * @return array{atk: int, def: int, spd: int}
     */
    private function readEquipmentStats(): array
    {
        return static::$pantherClient->executeScript(
            "const atk = document.querySelector('.equip-stat-pill--atk .font-semibold.tabular-nums');
             const def = document.querySelector('.equip-stat-pill--def .font-semibold.tabular-nums');
             const spd = document.querySelector('.equip-stat-pill--spd .font-semibold.tabular-nums');
             return {
                 atk: atk ? parseInt(atk.textContent.trim()) : 0,
                 def: def ? parseInt(def.textContent.trim()) : 0,
                 spd: spd ? parseInt(spd.textContent.trim()) : 0,
             };"
        );
    }

    /**
     * Equip the first available gear item.
     */
    private function equipFirstAvailableGear(): bool
    {
        return (bool) static::$pantherClient->executeScript(
            "const form = document.querySelector('.equip-available-item form[action*=\"equipment/equip\"]');
             if (form) { form.requestSubmit(); return true; }
             return false;"
        );
    }
}
