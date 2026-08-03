<?php

namespace App\GameEngine\Fight\Calculator;

use App\Entity\App\Mob;
use App\Entity\CharacterInterface;
use App\Entity\Game\Spell;
use App\Enum\CombatLever;
use App\GameEngine\Fight\CombatLeverEffects;
use App\GameEngine\Progression\CombatLeverScale;

class DamageCalculator
{
    /**
     * Calcule les degats de base d'un sort (avant modificateurs de combat).
     *
     * @param int $domainDamage bonus de degats du domaine
     */
    public function computeBaseDamage(Spell $spell, int $domainDamage, CharacterInterface $target, ?int $effectiveMaxLife = null): int
    {
        $spellDamage = $spell->getDamage();
        if ($spellDamage === null || $spellDamage === 0) {
            return 0;
        }

        $maxForPercent = $effectiveMaxLife ?? $target->getMaxLife();

        if ($spell->isPercent()) {
            return (int) round($maxForPercent * ($spellDamage / 100.0)) + $domainDamage;
        }

        return $spellDamage + $domainDamage;
    }

    /**
     * Calcule les soins de base d'un sort (avant modificateurs de combat).
     *
     * @param int $domainHeal bonus de soin du domaine
     */
    public function computeBaseHeal(Spell $spell, int $domainHeal, CharacterInterface $target, ?int $effectiveMaxLife = null): int
    {
        $spellHeal = $spell->getHeal();
        if ($spellHeal === null || $spellHeal === 0) {
            return 0;
        }

        $maxForPercent = $effectiveMaxLife ?? $target->getMaxLife();

        if ($spell->isPercent()) {
            return (int) round($maxForPercent * ($spellHeal / 100.0)) + $domainHeal;
        }

        return $spellHeal + $domainHeal;
    }

    /**
     * `power` — les degats du geste (ARC-03b).
     *
     * Multiplicatif sur la valeur de base, et c'est tout l'objet du jalon : un
     * `damage: +1` plat valait +50 % sur un geste a 2 degats et +8 % sur un
     * geste a 12. Le meme nœud vaut desormais la meme chose partout.
     */
    public function applyPower(int $damage, CombatLeverEffects $levers, CombatLeverScale $scale): int
    {
        if ($damage <= 0 || $levers->isEmpty()) {
            return $damage;
        }

        return max(0, (int) round($damage * $levers->multiplierFor(CombatLever::Power, $scale)));
    }

    /**
     * `mending` — le soin rendu (ARC-03b).
     */
    public function applyMending(int $heal, CombatLeverEffects $levers, CombatLeverScale $scale): int
    {
        if ($heal <= 0 || $levers->isEmpty()) {
            return $heal;
        }

        return max(0, (int) round($heal * $levers->multiplierFor(CombatLever::Mending, $scale)));
    }

    /**
     * `guard` — les degats subis, **apres** la resistance (ARC-03b).
     *
     * La place est la moitie de la decision : reduire avant la resistance
     * ferait dependre la garde de l'element de l'attaquant, alors qu'une armure
     * ne trie pas ce qu'elle encaisse. Les leviers lus ici sont ceux de la
     * **cible**, jamais ceux de l'attaquant.
     */
    public function applyGuard(int $damage, CombatLeverEffects $targetLevers, CombatLeverScale $scale): int
    {
        if ($damage <= 0 || $targetLevers->isEmpty()) {
            return $damage;
        }

        return max(0, (int) round($damage * $targetLevers->multiplierFor(CombatLever::Guard, $scale)));
    }

    /**
     * `dodge` — eviter entierement, **avant** tout calcul de degats (ARC-03b).
     *
     * Binaire et volatil la ou `guard` est continu et fiable : c'est ce qui les
     * rend tous deux necessaires, et ce qui distingue une armure de cuir d'une
     * armure de plaque autrement que par un chiffre (GAME_ARCHETYPES § 4).
     */
    public function isDodged(CombatLeverEffects $targetLevers, CombatLeverScale $scale): bool
    {
        if ($targetLevers->isEmpty()) {
            return false;
        }

        $chance = $targetLevers->pointsFor(CombatLever::Dodge, $scale);
        if ($chance <= 0.0) {
            return false;
        }

        try {
            return random_int(0, 9999) < (int) round($chance * 100);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Applique la resistance elementaire d'un mob sur les degats.
     *
     * `pierce` s'applique **ici**, et avant la resistance : il en ignore une
     * part, il ne multiplie pas ce qui en sort — sans quoi il ferait double
     * emploi avec `power` et deux leviers occuperaient la meme place.
     *
     * @return array{damage: int, resisted: bool, weak: bool}
     */
    public function applyElementalResistance(int $damage, Spell $spell, CharacterInterface $target, float $piercePoints = 0.0): array
    {
        $resisted = false;
        $weak = false;

        if ($damage > 0 && $target instanceof Mob) {
            $resistance = $target->getMonster()->getElementalResistance($spell->getElement()->value);
            // La penetration ne retourne jamais une resistance en faiblesse :
            // ignorer plus que ce qui existe, c'est ignorer tout.
            if ($piercePoints > 0.0 && $resistance > 0.0) {
                $resistance = max(0.0, $resistance - $piercePoints / 100.0);
            }
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
     * Applique le modificateur météo-élémentaire.
     */
    public function applyWeatherModifier(int $damage, float $modifier): int
    {
        if ($modifier === 1.0 || $damage <= 0) {
            return $damage;
        }

        return max(0, (int) round($damage * $modifier));
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
