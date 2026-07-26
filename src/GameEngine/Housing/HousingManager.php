<?php

namespace App\GameEngine\Housing;

use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\Zone;
use App\Enum\HouseStyle;
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
        $house->setPurchasedAt($now = new \DateTimeImmutable());
        // La premiere periode est offerte : on ne fait pas payer un loyer le
        // jour meme ou l'on vient de debourser le terrain.
        $house->setRentDueAt($now->modify(sprintf('+%d days', PlayerHouse::RENT_PERIOD_DAYS)));

        $this->entityManager->persist($house);
        $this->entityManager->flush();

        $this->logger->info('House purchased', [
            'player_id' => $player->getId(),
            'zone' => $zone->getSlug(),
            'price' => PlayerHouse::LAND_PRICE,
        ]);

        return $house;
    }

    /**
     * Conditions de visite d'une demeure (HOU-03).
     *
     * Il faut se trouver **dans sa zone**. La position d'un joueur est sa zone
     * (regle #7) : une visite consultable de n'importe ou ferait du voisinage
     * un annuaire, la ou c'est un lieu.
     *
     * On peut visiter la sienne — c'est la meme vue, et l'interdire obligerait
     * l'appelant a traiter un cas particulier sans aucun gain.
     */
    public function assertCanVisit(Player $visitor, PlayerHouse $house): void
    {
        if ($visitor->getCurrentZone()?->getId() !== $house->getZone()->getId()) {
            throw new \InvalidArgumentException('Rendez-vous dans son quartier pour visiter cette demeure.');
        }
    }

    /**
     * Paiement du loyer par le proprietaire (HOU-04).
     *
     * L'echeance est reportee a partir de la **precedente** et non de
     * « maintenant » : payer en retard ne doit pas offrir une periode pleine,
     * sinon attendre serait rentable.
     */
    public function payRent(Player $player, PlayerHouse $house): void
    {
        $this->assertOwnership($player, $house);

        if (!$player->removeGils(PlayerHouse::RENT_AMOUNT)) {
            throw new \InvalidArgumentException(sprintf('Il vous faut %d Gils pour le loyer.', PlayerHouse::RENT_AMOUNT));
        }

        $house->extendRent();
        $this->entityManager->flush();

        $this->logger->info('House rent paid', [
            'player_id' => $player->getId(),
            'amount' => PlayerHouse::RENT_AMOUNT,
            'next_due' => $house->getRentDueAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * Preleve les loyers echus (HOU-04).
     *
     * Prelevement automatique tant que la bourse suit : un joueur solvable ne
     * doit pas perdre l'usage de sa demeure pour avoir oublie un bouton. Quand
     * la bourse ne suit pas, **rien n'est confisque** — la demeure passe en
     * arriere et cesse de rendre service jusqu'au paiement.
     *
     * @return array{charged: int, unpaid: int}
     */
    public function collectDueRents(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $charged = 0;
        $unpaid = 0;

        foreach ($this->houseRepository->findWithRentDue($now) as $house) {
            $owner = $house->getOwner();

            if ($owner->removeGils(PlayerHouse::RENT_AMOUNT)) {
                $house->extendRent();
                ++$charged;
                continue;
            }

            ++$unpaid;
            $this->logger->info('House rent unpaid, dwelling dormant', [
                'player_id' => $owner->getId(),
                'due_since' => $house->getRentDueAt()->format(\DateTimeInterface::ATOM),
            ]);
        }

        if ($charged > 0) {
            $this->entityManager->flush();
        }

        return ['charged' => $charged, 'unpaid' => $unpaid];
    }

    /**
     * Installe un ameublement (HOU-05).
     *
     * Purement cosmetique et payant : un **gold sink** qui ne cree aucune
     * pression a depenser chez ceux que l'apparence n'interesse pas.
     */
    public function furnish(Player $player, PlayerHouse $house, HouseStyle $style): void
    {
        $this->assertOwnership($player, $house);

        if ($house->getStyle() === $style) {
            throw new \InvalidArgumentException('Votre demeure est deja meublee ainsi.');
        }

        $price = $style->price();
        if ($price > 0 && !$player->removeGils($price)) {
            throw new \InvalidArgumentException(sprintf('Il vous faut %s Gils pour cet ameublement.', number_format($price, 0, ',', ' ')));
        }

        $house->setStyle($style);
        $this->entityManager->flush();

        $this->logger->info('House furnished', [
            'player_id' => $player->getId(),
            'style' => $style->value,
            'price' => $price,
        ]);
    }

    /**
     * Grave la devise du fronton (HOU-05).
     *
     * Gratuite, contrairement a l'ameublement : on ne fait pas payer un joueur
     * pour ecrire une phrase chez lui.
     */
    public function setMotto(Player $player, PlayerHouse $house, ?string $motto): void
    {
        $this->assertOwnership($player, $house);

        $house->setMotto($motto);
        $this->entityManager->flush();
    }

    private function assertOwnership(Player $player, PlayerHouse $house): void
    {
        if ($house->getOwner()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cette demeure n\'est pas la votre.');
        }
    }

    public function rename(Player $player, PlayerHouse $house, string $name): void
    {
        $this->assertOwnership($player, $house);

        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('Donnez un nom a votre demeure.');
        }

        $house->setName($name);
        $this->entityManager->flush();
    }
}
