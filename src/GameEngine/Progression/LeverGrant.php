<?php

namespace App\GameEngine\Progression;

use App\Enum\CombatLever;

/**
 * Ce qu'un nœud passif accorde : un levier, un prix, parfois une condition.
 *
 * GAME_ARCHETYPES § 4.3 : *« un nœud passif s'ecrit donc `(levier, points de
 * budget, condition ?)` »*. Les trois champs sont la forme complete d'un passif
 * de combat — c'est ce qui remplace les cinq entiers plats de `Skill`.
 *
 * La **condition** est portee ici des ARC-03 pour que le stockage n'ait pas a
 * changer deux fois ; ce qu'elle vaut (les familles de conditions et leurs
 * multiplicateurs x1,4 / x2,0) est ARC-12. Une condition est donc, a ce stade,
 * une chaine que rien n'interprete encore — et un nœud sans condition est un
 * passif toujours vrai, ce que le canon appelle *un total, pas un build*.
 */
final readonly class LeverGrant
{
    public function __construct(
        public CombatLever $lever,
        public int $budgetPoints,
        public ?string $condition = null,
        /**
         * Le pacte de ce nœud (ARC-15) : ce qu'il rend en devenant plus faible.
         *
         * `null` sur la quasi-totalite des nœuds — un arbre en porte **un au
         * plus**, et au palier 3 seulement. C'est une signature, pas un outil.
         */
        public ?PactGrant $pact = null,
    ) {
    }

    public function isConditional(): bool
    {
        return $this->condition !== null;
    }

    public function isPact(): bool
    {
        return $this->pact !== null;
    }

    /**
     * Ce que ce nœud coute **reellement** au budget de son arbre.
     *
     * Le pacte contourne le budget de l'arbre, jamais le plafond d'un levier :
     * un nœud a 19 pb dont 10 sont rendus par un malus pese 9 — la valeur d'un
     * nœud de palier 3 ordinaire. *Le pacte ne change pas ce qu'un arbre pese,
     * il change sa forme.*
     */
    public function netBudgetPoints(): int
    {
        return $this->budgetPoints - ($this->pact !== null ? $this->pact->budgetPoints : 0);
    }

    /**
     * @return array{lever: string, points: int, condition?: string, pact?: array{lever: string, points: int}}
     */
    public function toArray(): array
    {
        $data = ['lever' => $this->lever->value, 'points' => $this->budgetPoints];
        if ($this->condition !== null) {
            $data['condition'] = $this->condition;
        }
        if ($this->pact !== null) {
            $data['pact'] = $this->pact->toArray();
        }

        return $data;
    }
}
