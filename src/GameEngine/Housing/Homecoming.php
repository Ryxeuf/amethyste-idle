<?php

namespace App\GameEngine\Housing;

use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\Zone;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Zone\ZoneTravelService;
use App\Repository\PlayerHouseRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le retour au logis (FOY-20).
 *
 * GAME_WORLD § 12.6 d : *« une fois par jour, le voyage vers sa zone de
 * residence est instantane — la commodite de fin de session »*.
 *
 * **Jamais vers ailleurs.** La borne est dans la definition, pas dans un
 * reglage : ce service ne prend aucune destination, il n'y a donc **aucun
 * endroit ou en passer une**. Un teleporteur libre rendrait le graphe de zones
 * decoratif, et avec lui les montures, les durees de trajet et le choix
 * d'itineraire — c'est-a-dire tout ce que le pivot PBBG a mis a la place de la
 * carte navigable.
 *
 * **Les deux refus du voyage restent.** On ne rentre pas d'un combat, et on ne
 * rentre pas pendant qu'on voyage : la commodite raccourcit un trajet, elle
 * n'annule pas un etat. Les reprendre ici plutot que de passer par
 * `ZoneTravelService` est un choix — le retour au logis n'est pas une liaison du
 * graphe, et lui en inventer une ferait apparaitre une arete que la carte du
 * monde ne connaît pas.
 */
class Homecoming
{
    public function __construct(
        private readonly PlayerHouseRepository $houses,
        private readonly ZoneTravelService $travelService,
        private readonly SettlementDefinitionLoader $settlements,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function perDay(): int
    {
        return $this->settlements->load()['housing']['homecoming_per_day'];
    }

    /**
     * Ce joueur peut-il rentrer maintenant ?
     */
    public function isAvailableFor(Player $player, \DateTimeImmutable $now): bool
    {
        return $this->refusalFor($player, $now) === null;
    }

    /**
     * La raison du refus, ou `null` si le retour est possible.
     *
     * Une raison plutot qu'un booleen : un bouton grise sans explication se lit
     * comme une panne.
     */
    public function refusalFor(Player $player, \DateTimeImmutable $now): ?string
    {
        $house = $this->houses->findForOwner($player);

        if (!$house instanceof PlayerHouse) {
            return 'game.house.homecoming.error.no_home';
        }

        if ($player->getFight() !== null) {
            return 'game.house.homecoming.error.in_fight';
        }

        if ($player->isTraveling()) {
            return 'game.house.homecoming.error.traveling';
        }

        if ($player->getCurrentZone()?->getId() === $house->getZone()->getId()) {
            return 'game.house.homecoming.error.already_home';
        }

        if ($house->homecomingsUsedOn($this->dayKey($now)) >= $this->perDay()) {
            return 'game.house.homecoming.error.already_used';
        }

        return null;
    }

    /**
     * Rentre chez soi. Rend la zone de residence.
     *
     * @throws \InvalidArgumentException si le retour est refuse
     */
    public function comeHome(Player $player, \DateTimeImmutable $now): Zone
    {
        $refusal = $this->refusalFor($player, $now);
        if ($refusal !== null) {
            throw new \InvalidArgumentException($refusal);
        }

        /** @var PlayerHouse $house */
        $house = $this->houses->findForOwner($player);
        $home = $house->getZone();

        $player->setCurrentZone($home);
        $player->setTravelToZone(null);
        $player->setTravelStartedAt(null);
        $player->setTravelArrivesAt(null);

        // Sa propre zone de residence compte comme visitee : on n'habite pas un
        // lieu qu'on n'aurait pas decouvert, et l'oublier fermerait les liaisons
        // rapides qui en partent.
        $this->travelService->markZoneVisited($player, $home, false);

        $house->recordHomecoming($this->dayKey($now));
        $this->entityManager->flush();

        return $home;
    }

    private function dayKey(\DateTimeImmutable $now): string
    {
        return $now->format('Y-m-d');
    }
}
