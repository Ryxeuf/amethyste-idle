<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Player;
use App\Entity\App\PlayerVisitedZone;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\Event\Zone\PlayerTraveledEvent;
use App\Event\Zone\ZoneVisitedEvent;
use App\GameEngine\Mount\MountTravelSpeed;
use App\Repository\PlayerVisitedZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Voyage entre zones (pivot PBBG, ZON-06).
 *
 * Le voyage est time-gated en temps reel : depart immediat, arrivee resolue
 * paresseusement (settleArrival) au prochain chargement d'ecran ou avant toute
 * action — aucun cron. L'arrivee enregistre la decouverte de la zone, qui
 * deverrouille les liaisons rapides (requiresDiscovery).
 */
class ZoneTravelService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerVisitedZoneRepository $visitedZoneRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MountTravelSpeed $mountTravelSpeed,
    ) {
    }

    /**
     * Demarre un voyage via une connexion. Retourne l'horodatage d'arrivee
     * (deja passe si la liaison est instantanee : l'arrivee est reglee inline).
     *
     * @throws ZoneTravelException si le voyage est refuse (cle de traduction en message)
     */
    public function startTravel(Player $player, ZoneConnection $connection): \DateTimeImmutable
    {
        $this->settleArrival($player, false);

        if ($player->isTraveling()) {
            throw new ZoneTravelException('game.zone.travel.error.already_traveling');
        }
        if (null !== $player->getFight()) {
            throw new ZoneTravelException('game.zone.travel.error.in_fight');
        }
        if ($player->getCurrentZone() !== $connection->getFromZone()) {
            throw new ZoneTravelException('game.zone.travel.error.wrong_origin');
        }

        // MJ : les garde-fous de progression tombent. Une liaison desactivee ou
        // une zone pas encore decouverte, c'est precisement ce qu'il doit
        // pouvoir aller voir — un contenu en preparation, ou un joueur bloque
        // au bout d'un chemin qu'il n'a lui-meme jamais emprunte. Les deux
        // refus qui restent au-dessus valent pour tout le monde : on ne part pas
        // deux fois, et on ne part pas d'un combat.
        $isGameMaster = $player->isGameMaster();

        if (!$isGameMaster) {
            if (!$connection->isEnabled() || !$connection->getToZone()->isEnabled()) {
                throw new ZoneTravelException('game.zone.travel.error.unavailable');
            }
            if ($connection->requiresDiscovery() && !$this->visitedZoneRepository->hasVisited($player, $connection->getToZone())) {
                throw new ZoneTravelException('game.zone.travel.error.not_discovered');
            }
        }

        // Duree reellement subie : la monture active raccourcit le voyage
        // (tache 130), sans jamais alterer la duree de reference du graphe.
        // Pour un MJ, la duree est nulle : l'arrivee se regle dans la foulee,
        // par le meme chemin que les liaisons instantanees.
        $seconds = $isGameMaster
            ? 0
            : $this->mountTravelSpeed->effectiveTravelSeconds($player, $connection->getTravelSeconds());

        $startedAt = new \DateTimeImmutable();
        $arrivesAt = $startedAt->modify(sprintf('+%d seconds', $seconds));
        $player->setTravelToZone($connection->getToZone());
        $player->setTravelStartedAt($startedAt);
        $player->setTravelArrivesAt($arrivesAt);

        // Liaison instantanee (interieurs...) : arrivee reglee dans la foulee.
        $this->settleArrival($player, false);

        $this->entityManager->flush();

        return $arrivesAt;
    }

    /**
     * Regle l'arrivee si l'heure est passee. Retourne la zone atteinte, ou
     * null si aucun voyage n'est arrive a terme.
     */
    public function settleArrival(Player $player, bool $flush = true): ?Zone
    {
        $destination = $player->getTravelToZone();
        $arrivesAt = $player->getTravelArrivesAt();
        if (null === $destination || null === $arrivesAt || $arrivesAt > new \DateTimeImmutable()) {
            return null;
        }

        $origin = $player->getCurrentZone();

        $player->setCurrentZone($destination);
        $player->setTravelToZone(null);
        $player->setTravelStartedAt(null);
        $player->setTravelArrivesAt(null);
        $this->markZoneVisited($player, $destination, false);

        if ($flush) {
            $this->entityManager->flush();
        }

        // Arrivee effective : point d'accroche du modele zone (ZON-22) pour le
        // tutoriel, la decouverte de region et le suivi de quetes. Emis a chaque
        // voyage, contrairement a ZoneVisitedEvent (premiere decouverte).
        $this->eventDispatcher->dispatch(
            new PlayerTraveledEvent($player, $destination, $origin),
            PlayerTraveledEvent::NAME
        );

        return $destination;
    }

    /**
     * Enregistre la decouverte d'une zone. Idempotent.
     */
    public function markZoneVisited(Player $player, Zone $zone, bool $flush = true): void
    {
        if ($this->visitedZoneRepository->hasVisited($player, $zone)) {
            return;
        }

        $this->entityManager->persist(new PlayerVisitedZone($player, $zone));

        if ($flush) {
            $this->entityManager->flush();
        }

        // Premiere decouverte : notifie les abonnes (deblocage Codex zone_visit, NAR-05).
        $this->eventDispatcher->dispatch(new ZoneVisitedEvent($player, $zone), ZoneVisitedEvent::NAME);
    }
}
