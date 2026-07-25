<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Event\Map\PlayerRespawnedEvent;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Derive Player::currentZone depuis la carte de rattachement (Zone::sourceMap).
 *
 * Depuis ZON-22, ce service n'est plus branche sur le deplacement : la zone est
 * la source de verite de la position (regle projet #7) et le voyage la met a
 * jour directement (ZoneTravelService). Il subsiste comme **amorce** pour les
 * cas ou un joueur n'a pas encore de zone : creation de personnage
 * (PlayerFactory), sortie de donjon (DungeonManager), teleportation
 * (GoldSinkManager), repli de l'ecran de zone, et respawn.
 *
 * Les cartes sans zone (donjons instancies, carte de test) laissent la zone
 * courante inchangee : le joueur y retournera en sortant.
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
            PlayerRespawnedEvent::NAME => 'onPlayerRespawned',
        ];
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
