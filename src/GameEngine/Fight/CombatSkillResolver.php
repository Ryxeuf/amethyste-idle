<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use Doctrine\ORM\EntityManagerInterface;

class CombatSkillResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Get all skills that grant combat abilities for a player.
     * Looks for skills whose `actions` JSON contains a "combat" key.
     *
     * @return array<array{skill: Skill, combat: array}>
     */
    public function getAvailableSkillsForCombat(Player $player): array
    {
        $combatSkills = [];

        foreach ($player->getSkills() as $skill) {
            $actions = $skill->getActions();
            if ($actions === null || !isset($actions['combat'])) {
                continue;
            }

            $combatData = $actions['combat'];
            $combatSkills[] = [
                'skill' => $skill,
                'combat' => $combatData,
            ];
        }

        return $combatSkills;
    }

    /**
     * Get the spells unlocked by a player's combat skills.
     *
     * @return Spell[]
     */
    public function getUnlockedSpells(Player $player): array
    {
        $combatSkills = $this->getAvailableSkillsForCombat($player);
        $spellSlugs = [];

        foreach ($combatSkills as $entry) {
            $combat = $entry['combat'];
            if (isset($combat['spell_slug'])) {
                $spellSlugs[] = $combat['spell_slug'];
            }
            if (isset($combat['spells']) && is_array($combat['spells'])) {
                foreach ($combat['spells'] as $spellSlug) {
                    $spellSlugs[] = $spellSlug;
                }
            }
        }

        if (empty($spellSlugs)) {
            return [];
        }

        $spellSlugs = array_unique($spellSlugs);

        return $this->entityManager->getRepository(Spell::class)->findBy([
            'slug' => $spellSlugs,
        ]);
    }

    /**
     * Check if a player has enough energy to cast a given spell.
     */
    public function hasEnoughEnergy(Player $player, Spell $spell): bool
    {
        return $player->getEnergy() >= $spell->getEnergyCost();
    }

    /**
     * Consume energy from a player when casting a spell.
     * Returns false if the player does not have enough energy.
     */
    public function consumeEnergy(Player $player, Spell $spell): bool
    {
        if (!$this->hasEnoughEnergy($player, $spell)) {
            return false;
        }

        $player->setEnergy($player->getEnergy() - $spell->getEnergyCost());

        return true;
    }

    /**
     * Get combat stat bonuses from all unlocked combat skills.
     *
     * @return array{damage: int, heal: int, hit: int, critical: int, life: int}
     */
    public function getCombatBonuses(Player $player): array
    {
        $bonuses = [
            'damage' => 0,
            'heal' => 0,
            'hit' => 0,
            'critical' => 0,
            'life' => 0,
        ];

        foreach ($player->getSkills() as $skill) {
            $bonuses['damage'] += $skill->getDamage();
            $bonuses['heal'] += $skill->getHeal();
            $bonuses['hit'] += $skill->getHit();
            $bonuses['critical'] += $skill->getCritical();
            $bonuses['life'] += $skill->getLife();
        }

        return $bonuses;
    }

    /**
     * Get the spell slugs unlocked by player's materia skills.
     * Scans skills with actions['materia']['unlock'] pattern.
     *
     * @return string[] Spell slugs the player has unlocked via skills
     */
    public function getUnlockedMateriaSpellSlugs(Player $player): array
    {
        $slugs = [];

        foreach ($player->getSkills() as $skill) {
            $actions = $skill->getActions();
            if ($actions === null || !isset($actions['materia']['unlock'])) {
                continue;
            }

            $slugs[] = (string) $actions['materia']['unlock'];
        }

        return array_unique($slugs);
    }

    /**
     * Check if a player has unlocked a specific materia spell via skills.
     */
    public function hasUnlockedMateriaSpell(Player $player, string $spellSlug): bool
    {
        return in_array($spellSlug, $this->getUnlockedMateriaSpellSlugs($player), true);
    }

    /**
     * Check if a player has a specific combat skill unlocked.
     */
    public function hasSkillWithSpell(Player $player, string $spellSlug): bool
    {
        $combatSkills = $this->getAvailableSkillsForCombat($player);

        foreach ($combatSkills as $entry) {
            $combat = $entry['combat'];
            if (isset($combat['spell_slug']) && $combat['spell_slug'] === $spellSlug) {
                return true;
            }
            if (isset($combat['spells']) && in_array($spellSlug, $combat['spells'], true)) {
                return true;
            }
        }

        return false;
    }
}
