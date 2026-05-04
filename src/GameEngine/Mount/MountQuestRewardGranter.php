<?php

namespace App\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Grants a mount to a player as a quest reward (source = SOURCE_QUEST).
 *
 * Resolves the mount by slug and delegates to MountAcquisitionService. If the
 * player already owns the mount, the call is a silent no-op so that quest
 * completion never fails because of an unrelated state transition.
 */
class MountQuestRewardGranter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MountAcquisitionService $mountAcquisitionService,
    ) {
    }

    /**
     * @return Mount|null the granted mount, or null when the slug is unknown
     *                    or the player already owns it (idempotent path)
     *
     * @throws \DomainException if the mount is disabled
     */
    public function grantBySlug(Player $player, string $slug, bool $flush = true): ?Mount
    {
        $mount = $this->entityManager->getRepository(Mount::class)->findOneBy(['slug' => $slug]);
        if ($mount === null) {
            return null;
        }

        try {
            $this->mountAcquisitionService->grantMount($player, $mount, PlayerMount::SOURCE_QUEST, $flush);
        } catch (MountAlreadyOwnedException) {
            return null;
        }

        return $mount;
    }
}
