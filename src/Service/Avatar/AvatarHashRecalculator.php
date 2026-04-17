<?php

declare(strict_types=1);

namespace App\Service\Avatar;

use App\Entity\App\Player;
use Doctrine\ORM\EntityManagerInterface;

final class AvatarHashRecalculator
{
    public function __construct(
        private readonly PlayerAvatarPayloadBuilder $payloadBuilder,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Recompute the avatar hash for the given player.
     *
     * Called after equipment changes to keep `avatarHash` aligned with the
     * visible gear layers. Touches `avatarUpdatedAt` only when the hash
     * actually changes, so Mercure subscribers can detect real updates.
     *
     * @return bool true when the hash (and therefore the avatar) changed
     */
    public function recalculate(Player $player): bool
    {
        if (!$player->hasAvatar()) {
            return false;
        }

        $payload = $this->payloadBuilder->build($player);
        if ($payload === null) {
            return false;
        }

        $newHash = $payload['avatarHash'];
        if ($player->getAvatarHash() === $newHash) {
            return false;
        }

        $player->setAvatarHash($newHash);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return true;
    }
}
