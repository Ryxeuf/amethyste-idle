<?php

namespace App\GameEngine\Housing;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\PlayerItem;
use App\Entity\App\Zone;
use App\Enum\HouseStyle;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\Helper\InventoryHelper;
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
     * Le plancher residentiel : les zones ou l'on achete SANS condition de
     * rang (FOY-18). Le Quartier des Jardins n'a pas de foyer du tout (bati
     * sur la Voute) — il ne peut pas etre une regle de rang, il est une
     * garantie : quoi qu'il arrive aux foyers, on peut toujours se loger la.
     * Le reste du lotissement est desormais une regle — tout foyer Hameau+
     * est residentiel, a capacite par rang (ResidentialParcels).
     */
    public const RESIDENTIAL_ZONE_SLUGS = ['quartier-des-jardins'];

    /**
     * Le necessaire d'ameublement du charpentier (ECO-30).
     *
     * Il remplace le prix en Gils d'un style, quel que soit ce style : un
     * necessaire meuble une demeure, et le luxe du Bourgeois se paie alors en
     * bois plutot qu'en monnaie.
     */
    public const FURNISHING_KIT_SLUG = 'crafted-furnishing-kit';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHouseRepository $houseRepository,
        private readonly LoggerInterface $logger,
        private readonly InventoryHelper $inventoryHelper,
        private readonly ResidentialParcels $residentialParcels,
        // En dernier : une dependance nouvelle s'ajoute en queue, jamais au
        // milieu — un service insere entre deux autres decalerait sans un mot
        // toute construction positionnelle dans les tests.
        private readonly HouseRentRouting $rentRouting,
        private readonly SettlementDefinitionLoader $settlements,
    ) {
    }

    public function getHouse(Player $player): ?PlayerHouse
    {
        return $this->houseRepository->findForOwner($player);
    }

    public function isResidential(Zone $zone): bool
    {
        // FOY-18 : le plancher (les Jardins, sans condition), puis la regle —
        // tout foyer au rang de Hameau ou plus est residentiel.
        return \in_array($zone->getSlug(), self::RESIDENTIAL_ZONE_SLUGS, true)
            || $this->residentialParcels->isRankResidential($zone);
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

        // FOY-18 : la capacite par rang ne gate que l'ouverture de NOUVELLES
        // parcelles — jamais une expulsion (decision A). Le plancher des
        // Jardins n'est pas soumis a la capacite : c'est sa definition.
        if (!\in_array($zone->getSlug(), self::RESIDENTIAL_ZONE_SLUGS, true)
            && !$this->residentialParcels->canOpenParcel($zone)) {
            throw new \InvalidArgumentException('Toutes les parcelles de ce foyer sont prises — la croissance du foyer en rouvrira.');
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

        // FOY-20 : le coffre naît avec la demeure et non avec le personnage —
        // on n'a pas de coffre avant d'avoir un logis. Cree ici plutot qu'a la
        // demande : un coffre qui apparaîtrait au premier depot ferait de son
        // existence un effet de bord, et l'ecran ne saurait pas quoi montrer
        // avant.
        $this->openHouseChest($player);

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

        // FOY-19 : le loyer devient politique. Dans une zone a foyer, il part au
        // tresor de la guilde controlante ; ailleurs, il sort du jeu.
        $this->rentRouting->route($house, PlayerHouse::RENT_AMOUNT);

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
                // FOY-19 : le prelevement automatique emprunte le meme chemin
                // que le paiement a la main. Router l'un et pas l'autre ferait
                // dependre le revenu d'une guilde du **bouton** sur lequel ses
                // habitants ont appuye.
                $this->rentRouting->route($house, PlayerHouse::RENT_AMOUNT);
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
     * Installe un ameublement (HOU-05, ECO-30).
     *
     * Purement cosmetique : un **gold sink** qui ne cree aucune pression a
     * depenser chez ceux que l'apparence n'interesse pas.
     *
     * Deux voies, et c'est ECO-30 qui ouvre la seconde. Le style se payait
     * **uniquement** en Gils — un cosmetique que rien de joueur ne produisait.
     * Le necessaire du charpentier le remplace : celui qui en a un meuble
     * gratuitement, et le charpentier a une raison de plus d'exister. La voie
     * marchande reste ouverte pour qui n'a pas d'artisan sous la main, sans quoi
     * le sink dependrait de la presence d'un metier.
     *
     * Le necessaire est **essaye d'abord** : un joueur qui en possede un ne
     * paie jamais, et n'a pas a le declarer.
     */
    public function furnish(Player $player, PlayerHouse $house, HouseStyle $style): void
    {
        $this->assertOwnership($player, $house);

        if ($house->getStyle() === $style) {
            throw new \InvalidArgumentException('Votre demeure est deja meublee ainsi.');
        }

        $price = $style->price();
        // `removeItemBySlug` puise dans le sac du joueur de la session, que
        // `assertOwnership` vient d'identifier a `$player`.
        $paidWithKit = $price > 0 && 1 === $this->inventoryHelper->removeItemBySlug(self::FURNISHING_KIT_SLUG, 1);

        if (!$paidWithKit && $price > 0 && !$player->removeGils($price)) {
            throw new \InvalidArgumentException(sprintf('Il vous faut %s Gils ou un necessaire d\'ameublement.', number_format($price, 0, ',', ' ')));
        }

        $house->setStyle($style);
        $this->entityManager->flush();

        $this->logger->info('House furnished', [
            'player_id' => $player->getId(),
            'style' => $style->value,
            'price' => $paidWithKit ? 0 : $price,
            'paid_with_kit' => $paidWithKit,
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

    /**
     * Le coffre domestique de ce joueur, ouvert s'il ne l'etait pas (FOY-20).
     *
     * Idempotent : un joueur n'a qu'un coffre, et l'appeler deux fois n'en
     * ouvre pas un second.
     */
    public function openHouseChest(Player $player): Inventory
    {
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->isHouse()) {
                return $inventory;
            }
        }

        $chest = new Inventory();
        $chest->setType(Inventory::TYPE_HOUSE);
        $chest->setSize($this->settlements->load()['housing']['chest_size']);
        $chest->setPlayer($player);
        $player->addInventory($chest);
        $this->entityManager->persist($chest);

        return $chest;
    }

    /**
     * Le coffre, s'il existe. `null` tant qu'on n'a pas de logis.
     */
    public function houseChest(Player $player): ?Inventory
    {
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->isHouse()) {
                return $inventory;
            }
        }

        return null;
    }

    /**
     * Deplace une piece entre le sac et le coffre (FOY-20).
     *
     * **Trois refus, et le premier est la propriete.** Un identifiant de piece
     * vient d'un formulaire : sans cette verification, un joueur rangerait chez
     * lui l'objet d'un autre.
     *
     * Le coffre refuse ce qui est **lie** ou **equipe**, pour la meme raison que
     * la banque et le coffre de guilde : on ne range pas ce qu'on porte, et une
     * piece liee n'a pas a bouger. Le predicat est celui de
     * `PlayerItem::isExchangeable()` — *« peut-il circuler ? » se demande une
     * fois* (FOY-19 l'a ramene a un seul endroit pour le coffre de guilde).
     */
    public function moveToChest(Player $player, int $playerItemId, bool $withdraw): void
    {
        $chest = $this->houseChest($player);
        if ($chest === null) {
            throw new \InvalidArgumentException('game.house.chest.error.no_chest');
        }

        $item = $this->entityManager->getRepository(PlayerItem::class)->find($playerItemId);
        if ($item === null || $item->getInventory()?->getPlayer()?->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('game.house.chest.error.not_yours');
        }

        if (!$item->isExchangeable()) {
            throw new \InvalidArgumentException('game.house.chest.error.not_movable');
        }

        $destination = $withdraw ? $this->bagOf($player) : $chest;
        if ($destination === null) {
            throw new \InvalidArgumentException('game.house.chest.error.no_chest');
        }

        if (\count($destination->getItems()) >= $destination->getSize()) {
            throw new \InvalidArgumentException('game.house.chest.error.full');
        }

        $item->getInventory()->removeItem($item);
        $item->setInventory($destination);
        $destination->addItem($item);

        $this->entityManager->flush();
    }

    private function bagOf(Player $player): ?Inventory
    {
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->isBag()) {
                return $inventory;
            }
        }

        return null;
    }
}
