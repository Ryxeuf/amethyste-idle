<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Enum\Element;

class GearBonusResolver
{
    /**
     * Get total element damage boost percentage from equipped gear.
     * Scans all equipped items for {"element_damage_boost": N} in their effect field.
     *
     * @return array<string, int> Keyed by element value (e.g. ['fire' => 20, 'water' => 10])
     */
    public function getElementDamageBoosts(Player $player): array
    {
        $boosts = [];

        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() === 0) {
                    continue;
                }

                $item = $playerItem->getGenericItem();
                $effect = $item->getEffect();
                if ($effect === null) {
                    continue;
                }

                $data = json_decode($effect, true);
                if (!is_array($data) || !isset($data['element_damage_boost'])) {
                    continue;
                }

                $element = $item->getElement();
                if ($element === Element::None) {
                    continue;
                }

                $elementKey = $element->value;
                $boosts[$elementKey] = ($boosts[$elementKey] ?? 0) + (int) $data['element_damage_boost'];
            }
        }

        return $boosts;
    }

    /**
     * Get the element damage boost for a specific spell element.
     * Returns the boost percentage (e.g. 10 for +10%).
     */
    public function getBoostForElement(Player $player, Element $element): int
    {
        if ($element === Element::None) {
            return 0;
        }

        $boosts = $this->getElementDamageBoosts($player);

        return $boosts[$element->value] ?? 0;
    }
}
