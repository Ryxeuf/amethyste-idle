<?php

namespace App\GameEngine\Fight;

use App\GameEngine\Balance\DepositValue;

/**
 * Ce qu'une riposte rend, et quand elle ne rend rien (ARC-18a).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 4 : *etre frappe est une action*. C'est la
 * moins chere des huit formes — un point d'accroche au moment d'encaisser — et
 * elle repare un defaut mesure : **le tank ne tue pas**. Le Mur met quatorze
 * tours la ou l'archer en met six (§ 9 sexies), et la riposte lui donne des
 * degats **sans lui donner de la vitesse** : son cout structurel reste entier.
 *
 * ## Le garde-fou, et pourquoi il est le cœur de la forme
 *
 * ***Une riposte ne se declenche jamais sur des degats evites.*** Le canon le
 * pose comme une condition d'admission, pas comme un reglage : si l'esquive ou
 * l'absorption declenchaient la riposte, **l'encaisse optimale consisterait a se
 * faire toucher expres** — et un archetype dont le jeu optimal est de baisser sa
 * garde n'est pas un archetype d'encaisse.
 *
 * La lecture qui le garantit est arithmetique plutot que declarative : on ne
 * demande pas *« la cible a-t-elle esquive ? »* mais *« combien de points de vie
 * ont reellement ete retires ? »*. Un coup esquive, entierement absorbe par un
 * bouclier, ou ramene a zero par la garde retire zero — et zero ne riposte pas.
 * *Poser la question sur le resultat plutot que sur la cause ferme d'un coup
 * tous les chemins d'evitement, y compris ceux qui n'existent pas encore.*
 *
 * ## Ce qu'elle rend, et pourquoi ce n'est pas un pourcentage
 *
 * La riposte est un **depot offensif**, et la correction 21 du § 13.3 lui
 * applique sa borne : *un depot offensif ne depasse jamais un tour d'attaque par
 * tour investi*. On rend donc une **valeur fixe par coup encaisse**, portee par
 * l'application du statut (`FightStatusEffect::valuePerTurn`, le champ
 * qu'ARC-11b-a a pose pour exactement cette raison), et non une part des degats
 * recus.
 *
 * **Une part des degats recus serait le mauvais choix**, et c'est mesurable :
 * elle grandirait avec le palier de l'adversaire, donc la riposte vaudrait le
 * plus contre les monstres qui frappent le plus fort — c'est-a-dire qu'elle
 * recompenserait d'etre en danger, ce que le garde-fou ci-dessus interdit deja
 * dans l'autre sens.
 *
 * ## Ce que cette classe ne fait pas
 *
 * Elle ne pose aucun statut et n'ecrit aucune valeur de jeu : **aucun geste
 * livre ne porte la forme `riposte`**. La loi est posee avant qu'il y ait
 * quelque chose a relire, comme `ElementalMark` (ARC-13a) et `DepositLaw`
 * (ARC-11b-a) avant elle. Les arbres d'encaisse la porteront avec ARC-08.
 */
final class RiposteLaw
{
    /**
     * Une riposte se declenche-t-elle sur ce coup ?
     *
     * @param int $lifeActuallyLost les points de vie **reellement** retires,
     *                              apres esquive, garde et bouclier
     */
    public static function triggersOn(int $lifeActuallyLost): bool
    {
        return $lifeActuallyLost > 0;
    }

    /**
     * Ce que la riposte rend a l'attaquant.
     *
     * Rend `0` quand le coup n'a rien retire — le garde-fou du § 13.1 — et
     * quand l'application ne porte aucune valeur, ce qui se lit « ce statut
     * n'est pas une riposte ».
     */
    public static function returnedDamage(int $lifeActuallyLost, ?int $valuePerTurn): int
    {
        if (!self::triggersOn($lifeActuallyLost)) {
            return 0;
        }

        return max(0, $valuePerTurn ?? 0);
    }

    /**
     * La valeur totale qu'une riposte a le droit de rendre sur sa duree.
     *
     * La borne des depots offensifs (§ 13.3, correction 21), lue **une fois**
     * ici plutot que recopiee dans chaque geste : *un depot offensif ne depasse
     * jamais un tour d'attaque par tour investi*. Un depot **defensif** peut
     * valoir davantage, parce que la barre de vie de sa cible l'ecrete toute
     * seule ; un depot de degats n'est borne par rien et deviendrait dominant.
     */
    public static function isWithinOffensiveDepositBound(int $valuePerTurn, int $turns, int $oneTurnOfAttack): bool
    {
        return DepositValue::isWithinOffensiveBound($valuePerTurn * max(0, $turns), $oneTurnOfAttack);
    }
}
