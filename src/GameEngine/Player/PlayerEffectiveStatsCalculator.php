<?php

namespace App\GameEngine\Player;

use App\Entity\App\Player;
use App\GameEngine\Enchantment\EnchantmentManager;
use App\GameEngine\Fight\CombatSkillResolver;

/**
 * PV max, ATK, DEF « effectifs » = base joueur + bonus (sets, compétences, synergies, enchantements).
 */
class PlayerEffectiveStatsCalculator
{
    public function __construct(
        private readonly CombatSkillResolver $combatSkillResolver,
        private readonly EnchantmentManager $enchantmentManager,
    ) {
    }

    public function getEffectiveMaxLife(Player $player): int
    {
        $combat = $this->combatSkillResolver->getCombatBonuses($player);
        $enchant = $this->enchantmentManager->getEnchantmentBonuses($player);
        $enchantMaxLife = (int) ($enchant['max_life'] ?? 0);

        return max(1, $player->getMaxLife() + $combat['life'] + $enchantMaxLife);
    }

    /**
     * Bonus PV max hors base persistante (affichage type FF7 : base + bonus).
     */
    public function getMaxLifeBonusTotal(Player $player): int
    {
        $combat = $this->combatSkillResolver->getCombatBonuses($player);
        $enchant = $this->enchantmentManager->getEnchantmentBonuses($player);

        return $combat['life'] + (int) ($enchant['max_life'] ?? 0);
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
        $maxLifeBonus = $combat['life'] + $enchantMaxLife;
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
