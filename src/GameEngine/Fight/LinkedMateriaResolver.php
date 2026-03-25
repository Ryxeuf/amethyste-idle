<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\App\Slot;
use App\Enum\Element;

class LinkedMateriaResolver
{
    public const LINKED_MATERIA_DAMAGE_BONUS = 0.15;

    /**
     * Check if a slot has a linked slot and both contain materia of the same element.
     */
    public function hasLinkedBonus(Slot $slot): bool
    {
        $linked = $slot->getLinkedSlot();
        if ($linked === null) {
            return false;
        }

        $materia = $slot->getItemSet();
        $linkedMateria = $linked->getItemSet();

        if ($materia === null || $linkedMateria === null) {
            return false;
        }

        $element = $materia->getGenericItem()->getElement();
        $linkedElement = $linkedMateria->getGenericItem()->getElement();

        if ($element === Element::None || $linkedElement === Element::None) {
            return false;
        }

        return $element === $linkedElement;
    }

    /**
     * Get the damage multiplier for linked materia synergy.
     * Returns 1.15 if both linked slots have same-element materia, 1.0 otherwise.
     */
    public function getDamageMultiplier(Slot $slot): float
    {
        return $this->hasLinkedBonus($slot) ? 1.0 + self::LINKED_MATERIA_DAMAGE_BONUS : 1.0;
    }

    /**
     * Get all linked materia bonuses for a player's equipped materia spells.
     *
     * @return array<string, bool> Keyed by spell slug, true if linked bonus applies
     */
    public function getLinkedBonuses(Player $player): array
    {
        $bonuses = [];

        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() === 0) {
                    continue;
                }

                foreach ($playerItem->getSlots() as $slot) {
                    $materia = $slot->getItemSet();
                    if ($materia === null || !$materia->isMateria()) {
                        continue;
                    }

                    $spell = $materia->getGenericItem()->getSpell();
                    if ($spell === null) {
                        continue;
                    }

                    if ($this->hasLinkedBonus($slot)) {
                        $bonuses[$spell->getSlug()] = true;
                    }
                }
            }
        }

        return $bonuses;
    }
}
