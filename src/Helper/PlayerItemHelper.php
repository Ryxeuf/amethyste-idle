<?php

namespace App\Helper;

use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\CombatSkillResolver;

class PlayerItemHelper
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly InventoryHelper $inventoryHelper,
        private readonly CombatSkillResolver $combatSkillResolver,
    ) {
    }

    /**
     * Return if a player can equip an item he has.
     */
    public function canBeEquipped(PlayerItem $item): bool
    {
        if ($item->getGenericItem()->getRequirements()->count() === 0) {
            $playerMeetRequirements = true;
        } else {
            $intersect = array_intersect($item->getGenericItem()->getRequirements()->toArray(), $this->playerHelper->getPlayer()->getSkills()->toArray());
            $playerMeetRequirements = count($intersect) === $item->getGenericItem()->getRequirements()->count();
        }

        return $this->inventoryHelper->hasItem($item) && $playerMeetRequirements;
    }

    /**
     * Return if a player can equip a materia (must be a materia + meet skill requirements + have materia unlock skill).
     */
    public function canEquipMateria(PlayerItem $materia): bool
    {
        if (!$materia->isMateria()) {
            return false;
        }

        if (!$this->canBeEquipped($materia)) {
            return false;
        }

        // Check that the player has the materia.unlock skill for this materia's spell
        $spell = $materia->getGenericItem()->getSpell();
        if ($spell === null) {
            return false;
        }

        $player = $this->playerHelper->getPlayer();

        return $this->combatSkillResolver->hasUnlockedMateriaSpell($player, $spell->getSlug());
    }
}
