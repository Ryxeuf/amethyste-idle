<?php

namespace App\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Grants mounts to players independently of the acquisition channel
 * (quest reward, monster drop, shop purchase, achievement, admin action).
 */
class MountAcquisitionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerMountRepository $playerMountRepository,
    ) {
    }

    /**
     * @throws MountAlreadyOwnedException if the player already owns the mount
     * @throws \DomainException           if the mount is disabled or the player is below requiredLevel
     */
    public function grantMount(Player $player, Mount $mount, string $source, bool $flush = true): PlayerMount
    {
        if (!$mount->isEnabled()) {
            throw new \DomainException(sprintf('La monture "%s" est desactivee.', $mount->getSlug()));
        }

        if ($this->playerMountRepository->playerOwnsMount($player, $mount)) {
            throw new MountAlreadyOwnedException($player, $mount);
        }

        $playerMount = new PlayerMount($player, $mount, $source);
        $this->entityManager->persist($playerMount);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $playerMount;
    }
}
