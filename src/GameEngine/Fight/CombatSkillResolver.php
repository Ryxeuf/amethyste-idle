<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\Enum\CombatRegister;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\SkillLeverReader;
use App\GameEngine\Progression\SynergyCalculator;
use App\GameEngine\Reputation\PatronageBonusResolver;

class CombatSkillResolver
{
    public function __construct(
        private readonly BuildDomainResolver $buildDomainResolver,
        private readonly SynergyCalculator $synergyCalculator,
        private readonly EquipmentSetResolver $equipmentSetResolver,
        private readonly PatronageBonusResolver $patronageBonusResolver,
        private readonly SkillLeverReader $leverReader,
        private readonly CombatLeverScale $leverScale,
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
     * **La double borne des passifs (DOM-01).** Avec une portee, seuls les
     * passifs dont un domaine occupe la case `element x registre` de l'action
     * s'appliquent : le « critique +1 % » du pyromancien ne sert que les sorts
     * de feu, jamais le corps a corps ni un sort d'eau (GAME_DOMAINS § 2).
     * Avant ce jalon, une vie de progression au berserker faisait des degats a
     * un sort d'eau — et sur une action donnee, tous les arbres s'exprimaient a
     * la fois, ce qui vidait la notion meme de build.
     *
     * **Sans portee, rien n'est borne**, et c'est voulu : la fiche d'inventaire
     * affiche un total, pas une action. La borne s'applique la ou un geste a
     * lieu.
     *
     * **`life` echappe toujours a la borne.** Les points de vie maximum ne sont
     * pas un geste : les faire dependre du sort qu'on lance ferait varier la
     * barre de vie d'un tour a l'autre. Les quatre autres statistiques
     * qualifient une action et se bornent avec elle.
     *
     * **La seconde borne, materielle (DOM-02).** Un domaine ne s'exprime que si
     * le build en porte une source : une materia de son element pour une ecole
     * de sort, une arme de son registre pour une ecole d'arme. Les deux bornes
     * sont cumulatives et ne disent pas la meme chose — la premiere dit « ce
     * geste-ci n'est pas le tien », la seconde « tu n'as rien sur toi qui le
     * permette ». Sans elle, un joueur qui monte les trente-six arbres les
     * exprimerait tous des qu'une action tombe dans la bonne case.
     *
     * @return array{damage: int, heal: int, hit: int, critical: int, life: int}
     */
    public function getCombatBonuses(Player $player, ?CombatScope $scope = null): array
    {
        $bonuses = [
            'damage' => 0,
            'heal' => 0,
            'hit' => 0,
            'critical' => 0,
            'life' => 0,
        ];

        foreach ($player->getSkills() as $skill) {
            // La vie precede la borne : elle n'est pas une action.
            $bonuses['life'] += $skill->getLife();

            if (!$this->skillAppliesTo($player, $skill, $scope)) {
                continue;
            }

            $bonuses['damage'] += $skill->getDamage();
            $bonuses['heal'] += $skill->getHeal();
            $bonuses['hit'] += $skill->getHit();
            $bonuses['critical'] += $skill->getCritical();
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

        // FAC-01 : les couleurs qu'on porte, et elles seules. Le patronage
        // amplifie le total en dernier — il qualifie l'ensemble du geste, pas
        // une source en particulier, et l'appliquer avant les sets ferait
        // dependre son effet de l'ordre des additions.
        return $this->patronageBonusResolver->amplify($player, $bonuses);
    }

    /**
     * Les leviers du personnage sur cette action, en points de budget (ARC-03b).
     *
     * Meme loi de bornage que `getCombatBonuses()` — un passif ne sert pas tous
     * les gestes a la fois (DOM-01) —, avec la meme exception : les leviers que
     * le canon declare **hors double borne** (`life` et `recovery`, § 4.2)
     * precedent la borne, parce qu'ils ne qualifient pas une action. La liste
     * n'est pas ecrite ici : elle se lit sur le levier lui-meme, ce qui evite
     * une seconde source de verite.
     *
     * On rend des **points de budget**, pas des effets : la conversion est le
     * travail du convertisseur unique, et la faire ici la dupliquerait. C'est
     * aussi ce qui permet de sommer — deux nœuds qui achetent `power` cumulent
     * leurs points, exactement comme un arbre les depense.
     *
     * @return array<string, int>
     */
    public function getCombatLevers(Player $player, ?CombatScope $scope = null): array
    {
        $totals = [];

        foreach ($player->getSkills() as $skill) {
            $applies = $this->skillAppliesTo($player, $skill, $scope);

            foreach ($this->leverReader->grantsOf($skill) as $grant) {
                if (!$applies && $this->leverScale->isBounded($grant->lever)) {
                    continue;
                }

                $totals[$grant->lever->value] = ($totals[$grant->lever->value] ?? 0) + $grant->budgetPoints;
            }
        }

        return $totals;
    }

    /**
     * Les leviers deja convertis en effets, pour le registre de l'action.
     */
    public function getLeverEffects(Player $player, ?CombatScope $scope = null, ?CombatRegister $register = null): CombatLeverEffects
    {
        return CombatLeverEffects::of($this->getCombatLevers($player, $scope), $this->leverScale, $register);
    }

    /**
     * Le passif de cette competence s'exprime-t-il sur l'action en cours ?
     *
     * **La retro-compatibilite passe par un « global » explicite.** Une
     * competence sans domaine de combat — un nœud de recolte, d'artisanat, ou
     * un nœud sans domaine du tout — n'a pas de case `element x registre` a
     * comparer. La borner reviendrait a supprimer son passif partout ; la
     * declarer globale la laisse se comporter comme avant DOM-01. C'est la
     * clause qui permet de typer les domaines sans relire les 524 nœuds.
     *
     * **Les deux bornes sont cumulatives (DOM-02).** Le domaine doit convenir a
     * l'action *et* etre porte par le build. Elles ne disent pas la meme chose :
     * la premiere refuse un geste qui n'est pas celui de l'arbre, la seconde
     * refuse un arbre que rien sur le personnage n'exprime.
     */
    private function skillAppliesTo(Player $player, Skill $skill, ?CombatScope $scope): bool
    {
        if ($scope === null) {
            return true;
        }

        $bounded = false;
        foreach ($skill->getDomains() as $domain) {
            if (!$domain->isCombatDomain()) {
                continue;
            }

            $bounded = true;
            if ($scope->admits($domain) && $this->buildDomainResolver->isActive($player, $domain)) {
                return true;
            }
        }

        return !$bounded;
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
