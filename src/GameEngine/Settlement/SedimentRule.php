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
 */
final readonly class SedimentRule
{
    public function __construct(
        public string $action,
        public ?SettlementIndex $index,
        public float $grains,
    ) {
    }

    public function isSpread(): bool
    {
        return $this->index === null;
    }
}
