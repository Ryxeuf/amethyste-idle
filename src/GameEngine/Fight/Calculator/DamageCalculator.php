<?php

namespace App\GameEngine\Fight\Calculator;

use App\Entity\App\Mob;
use App\Entity\CharacterInterface;
use App\Entity\Game\Spell;

class DamageCalculator
{
    /**
     * Calcule les degats de base d'un sort (avant modificateurs de combat).
     *
     * @param int $domainDamage bonus de degats du domaine
     */
    public function computeBaseDamage(Spell $spell, int $domainDamage, CharacterInterface $target): int
    {
        $spellDamage = $spell->getDamage();
        if ($spellDamage === null || $spellDamage === 0) {
            return 0;
        }

        if ($spell->isPercent()) {
            return (int) round($target->getMaxLife() * ($spellDamage / 100.0)) + $domainDamage;
        }

        return $spellDamage + $domainDamage;
    }

    /**
     * Calcule les soins de base d'un sort (avant modificateurs de combat).
     *
     * @param int $domainHeal bonus de soin du domaine
     */
    public function computeBaseHeal(Spell $spell, int $domainHeal, CharacterInterface $target): int
    {
        $spellHeal = $spell->getHeal();
        if ($spellHeal === null || $spellHeal === 0) {
            return 0;
        }

        if ($spell->isPercent()) {
            return (int) round($target->getMaxLife() * ($spellHeal / 100.0)) + $domainHeal;
        }

        return $spellHeal + $domainHeal;
    }

    /**
     * Applique la resistance elementaire d'un mob sur les degats.
     *
     * @return array{damage: int, resisted: bool, weak: bool}
     */
    public function applyElementalResistance(int $damage, Spell $spell, CharacterInterface $target): array
    {
        $resisted = false;
        $weak = false;

        if ($damage > 0 && $target instanceof Mob) {
            $resistance = $target->getMonster()->getElementalResistance($spell->getElement());
            if ($resistance !== 0.0) {
                $damage = (int) round($damage * (1.0 - $resistance));
                $damage = max(0, $damage);
                $resisted = $resistance > 0;
                $weak = $resistance < 0;
            }
        }

        return ['damage' => $damage, 'resisted' => $resisted, 'weak' => $weak];
    }

    /**
     * Applique le multiplicateur berserk.
     */
    public function applyBerserkModifier(int $damage): int
    {
        return (int) round($damage * 1.5);
    }

    /**
     * Applique la reduction de degats par brulure.
     */
    public function applyBurnReduction(int $damage): int
    {
        return (int) round($damage * 0.75);
    }

    /**
     * Applique l'absorption du bouclier.
     *
     * @return array{damage: int, absorbed: int}
     */
    public function applyShieldAbsorption(int $damage, int $shieldAbsorb): array
    {
        if ($shieldAbsorb <= 0 || $damage <= 0) {
            return ['damage' => $damage, 'absorbed' => 0];
        }

        $absorbed = min($damage, $shieldAbsorb);

        return ['damage' => $damage - $absorbed, 'absorbed' => $absorbed];
    }
}
