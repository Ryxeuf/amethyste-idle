<?php

namespace App\GameEngine\Player;

use App\Entity\App\Player;
use App\Enum\CombatLever;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Reputation\PatronageBonusResolver;

/**
 * PV max, ATK, DEF « effectifs » = base joueur + bonus (sets, compétences, synergies, enchantements).
 */
class PlayerEffectiveStatsCalculator
{
    public function __construct(
        private readonly CombatSkillResolver $combatSkillResolver,
        private readonly EnchantmentManager $enchantmentManager,
        private readonly PatronageBonusResolver $patronageBonusResolver,
        private readonly CombatLeverScale $leverScale,
    ) {
    }

    /**
     * Les points de vie que le patronage ajoute (FAC-01).
     *
     * Le pourcentage porte sur la **base du personnage** et non sur le total :
     * l'appliquer au total le ferait dependre des enchantements du moment, et
     * un joueur verrait sa vie maximum bouger en changeant d'anneau sans que la
     * faction y soit pour rien.
     *
     * `life` est la seule statistique que le patronage traite ici : les quatre
     * autres qualifient un geste, et s'appliquent la ou le geste a lieu
     * (`CombatSkillResolver::getCombatBonuses`).
     */
    private function patronMaxLife(Player $player): int
    {
        $percent = $this->patronageBonusResolver->maxLifePercent($player);

        return $percent > 0 ? (int) round($player->getMaxLife() * $percent / 100) : 0;
    }

    /**
     * Les PV que le levier `life` ajoute (ARC-03b).
     *
     * Comme le patronage, le pourcentage porte sur la **base du personnage** et
     * non sur le total : l'appliquer au total le ferait dependre des
     * enchantements du moment. Et comme le patronage, il est lu **sans portee** —
     * `life` est l'un des deux leviers que le canon place hors de la double
     * borne (§ 4.2), parce que les points de vie ne sont pas un geste : les
     * borner ferait varier la barre de vie d'un tour a l'autre selon le sort
     * choisi.
     */
    private function leverMaxLife(Player $player): int
    {
        $levers = $this->combatSkillResolver->getLeverEffects($player);
        if ($levers->isEmpty()) {
            return 0;
        }

        return (int) round($player->getMaxLife() * ($levers->multiplierFor(CombatLever::Life, $this->leverScale) - 1.0));
    }

    public function getEffectiveMaxLife(Player $player): int
    {
        $combat = $this->combatSkillResolver->getCombatBonuses($player);
        $enchant = $this->enchantmentManager->getEnchantmentBonuses($player);
        $enchantMaxLife = (int) ($enchant['max_life'] ?? 0);

        return max(1, $player->getMaxLife() + $combat['life'] + $enchantMaxLife + $this->patronMaxLife($player) + $this->leverMaxLife($player));
    }

    /**
     * Bonus PV max hors base persistante (affichage type FF7 : base + bonus).
     */
    public function getMaxLifeBonusTotal(Player $player): int
    {
        $combat = $this->combatSkillResolver->getCombatBonuses($player);
        $enchant = $this->enchantmentManager->getEnchantmentBonuses($player);

        return $combat['life'] + (int) ($enchant['max_life'] ?? 0) + $this->patronMaxLife($player) + $this->leverMaxLife($player);
    }

    /**
     * @param int $protectionFromGearAndSets Somme DEF pièces + bonus sets
     *
     * @return array{
     *   life: int,
     *   maxLife: int,
     *   maxLifeBase: int,
     *   maxLifeBonus: int,
     *   hit: int,
     *   hitBase: int,
     *   hitBonus: int,
     *   energy: int,
     *   maxEnergy: int,
     *   speed: int,
     *   protection: int
     * }
     */
    public function getInventorySheetStats(Player $player, int $protectionFromGearAndSets): array
    {
        $combat = $this->combatSkillResolver->getCombatBonuses($player);
        $enchant = $this->enchantmentManager->getEnchantmentBonuses($player);
        $enchantMaxLife = (int) ($enchant['max_life'] ?? 0);
        $enchantDefense = (int) ($enchant['defense'] ?? 0);
        $enchantHit = (int) ($enchant['hit'] ?? 0);

        $maxLifeBase = $player->getMaxLife();
        $maxLifeBonus = $combat['life'] + $enchantMaxLife + $this->patronMaxLife($player) + $this->leverMaxLife($player);
        $maxLife = max(1, $maxLifeBase + $maxLifeBonus);

        $life = min($player->getLife(), $maxLife);

        $hitBase = $player->getHit();
        $hitBonus = $combat['hit'] + $enchantHit;

        return [
            'life' => $life,
            'maxLife' => $maxLife,
            'maxLifeBase' => $maxLifeBase,
            'maxLifeBonus' => $maxLifeBonus,
            'hit' => $hitBase + $hitBonus,
            'hitBase' => $hitBase,
            'hitBonus' => $hitBonus,
            'energy' => $player->getEnergy(),
            'maxEnergy' => $player->getMaxEnergy(),
            'speed' => $player->getSpeed(),
            'protection' => $protectionFromGearAndSets + $enchantDefense,
        ];
    }
}
