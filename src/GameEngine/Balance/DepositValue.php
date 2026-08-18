<?php

namespace App\GameEngine\Balance;

/**
 * La borne de valeur d'un depot, offensif et defensif (ARC-18a).
 *
 * GAME_ARCHETYPES § 13.3, correction 21 — et c'est une regle generale que le
 * canon a tiree du familier, mais qui vaut pour **toutes** les formes qui
 * deposent :
 *
 * > ***Un depot offensif ne depasse jamais un tour d'attaque par tour investi ;
 * > un depot defensif peut valoir davantage, parce que la barre de vie de sa
 * > cible l'ecrete toute seule.***
 *
 * L'asymetrie n'est pas une preference : elle est arithmetique. Un soin de
 * groupe peut valoir x8,8 le tour investi parce qu'il ne rend que ce qui a ete
 * perdu — une barre pleine absorbe le surplus. Un depot de degats, lui, n'est
 * borne par rien : chaque point rendu est un point retire, et plus la rencontre
 * dure plus il rapporte. C'est ce qui a fait mesurer **+87 %** a un invocateur
 * a quatre invocations avant la correction.
 *
 * La classe est posee ici, dans `Balance`, et non dans le moteur de combat :
 * c'est une regle d'equilibrage que plusieurs formes d'ARC-18 auront a lire,
 * et l'ecrire une seconde fois la ferait deriver de son original en silence.
 */
final class DepositValue
{
    /**
     * Ce qu'un depot **offensif** a le droit de rendre en tout.
     *
     * Un tour d'attaque par tour investi, et la riposte comme le familier n'en
     * investissent qu'un seul — celui ou on les pose.
     */
    public static function isWithinOffensiveBound(int $totalValue, int $oneTurnOfAttack): bool
    {
        return $totalValue <= max(0, $oneTurnOfAttack);
    }
}
