<?php

namespace App\GameEngine\Region;

use App\Entity\App\Player;
use App\Entity\App\PlayerVisitedRegion;
use App\Entity\App\Region;
use App\Event\Map\PlayerMovedEvent;
use App\Repository\PlayerVisitedRegionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Records the regions a player has set foot in. The data backs the fast-travel
 * destination filter: a region must have been visited at least once before the
 * player can teleport back to it (cf. tache 130 sous-phase 5).
 */
class RegionDiscoveryTracker implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerVisitedRegionRepository $repository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerMovedEvent::NAME => 'onPlayerMoved',
        ];
    }

    public function onPlayerMoved(PlayerMovedEvent $event): void
    {
        $this->recordCurrentRegion($event->getPlayer());
    }

    /**
     * Records the player's current region (if any) as visited. Idempotent: a
     * second call for the same (player, region) pair is a no-op.
     */
    public function recordCurrentRegion(Player $player, bool $flush = true): bool
    {
        $region = $player->getMap()?->getRegion();
        if (!$region instanceof Region) {
            return false;
        }

        if ($this->repository->hasVisited($player, $region)) {
            return false;
        }

        $this->entityManager->persist(new PlayerVisitedRegion($player, $region));

        if ($flush) {
            $this->entityManager->flush();
        }

        return true;
    }
}
