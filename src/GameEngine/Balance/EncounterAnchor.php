<?php

namespace App\GameEngine\Balance;

use App\Enum\MonsterRank;
use App\GameEngine\Bestiary\MonsterStatTemplate;

/**
 * L'ancre d'echelle : ce qu'un geste doit valoir, et combien de tours ca dure (ARC-05a).
 *
 * GAME_ARCHETYPES § 6.4 enonce le probleme sans detour : *les gestes valent 1 a
 * 12 points, les monstres ont 11 a 3 200 PV*. Des pourcentages poses sur des
 * nombres qui n'ont aucun rapport entre eux ne veulent rien dire — c'est la
 * raison pour laquelle un levier a +9 % ne retire pas un seul tour de combat.
 *
 * **On fixe la duree, et on en derive les valeurs — jamais l'inverse.** C'est
 * la seule facon d'obtenir une echelle qui tienne : partir des nombres et
 * esperer une duree, c'est ce qui a produit l'ecart actuel.
 *
 * Cette classe ne change **aucune valeur de jeu**. Elle dit ce que la regle
 * exige et permet de **mesurer l'ecart** ; deplacer les valeurs est le travail
 * d'ARC-05b, et le simulateur complet celui d'ARC-17. GAME_ARCHETYPES § 0.2
 * previent qu'aucun nombre du canon n'est definitif : ce qui est fige ici, ce
 * sont les **regles** — 25 % par geste, les bandes de duree —, pas les cibles
 * qu'elles produisent, qui se recalculent depuis le gabarit du bestiaire.
 */
final class EncounterAnchor
{
    /**
     * La regle du canon : *un geste de palier n retire ~25 % des PV d'un
     * adversaire commun de palier n*.
     */
    public const SHARE_OF_COMMON_LIFE = 0.25;

    /**
     * Les bandes de duree visees, en tours, par rang d'adversaire (§ 6.4).
     *
     * Elles ne sont pas des moyennes a atteindre mais des **fourchettes** : un
     * commun qui dure 2 tours ne se joue pas, un commun qui dure 8 tours n'est
     * plus un commun. Le rang dit le format de la rencontre, pas sa difficulte
     * seule.
     *
     * @var array<string, array{0: int, 1: int}>
     */
    public const TURN_BANDS = [
        'common' => [3, 5],
        'elite' => [6, 10],
        'boss' => [12, 20],
    ];

    /**
     * Ce qu'un geste de ce palier doit retirer a un commun de son palier.
     *
     * La cible se **calcule** depuis le gabarit du bestiaire (BES-02) plutot
     * que de vivre dans une table : recalibrer les PV d'un monstre deplace
     * automatiquement ce qu'un geste doit valoir, et les deux ne peuvent pas
     * diverger en silence.
     */
    public static function targetDamageFor(int $tier): int
    {
        $life = MonsterStatTemplate::lifeFor($tier, MonsterRank::Common);

        return (int) round($life * self::SHARE_OF_COMMON_LIFE);
    }

    /**
     * Combien de tours il faut a ce geste pour venir a bout de cette case.
     *
     * Un entier, arrondi vers le haut : le dernier coup compte, meme partiel.
     * Un geste sans degat rend `null` — il ne conclut pas une rencontre, et lui
     * chercher une duree n'aurait pas de sens.
     */
    public static function turnsToClear(int $damagePerTurn, int $tier, MonsterRank $rank): ?int
    {
        if ($damagePerTurn <= 0) {
            return null;
        }

        return (int) ceil(MonsterStatTemplate::lifeFor($tier, $rank) / $damagePerTurn);
    }

    /**
     * Cette duree tient-elle dans la bande de son rang ?
     */
    public static function isWithinBand(int $turns, MonsterRank $rank): bool
    {
        [$min, $max] = self::TURN_BANDS[$rank->value];

        return $turns >= $min && $turns <= $max;
    }

    /**
     * Le facteur qui separe ce qu'un geste vaut de ce qu'il devrait valoir.
     *
     * C'est le chiffre qu'ARC-05b aura a ramener vers 1. Le rendre lisible
     * maintenant est tout l'objet de ce jalon : *on ne recalibre pas ce qu'on
     * ne mesure pas*.
     */
    public static function shortfallFor(int $damage, int $tier): float
    {
        if ($damage <= 0) {
            return \INF;
        }

        return self::targetDamageFor($tier) / $damage;
    }
}
