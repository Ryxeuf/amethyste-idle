<?php

namespace App\GameEngine\Settlement;

/**
 * Ce que l'ecran de zone a le droit de dire d'un filon pali (FOY-12).
 *
 * Le cout est **toujours** annonce, meme a un joueur sans guilde : restaurer
 * est un acte de gouvernement, et le prix d'un acte de gouvernement se lit
 * avant qu'on ait le pouvoir de le poser. `blockedReason` dit pourquoi le
 * bouton manque, plutot que de laisser un blanc.
 */
readonly class VeinRestorationOffer
{
    public function __construct(
        public string $veinSlug,
        public int $cost,
        public int $palenessPercent,
        public ?\DateTimeImmutable $activeUntil = null,
        public ?string $activeGuildName = null,
        public ?string $blockedReason = null,
    ) {
    }

    public function isUnderway(): bool
    {
        return null !== $this->activeUntil;
    }

    public function canOpen(): bool
    {
        return !$this->isUnderway() && null === $this->blockedReason;
    }
}
