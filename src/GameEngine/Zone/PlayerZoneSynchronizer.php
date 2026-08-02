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
 * (PlayerFactory), teleportation
 * (GoldSinkManager), repli de l'ecran de zone, et respawn.
 *
 * Les cartes sans zone (donjons instancies, carte de test) laissent la zone
 * courante inchangee : le joueur y retournera en sortant.
 *
 * `resolveOrAssign()` complete l'amorce : un joueur qui n'a **ni** zone **ni**
 * carte rattachee a une zone — cas de tout personnage reste sur la carte de
 * test, ou d'une base ou le hub n'a pas ete seede sous son slug attendu — se
 * retrouvait sans position, donc sans aucune action possible. Le repli remonte
 * jusqu'a une zone de depart plausible plutot que de rendre `null`.
 */
class PlayerZoneSynchronizer implements EventSubscriberInterface
{
    /**
     * Zone de depart canonique du monde 1.
     */
    public const HUB_SLUG = 'village-de-lumiere';

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
        $this->resolveOrAssign($event->getPlayer(), true);
    }

    /**
     * Position effective du joueur, en la lui attribuant si elle manque.
     *
     * Ordre de resolution, du plus precis au plus generique :
     *  1. la zone courante, si elle existe ;
     *  2. la zone de la carte de rattachement (transition pivot) ;
     *  3. le hub declare (`village-de-lumiere`) ;
     *  4. une zone de depart plausible restante (ville sure, puis ville, puis
     *     n'importe quelle zone hors donjon).
     *
     * Retourne `null` uniquement si le monde ne contient aucune zone active :
     * la seule situation ou « position inconnue » decrit vraiment la base.
     */
    public function resolveOrAssign(Player $player, bool $flush = false): ?Zone
    {
        $before = $player->getCurrentZone();

        $zone = $before ?? $this->syncFromMap($player);
        if (null === $zone) {
            $zone = $this->zoneRepository->findEnabledBySlug(self::HUB_SLUG)
                ?? $this->zoneRepository->findDefaultStartingZone();

            if (null !== $zone) {
                $player->setCurrentZone($zone);
            }
        }

        if ($flush && $player->getCurrentZone() !== $before) {
            $this->entityManager->flush();
        }

        return $zone;
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
