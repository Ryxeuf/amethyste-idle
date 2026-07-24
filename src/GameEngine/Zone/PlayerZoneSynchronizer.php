<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Event\Map\PlayerMovedEvent;
use App\Event\Map\PlayerRespawnedEvent;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maintient Player::currentZone alignee sur la carte du joueur pendant la
 * transition vers le modele zone (pivot PBBG, ZON-03).
 *
 * Tant que la carte reste la source des deplacements (jusqu'a ZON-01/ZON-05),
 * la zone est derivee de Zone::sourceMap. Les cartes sans zone (donjons
 * instancies, carte de test) laissent la zone courante inchangee : le joueur
 * y retournera en sortant (cf. ZON-19 pour les donjons).
 */
class PlayerZoneSynchronizer implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerMovedEvent::NAME => 'onPlayerMoved',
            PlayerRespawnedEvent::NAME => 'onPlayerRespawned',
        ];
    }

    public function onPlayerMoved(PlayerMovedEvent $event): void
    {
        $this->syncFromMap($event->getPlayer(), true);
    }

    public function onPlayerRespawned(PlayerRespawnedEvent $event): void
    {
        $this->syncFromMap($event->getPlayer(), true);
    }

    /**
     * Aligne la zone courante sur la carte du joueur. Retourne la zone
     * effective apres synchronisation (inchangee si la carte n'a pas de zone).
     */
    public function syncFromMap(Player $player, bool $flush = false): ?Zone
    {
        $map = $player->getMap();
        if ($map === null) {
            return $player->getCurrentZone();
        }

        // Deja alignee : evite une requete par pas de deplacement intra-carte.
        $current = $player->getCurrentZone();
        if ($current !== null && $current->getSourceMap() === $map) {
            return $current;
        }

        $zone = $this->zoneRepository->findEnabledBySourceMap($map);
        if ($zone === null || $zone === $current) {
            return $current;
        }

        $player->setCurrentZone($zone);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $zone;
    }
}
