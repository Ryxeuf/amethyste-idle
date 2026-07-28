<?php

namespace App\Enum;

/**
 * Ce qu'on demande en echange d'une commission livree (RET-02b).
 *
 * Trois options **d'ampleur comparable**, et c'est une regle de conception, pas
 * un reglage : des recompenses inegales transformeraient le choix en calcul, et
 * le rendez-vous hebdomadaire en optimisation. On veut que le joueur choisisse
 * ce dont il a besoin *cette semaine-la*, pas ce qui rapporte le plus.
 *
 * La troisieme est la seule qui compte vraiment pour le pilier territorial :
 * **le Tribut** rend au foyer ce que le joueur aurait pris. C'est ce qui donne
 * au solo une facon de peser sur le chantier collectif sans guilde — la raison
 * d'etre de la commission (GAME_PROGRESSION § 3).
 */
enum WeeklyCommissionReward: string
{
    /** La bourse — des gils, tout de suite. */
    case Purse = 'purse';

    /** Le renfort — de l'energie d'action rendue, donc du temps de jeu. */
    case Vigour = 'vigour';

    /** Le tribut — le joueur renonce a sa part, le foyer recoit davantage. */
    case Tribute = 'tribute';

    /**
     * Toutes les options, dans l'ordre d'affichage.
     *
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [self::Purse, self::Vigour, self::Tribute];
    }
}
