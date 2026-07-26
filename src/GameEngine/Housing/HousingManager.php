<?php

namespace App\GameEngine\Housing;

use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\Zone;
use App\Repository\PlayerHouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Acquisition et consultation d'une demeure (tache 129, HOU-01).
 *
 * Le housing est un **gold sink** (GAME_PRINCIPLES §4.7) et le prerequis des
 * echoppes joueur (ECO-10). Ce jalon pose le terrain ; le jardin, les visites
 * et l'entretien suivent.
 */
class HousingManager
{
    /**
     * Zones ou l'on peut acquerir un terrain.
     *
     * Une liste explicite plutot qu'un drapeau sur `Zone` : le lotissement est
     * une decision de contenu, et la garder ici evite qu'une zone devienne
     * residentielle par accident en editant sa configuration.
     */
    public const RESIDENTIAL_ZONE_SLUGS = ['quartier-des-jardins'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHouseRepository $houseRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getHouse(Player $player): ?PlayerHouse
    {
        return $this->houseRepository->findForOwner($player);
    }

    public function isResidential(Zone $zone): bool
    {
        return \in_array($zone->getSlug(), self::RESIDENTIAL_ZONE_SLUGS, true);
    }

    /**
     * Achete le terrain et y batit la demeure.
     *
     * L'achat exige d'etre **sur place** : la position d'un joueur est sa zone
     * (regle #7), et acheter a distance retirerait au lotissement le seul cout
     * qui ne soit pas monetaire — le voyage.
     */
    public function buyLand(Player $player, Zone $zone, string $name): PlayerHouse
    {
        if (null !== $this->getHouse($player)) {
            throw new \InvalidArgumentException('Vous possedez deja une demeure.');
        }

        if (!$this->isResidential($zone)) {
            throw new \InvalidArgumentException('Aucun terrain n\'est a vendre dans cette zone.');
        }

        if ($player->getCurrentZone()?->getId() !== $zone->getId()) {
            throw new \InvalidArgumentException('Rendez-vous sur place pour acquerir ce terrain.');
        }

        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('Donnez un nom a votre demeure.');
        }

        // Les Gils partent avant la construction : si la bourse ne suit pas,
        // rien n'est engage.
        if (!$player->removeGils(PlayerHouse::LAND_PRICE)) {
            throw new \InvalidArgumentException(sprintf('Il vous faut %s Gils pour ce terrain.', number_format(PlayerHouse::LAND_PRICE, 0, ',', ' ')));
        }

        $house = new PlayerHouse();
        $house->setOwner($player);
        $house->setZone($zone);
        $house->setName($name);
        $house->setPurchasedAt(new \DateTimeImmutable());

        $this->entityManager->persist($house);
        $this->entityManager->flush();

        $this->logger->info('House purchased', [
            'player_id' => $player->getId(),
            'zone' => $zone->getSlug(),
            'price' => PlayerHouse::LAND_PRICE,
        ]);

        return $house;
    }

    public function rename(Player $player, PlayerHouse $house, string $name): void
    {
        if ($house->getOwner()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cette demeure n\'est pas la votre.');
        }

        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('Donnez un nom a votre demeure.');
        }

        $house->setName($name);
        $this->entityManager->flush();
    }
}
