<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Spell;
use App\Enum\Element;

class CombatCapacityResolver
{
    public const ELEMENT_MATCH_DAMAGE_BONUS = 0.25;
    public const ELEMENT_MATCH_XP_BONUS = 0.25;

    public function __construct(
        private readonly CombatSkillResolver $combatSkillResolver,
    ) {
    }

    /**
     * Get available combat spells from equipped materia.
     * Each entry includes a 'locked' flag indicating if the player lacks the skill unlock.
     *
     * @return array<string, array{spell: Spell, materia: PlayerItem, slot: Slot, elementMatch: bool, locked: bool}>
     *                                                                                                               Keyed by spell slug, deduplicated (best match wins)
     */
    public function getEquippedMateriaSpells(Player $player): array
    {
        $materiaSpells = [];
        $unlockedSlugs = $this->combatSkillResolver->getUnlockedMateriaSpellSlugs($player);

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

                    $elementMatch = $this->isElementMatch($slot, $materia);
                    $slug = $spell->getSlug();
                    $locked = !in_array($slug, $unlockedSlugs, true);

                    // Deduplicate: keep the one with element match if possible
                    if (isset($materiaSpells[$slug]) && $materiaSpells[$slug]['elementMatch'] && !$elementMatch) {
                        continue;
                    }

                    $materiaSpells[$slug] = [
                        'spell' => $spell,
                        'materia' => $materia,
                        'slot' => $slot,
                        'elementMatch' => $elementMatch,
                        'locked' => $locked,
                    ];
                }
            }
        }

        return $materiaSpells;
    }

    /**
     * Check if a player has access to a spell via equipped materia.
     */
    public function hasMateriaSpell(Player $player, string $spellSlug): bool
    {
        $materiaSpells = $this->getEquippedMateriaSpells($player);

        return isset($materiaSpells[$spellSlug]);
    }

    /**
     * Find the spell entry for a given slug from equipped materia.
     *
     * @return array{spell: Spell, materia: PlayerItem, slot: Slot, elementMatch: bool, locked: bool}|null
     */
    public function findMateriaSpell(Player $player, string $spellSlug): ?array
    {
        $materiaSpells = $this->getEquippedMateriaSpells($player);

        return $materiaSpells[$spellSlug] ?? null;
    }

    /**
     * Check if slot element matches materia element (both must be non-None).
     */
    public function isElementMatch(Slot $slot, PlayerItem $materia): bool
    {
        $slotElement = $slot->getElement();
        $materiaElement = $materia->getGenericItem()->getElement();

        if ($slotElement === null) {
            return false;
        }

        if ($slotElement === Element::None || $materiaElement === Element::None) {
            return false;
        }

        return $slotElement === $materiaElement;
    }

    /**
     * Get the damage multiplier for element matching.
     * Returns 1.25 if match, 1.0 otherwise.
     */
    public function getElementMatchDamageMultiplier(Slot $slot, PlayerItem $materia): float
    {
        return $this->isElementMatch($slot, $materia) ? 1.0 + self::ELEMENT_MATCH_DAMAGE_BONUS : 1.0;
    }

    /**
     * Get the XP multiplier for element matching.
     * Returns 1.25 if match, 1.0 otherwise.
     */
    public function getElementMatchXpMultiplier(Slot $slot, PlayerItem $materia): float
    {
        return $this->isElementMatch($slot, $materia) ? 1.0 + self::ELEMENT_MATCH_XP_BONUS : 1.0;
    }
}
