<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Player;
use App\Entity\App\PlayerVisitedZone;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\Enum\QuestGesture;
use App\Event\Game\PlayerGestureEvent;
use App\Event\Zone\PlayerTraveledEvent;
use App\Event\Zone\ZoneVisitedEvent;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Mount\MountTravelSpeed;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\GameEngine\Reputation\ShadowsSmuggling;
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
        private readonly GameMasterPolicy $gameMasterPolicy,
        private readonly HostileConsequenceResolver $hostileConsequences,
        private readonly ShadowsSmuggling $shadowsSmuggling,
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
        //
        // La question passe par la politique et non par le drapeau : c'est le
        // point d'accroche des portes a venir. Quand FAC-09 posera les cinq
        // portes de faction — zones cachees derriere un palier de reputation —
        // elles interrogeront `bypassesAccessGates()` plutot que de reimplementer
        // la regle, et le MJ les franchira sans qu'on y revienne.
        $isGameMaster = $this->gameMasterPolicy->bypassesAccessGates($player);

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

        // FAC-03 — les fouilles de l'Ordre : un joueur Hostile aux Chevaliers
        // entre plus lentement dans une zone a foyer Bastion. Un surcout de
        // temps sur une liaison qui en coutait deja, **jamais un refus** — le
        // voyage de base est un droit que l'hostilite ne ferme pas, et une
        // liaison instantanee le reste (0 majore de 50 % vaut 0).
        if (!$isGameMaster && $seconds > 0) {
            $surcharge = $this->hostileConsequences->travelSurchargePercent($player, $connection->getToZone());
            if ($surcharge > 0) {
                // Arithmetique entiere (plafond), pas de flottant : voir la
                // surcharge boutique.
                $seconds = intdiv($seconds * (100 + $surcharge) + 99, 100);
            }
        }

        // FAC-08 — la fouille aux portes : un ballot de contrebande en transit
        // peut etre confisque a l'entree d'une zone a foyer Bastion. Le
        // contrat, jamais l'inventaire — et jamais un refus de voyage : on
        // entre quand meme, deleste et decote. Inerte tant qu'aucun foyer n'a
        // bascule Bastion.
        if (!$isGameMaster) {
            $this->shadowsSmuggling->inspectAtGates($player, $connection->getToZone());
        }

        // ONB-10 — le **premier voyage est offert**, une seule fois.
        //
        // L'acte I demande de rejoindre une zone pour y recolter (etape 7) bien
        // avant d'enseigner que le voyage coute du temps reel (etape 9). Sans
        // cette exception, la chaine s'arrete net sur une attente de quatre a
        // dix minutes qu'aucune etape n'a preparee, juste avant la premiere
        // recolte — soit exactement le moment ou l'on ferme l'onglet.
        //
        // La faveur se consomme meme si le voyage etait deja instantane : ce qui
        // est offert est **le premier voyage**, pas la premiere attente. La
        // garder pour plus tard en ferait une monnaie a optimiser.
        if (!$isGameMaster && $player->hasFirstTravelOffer()) {
            $seconds = 0;
            $player->spendFirstTravel();
        }

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

        // ONB-12b : l'etape 9 de l'acte I propose trois destinations et n'en
        // impose aucune. La cible est la zone atteinte ; une quete qui ne la
        // declare pas se contente du depart, ce qui est la lecon.
        $this->eventDispatcher->dispatch(
            new PlayerGestureEvent(QuestGesture::Travel, [$destination->getSlug()]),
            PlayerGestureEvent::NAME,
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
