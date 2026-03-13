<?php

namespace App\GameEngine\Map;

use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use Doctrine\ORM\EntityManagerInterface;

class PortalDetector
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Check if a portal exists at the given coordinates on the player's current map.
     */
    public function detectPortal(Player $player, string $coordinates): ?ObjectLayer
    {
        return $this->entityManager->getRepository(ObjectLayer::class)->findOneBy([
            'coordinates' => $coordinates,
            'map' => $player->getMap(),
            'type' => ObjectLayer::TYPE_PORTAL,
        ]);
    }
}
