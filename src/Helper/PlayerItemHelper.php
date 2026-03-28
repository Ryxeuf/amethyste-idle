<?php

namespace App\Helper;

use App\Entity\App\PlayerItem;
use App\GameEngine\Fight\CombatSkillResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlayerItemHelper
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly InventoryHelper $inventoryHelper,
        private readonly CombatSkillResolver $combatSkillResolver,
        private readonly TranslatorInterface $translator,
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
        if ($player === null) {
            return false;
        }

        return $this->combatSkillResolver->hasUnlockedMateriaSpell($player, $spell->getSlug());
    }

    /**
     * Message traduit expliquant pourquoi la materia ne peut pas être sertie, ou null si OK.
     */
    public function getMateriaSocketBlockMessage(PlayerItem $materia): ?string
    {
        if (!$materia->isMateria()) {
            return $this->translator->trans('game.inventory.materia_socket.block_not_materia');
        }

        if (!$this->inventoryHelper->hasItem($materia)) {
            return $this->translator->trans('game.inventory.materia_socket.block_not_owned');
        }

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->translator->trans('game.inventory.materia_socket.block_no_player');
        }

        $generic = $materia->getGenericItem();
        $requirements = $generic->getRequirements();
        if ($requirements->count() > 0) {
            $missingNames = [];
            foreach ($requirements as $skill) {
                if (!$player->getSkills()->contains($skill)) {
                    $missingNames[] = $skill->getTitle();
                }
            }
            if ($missingNames !== []) {
                return $this->translator->trans('game.inventory.materia_socket.block_requirements', [
                    '%skills%' => implode(', ', $missingNames),
                ]);
            }
        }

        $spell = $generic->getSpell();
        if ($spell === null) {
            return $this->translator->trans('game.inventory.materia_socket.block_no_spell');
        }

        if (!$this->combatSkillResolver->hasUnlockedMateriaSpell($player, $spell->getSlug())) {
            return $this->translator->trans('game.inventory.materia_socket.block_unlock', [
                '%spell%' => $spell->getName(),
            ]);
        }

        return null;
    }
}
