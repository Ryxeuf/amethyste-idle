<?php

namespace App\GameEngine\Settlement;

use App\Enum\SettlementIndex;

/**
 * Ce qu'une action depose dans un foyer (FOY-01, table de BALANCE § 23.1).
 *
 * `$index` a `null` signifie **reparti** : la traversee d'une zone n'est ni du
 * negoce ni de la guerre, elle nourrit les quatre indices a parts egales. C'est
 * ce qui fait vivre une zone de transit sans qu'on y farme, et c'est aussi ce
 * qui l'empeche d'y gagner une identite : passer n'a jamais fait une ville.
 *
 * `$grains` est un **flottant** parce que la traversee vaut 0,2. Un depot
 * fractionnaire arrondi a chaque evenement vaudrait zero et la ligne du tableau
 * serait morte sans que rien ne le dise ; c'est au consommateur (FOY-02)
 * d'accumuler le reste, comme `GatherService::regenerate()` reporte le sien.
 *
 * `$capped` a `false` sort l'action du plafond journalier (RET-02b). Reserve aux
 * depots **structurellement ingrindables** — une livraison hebdomadaire, une par
 * semaine et sans reroll. Le plafond existe pour que le grind ne batte pas la
 * regularite ; l'appliquer a un rendez-vous unique reviendrait a manger en
 * silence celui d'un joueur qui a beaucoup joue le meme jour, ce qui punit
 * exactement le comportement qu'on cherche a obtenir.
 */
final readonly class SedimentRule
{
    public function __construct(
        public string $action,
        public ?SettlementIndex $index,
        public float $grains,
        public bool $capped = true,
    ) {
    }

    public function isSpread(): bool
    {
        return $this->index === null;
    }
}
