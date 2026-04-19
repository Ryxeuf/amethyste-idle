<?php

namespace App\GameEngine\Mount;

use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Entity\Game\Mount;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Attribue une monture a un joueur.
 *
 * Idempotent : si le joueur possede deja la monture, retourne l'enregistrement existant
 * sans creer de doublon ni toucher la source d'obtention initiale.
 */
class MountGranter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerMountRepository $playerMountRepository,
    ) {
    }

    /**
     * Accorde une monture au joueur.
     *
     * @param string $source l'une des constantes PlayerMount::SOURCE_*
     */
    public function grant(Player $player, Mount $mount, string $source): PlayerMount
    {
        $existing = $this->playerMountRepository->findOneByPlayerAndMount($player, $mount);
        if (null !== $existing) {
            return $existing;
        }

        $playerMount = new PlayerMount($player, $mount, $source);
        $this->entityManager->persist($playerMount);
        $this->entityManager->flush();

        return $playerMount;
    }

    public function playerOwnsMount(Player $player, Mount $mount): bool
    {
        return null !== $this->playerMountRepository->findOneByPlayerAndMount($player, $mount);
    }
}
