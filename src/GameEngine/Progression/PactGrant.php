<?php

namespace App\GameEngine\Progression;

use App\Enum\CombatLever;

/**
 * Le malus d'un pacte : ce qu'un nœud rend en devenant plus faible (ARC-15).
 *
 * GAME_ARCHETYPES § 6.5. **La seule mecanique du document qui rende un
 * personnage mesurablement plus faible quelque part** — sans elle, tous les
 * builds sont des additions, et « un archetype memorable est mauvais a quelque
 * chose, et il l'a choisi » reste une phrase.
 *
 * > **Un nœud peut prendre un malus. Sa valeur, au taux de change du levier,
 * > s'ajoute au budget du nœud.** Un arbre porte alors jusqu'a 60 pb de bonus
 * > et 10 pb de malus — la somme reste 50. *Le pacte ne change pas ce qu'un
 * > arbre pese, il change sa forme.*
 *
 * Deux crans seulement, et ce n'est pas un arrondi : le cran decide **quels
 * leviers peuvent l'accueillir**, puisque le nœud qui en resulte doit tenir
 * sous le plafond de son levier. Un pacte majeur consomme 19 des 20 points de
 * plafond, ce qui n'est possible que sur les quatre leviers plafonnes a 20.
 */
final readonly class PactGrant
{
    /** Le cran mineur : 5 pb rendus, pour un nœud a 14. */
    public const MINOR = 5;

    /** Le cran majeur : 10 pb rendus, pour un nœud a 19. */
    public const MAJOR = 10;

    /**
     * Les deux crans, et rien entre les deux.
     *
     * Une echelle continue rejouerait le defaut qu'ARC-06a a corrige sur les
     * couts : deux valeurs voisines ne diraient pas deux decisions, elles
     * diraient qu'on a dose a la main.
     *
     * @var list<int>
     */
    public const CRANS = [self::MINOR, self::MAJOR];

    public function __construct(
        /** Le levier sur lequel on s'affaiblit. */
        public CombatLever $lever,
        /** Ce que le malus rend au nœud, en points de budget. */
        public int $budgetPoints,
    ) {
    }

    public function isMajor(): bool
    {
        return $this->budgetPoints === self::MAJOR;
    }

    /**
     * @return array{lever: string, points: int}
     */
    public function toArray(): array
    {
        return ['lever' => $this->lever->value, 'points' => $this->budgetPoints];
    }
}
