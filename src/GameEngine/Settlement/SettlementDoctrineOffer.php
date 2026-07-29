<?php

namespace App\GameEngine\Settlement;

use App\Enum\SettlementDoctrine;

/**
 * Ce que l'ecran de zone dit des ateliers de doctrine (FOY-13).
 *
 * L'ecran montre **les deux** ateliers, toujours, meme quand un seul est
 * adoptable. L'axe Extraire / Preserver ne se comprend qu'en voyant ce qu'on
 * n'a pas choisi : n'afficher que l'option ouverte reviendrait a cacher la
 * moitie du choix.
 */
readonly class SettlementDoctrineOffer
{
    public function __construct(
        public SettlementDoctrine $doctrine,
        public bool $adopted,
        public int $cost,
        public ?string $blockedReason = null,
        public ?\DateTimeImmutable $lockedUntil = null,
    ) {
    }

    public function canAdopt(): bool
    {
        return !$this->adopted && null === $this->blockedReason;
    }
}
