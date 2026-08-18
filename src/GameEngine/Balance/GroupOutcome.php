<?php

namespace App\GameEngine\Balance;

use App\Enum\MonsterRank;

/**
 * Ce qu'une rencontre de groupe a coute a une composition (ARC-17c-d).
 *
 * GAME_ARCHETYPES § 7 bis pose la regle que ce releve verifie : ***aucun role
 * n'est necessaire***. Un groupe sans tank et sans soigneur doit venir a bout
 * d'une elite de son palier — sinon la composition cesse d'etre un gout pour
 * devenir un peage, et le jeu redevient celui qu'on a refuse.
 *
 * La colonne `membersDown` est celle qui distingue une victoire d'une victoire :
 * quatre debout et un seul debout ne racontent pas la meme rencontre, et le seuil
 * ne se lit pas sans elle.
 */
final readonly class GroupOutcome
{
    public function __construct(
        public string $compositionLabel,
        public int $tier,
        public MonsterRank $rank,
        public int $turns,
        public bool $victory,
        public bool $resolved,
        public int $membersDown,
        public int $memberCount,
        public int $encounterHpRemaining,
        public int $encounterHpMax,
    ) {
    }

    /**
     * La part de la barre de la rencontre que le groupe a entamee.
     *
     * Sur une defaite, c'est le seul chiffre qui dit *de combien* le groupe a
     * manque : « perdu » se lit pareil a 5 % et a 95 %.
     */
    public function encounterShareCleared(): float
    {
        if ($this->encounterHpMax <= 0) {
            return 0.0;
        }

        return ($this->encounterHpMax - max(0, $this->encounterHpRemaining)) * 100.0 / $this->encounterHpMax;
    }

    public function membersStanding(): int
    {
        return max(0, $this->memberCount - $this->membersDown);
    }
}
