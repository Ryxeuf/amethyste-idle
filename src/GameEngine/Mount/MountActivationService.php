<?php

namespace App\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\Game\Mount;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Manages the active mount of a player (the one currently ridden).
 *
 * Invariants:
 * - A player can activate a mount only if they own it (PlayerMount exists).
 * - A player can activate at most one mount at a time. Activating a new
 *   mount automatically replaces the previous one.
 * - The activation persists on Player::activeMount.
 */
class MountActivationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerMountRepository $playerMountRepository,
    ) {
    }

    /**
     * @throws MountNotOwnedException if the player does not own the mount
     * @throws \DomainException       if the mount is disabled
     */
    public function mount(Player $player, Mount $mount, bool $flush = true): void
    {
        if (!$mount->isEnabled()) {
            throw new \DomainException(sprintf('La monture "%s" est desactivee.', $mount->getSlug()));
        }

        if (!$this->playerMountRepository->playerOwnsMount($player, $mount)) {
            throw new MountNotOwnedException($player, $mount);
        }

        $player->setActiveMount($mount);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Removes the active mount from the player. Idempotent: if the player
     * has no active mount, this is a no-op (still returns a flush if requested).
     */
    public function unmount(Player $player, bool $flush = true): void
    {
        if ($player->getActiveMount() === null) {
            return;
        }

        $player->setActiveMount(null);

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
