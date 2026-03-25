<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Enum\Element;

/**
 * Détecte les synergies entre slots materia liés.
 * Si 2 slots liés contiennent des materia du même élément (non-None),
 * un bonus de +15% dégâts est accordé.
 */
class LinkedMateriaResolver
{
    public const LINKED_DAMAGE_BONUS = 0.15;

    /**
     * Vérifie si un slot bénéficie d'un bonus de synergie avec son slot lié.
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

        if (!$materia->isMateria() || !$linkedMateria->isMateria()) {
            return false;
        }

        return $this->isSameElement($materia, $linkedMateria);
    }

    /**
     * Retourne le multiplicateur de dégâts lié (1.15 si bonus, 1.0 sinon).
     */
    public function getLinkedDamageMultiplier(Slot $slot): float
    {
        return $this->hasLinkedBonus($slot) ? 1.0 + self::LINKED_DAMAGE_BONUS : 1.0;
    }

    /**
     * Collecte tous les slots qui bénéficient du bonus linked sur l'équipement du joueur.
     *
     * @return Slot[] Slots avec bonus actif
     */
    public function getLinkedBonusSlots(Player $player): array
    {
        $bonusSlots = [];

        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() === 0) {
                    continue;
                }

                foreach ($playerItem->getSlots() as $slot) {
                    if ($this->hasLinkedBonus($slot)) {
                        $bonusSlots[] = $slot;
                    }
                }
            }
        }

        return $bonusSlots;
    }

    private function isSameElement(PlayerItem $materia1, PlayerItem $materia2): bool
    {
        $element1 = $materia1->getGenericItem()->getElement();
        $element2 = $materia2->getGenericItem()->getElement();

        if ($element1 === Element::None || $element2 === Element::None) {
            return false;
        }

        return $element1 === $element2;
    }
}
