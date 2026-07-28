<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

/**
 * L'assiduite de la semaine, telle qu'on la montre (RET-04).
 *
 * **Une restitution, pas un compteur culpabilisant.** L'ecran dit ce qui a ete
 * fait et ce qui reste a portee ; il ne dit jamais ce qui a ete manque, ne
 * compare pas a la semaine derniere et n'annonce aucune echeance. Un joueur qui
 * revient apres deux semaines d'absence doit lire exactement la meme chose
 * qu'un joueur qui n'est jamais parti.
 *
 * C'est pour cela que ce DTO ne porte **ni** historique, **ni** compte a
 * rebours : ce qu'il ne contient pas est aussi voulu que ce qu'il contient.
 */
final readonly class HubAttendance
{
    public function __construct(
        public int $activeDays,
        public int $nextTierDays = 0,
        public int $nextTierGils = 0,
        public int $nextTierEnergy = 0,
    ) {
    }

    /**
     * Reste-t-il un palier a portee cette semaine ?
     */
    public function hasNextTier(): bool
    {
        return $this->nextTierDays > 0;
    }

    /**
     * Jours restants avant le prochain palier.
     */
    public function daysToNextTier(): int
    {
        return max(0, $this->nextTierDays - $this->activeDays);
    }
}
