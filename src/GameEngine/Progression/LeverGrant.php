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
    ) {
    }

    public function isConditional(): bool
    {
        return $this->condition !== null;
    }

    /**
     * @return array{lever: string, points: int, condition?: string}
     */
    public function toArray(): array
    {
        $data = ['lever' => $this->lever->value, 'points' => $this->budgetPoints];
        if ($this->condition !== null) {
            $data['condition'] = $this->condition;
        }

        return $data;
    }
}
