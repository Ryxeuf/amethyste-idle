<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\Game\Spell;
use App\GameEngine\Progression\SynergyCalculator;

class CombatSkillResolver
{
    public function __construct(
        private readonly SynergyCalculator $synergyCalculator,
        private readonly EquipmentSetResolver $equipmentSetResolver,
    ) {
    }

    /*
     * Regle absolue #9 : une competence d'arbre n'accorde JAMAIS de sort actif.
     *
     * Deux methodes lisaient ici `actions['combat']['spell_slug']` et rendaient
     * les `Spell` correspondants — exactement le motif interdit, et celui que
     * l'audit du systeme de design a retrouve dans la maquette « Competences »
     * sous la forme d'un noeud d'arbre « Trait d'ombre · 25 PM ».
     *
     * Elles n'avaient aucun appelant : le chemin etait ouvert, jamais emprunte.
     * Il est ferme. Un sort actif s'obtient par une materia — competence
     * `actions.materia.unlock`, materia possedee, materia sertie — et par
     * l'attaque de base de l'arme, toujours gratuite.
     *
     * Les competences restent passives : bonus de stats (`getCombatBonuses`)
     * et deverrouillage de materia (`getUnlockedMateriaSpellSlugs`).
     */

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

        // Ajouter les bonus de synergies cross-domaine
        $synergyBonuses = $this->synergyCalculator->getSynergyBonuses($player);
        foreach ($synergyBonuses as $stat => $value) {
            $bonuses[$stat] += $value;
        }

        // Ajouter les bonus de sets d'équipement
        $setBonuses = $this->equipmentSetResolver->getSetBonuses($player);
        foreach (['damage', 'heal', 'hit', 'critical', 'life'] as $stat) {
            $bonuses[$stat] += $setBonuses[$stat];
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
}
