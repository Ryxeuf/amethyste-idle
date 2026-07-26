<?php

namespace App\GameEngine\Shop;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\PlayerShop;
use App\Entity\App\ShopListing;
use App\Enum\CraftSpecialization;
use App\Enum\ShopStatus;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Housing\HousingManager;
use App\Repository\PlayerShopRepository;
use App\Repository\ShopListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Ouverture et approvisionnement des echoppes joueur (ECO-10).
 *
 * L'echoppe est la vitrine persistante d'un artisan : elle vend en son absence,
 * porte son nom, et a une adresse. Elle differe de l'hotel des ventes, qui est
 * un carnet d'ordres anonyme ou seul le prix distingue les vendeurs.
 *
 * Ce jalon pose les entites, le gardien d'ouverture et l'escrow. La vente
 * elle-meme, la caisse et le loyer suivent en ECO-11.
 */
class ShopManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerShopRepository $shopRepository,
        private readonly ShopListingRepository $listingRepository,
        private readonly HousingManager $housingManager,
        private readonly CraftingManager $craftingManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getShop(Player $player): ?PlayerShop
    {
        return $this->shopRepository->findForOwner($player);
    }

    /**
     * Meilleur niveau de metier du joueur, tous crafts confondus.
     *
     * L'echoppe ne demande pas un metier **particulier** : un maitre joaillier
     * a autant sa place dans la rue qu'un forgeron.
     */
    public function bestCraftLevel(Player $player): int
    {
        $best = 0;
        foreach (CraftSpecialization::cases() as $craft) {
            $best = max($best, $this->craftingManager->getCraftingLevel($player, $craft->craftSlug()));
        }

        return $best;
    }

    /**
     * Ouvre l'echoppe d'un artisan.
     *
     * Double gardien, et les deux disent la meme chose sous deux angles :
     * l'artisan doit avoir **pignon sur rue** (une demeure, donc une adresse)
     * et **de quoi vendre** (un metier au-dela du T1 que les PNJ ecoulent
     * deja). Sans le premier, l'echoppe n'aurait nulle part ou exister ; sans
     * le second, elle ferait doublon avec le plancher PNJ d'ECO-02.
     *
     * @throws \InvalidArgumentException si l'ouverture est refusee
     */
    public function open(Player $player, string $name): PlayerShop
    {
        if (null !== $this->getShop($player)) {
            throw new \InvalidArgumentException('Vous tenez deja une echoppe.');
        }

        $house = $this->housingManager->getHouse($player);
        if (null === $house) {
            throw new \InvalidArgumentException('Il faut posseder une demeure pour ouvrir une echoppe.');
        }

        $level = $this->bestCraftLevel($player);
        if ($level < PlayerShop::MIN_CRAFT_LEVEL) {
            throw new \InvalidArgumentException(sprintf('Il faut le niveau %d dans un metier d\'artisanat pour ouvrir une echoppe (votre meilleur : %d).', PlayerShop::MIN_CRAFT_LEVEL, $level));
        }

        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('Donnez un nom a votre echoppe.');
        }

        $shop = new PlayerShop();
        $shop->setOwner($player);
        // L'echoppe suit la demeure : c'est la que le joueur a pignon sur rue.
        $shop->setZone($house->getZone());
        $shop->setName($name);

        $this->entityManager->persist($shop);
        $this->entityManager->flush();

        $this->logger->info('Player shop opened', [
            'player_id' => $player->getId(),
            'zone' => $house->getZone()->getSlug(),
            'craft_level' => $level,
        ]);

        return $shop;
    }

    public function rename(Player $player, PlayerShop $shop, string $name): void
    {
        $this->assertOwnership($player, $shop);

        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('Donnez un nom a votre echoppe.');
        }

        $shop->setName($name);
        $this->entityManager->flush();
    }

    public function setSign(Player $player, PlayerShop $shop, ?string $sign): void
    {
        $this->assertOwnership($player, $shop);

        $shop->setSign($sign);
        $this->entityManager->flush();
    }

    /**
     * Expose un lot en vitrine.
     *
     * L'objet part en **escrow** des le depot, comme a l'hotel des ventes :
     * l'echoppe vendant en l'absence de son proprietaire, il n'y a personne
     * pour arbitrer si l'objet a ete consomme entre-temps.
     *
     * @throws \InvalidArgumentException si le depot est refuse
     */
    public function stock(Player $player, PlayerShop $shop, PlayerItem $playerItem, int $quantity, int $unitPrice): ShopListing
    {
        $this->assertOwnership($player, $shop);

        // ECO-01 : garde-fou cote service. L'ecran filtre deja les objets lies,
        // mais une requete forgee ne passe pas par l'ecran.
        if (!$playerItem->isExchangeable()) {
            throw new \InvalidArgumentException('Cet objet est lie a son proprietaire et ne peut pas etre vendu.');
        }

        $inventory = $playerItem->getInventory();
        if (null === $inventory || $inventory->getPlayer()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cet objet n\'est pas dans votre inventaire.');
        }

        if ($this->listingRepository->countForShop($shop) >= $shop->getSlotCount()) {
            throw new \InvalidArgumentException(sprintf('Votre echoppe n\'a que %d emplacements, tous occupes.', $shop->getSlotCount()));
        }

        $listing = new ShopListing($shop, $playerItem, $quantity, $unitPrice);
        $playerItem->setInventory(null);

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        $this->logger->info('Shop listing created', [
            'shop_id' => $shop->getId(),
            'item' => $playerItem->getGenericItem()->getName(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ]);

        return $listing;
    }

    /**
     * Retire un lot de la vitrine et le rend a l'inventaire.
     */
    public function unstock(Player $player, ShopListing $listing): void
    {
        $this->assertOwnership($player, $listing->getShop());

        $listing->getPlayerItem()->setInventory($this->bagInventory($player));
        $this->entityManager->remove($listing);
        $this->entityManager->flush();
    }

    public function reprice(Player $player, ShopListing $listing, int $unitPrice): void
    {
        $this->assertOwnership($player, $listing->getShop());

        $listing->setUnitPrice($unitPrice);
        $this->entityManager->flush();
    }

    /**
     * Ferme ou rouvre l'echoppe.
     *
     * La fermeture ne touche pas au stock : c'est un rideau, pas un
     * demenagement. Une echoppe en impaye ne se rouvre pas d'un clic — le
     * loyer se regle d'abord (ECO-11).
     */
    public function setOpen(Player $player, PlayerShop $shop, bool $open): void
    {
        $this->assertOwnership($player, $shop);

        if (ShopStatus::Arrears === $shop->getStatus()) {
            throw new \InvalidArgumentException('Reglez le loyer avant de rouvrir votre echoppe.');
        }

        $shop->setStatus($open ? ShopStatus::Open : ShopStatus::Closed);
        $this->entityManager->flush();
    }

    /**
     * Sac du joueur : la ou revient un lot retire de la vitrine.
     */
    private function bagInventory(Player $player): Inventory
    {
        foreach ($player->getInventories() as $inventory) {
            if (Inventory::TYPE_BAG === $inventory->getType()) {
                return $inventory;
            }
        }

        throw new \InvalidArgumentException('Vous n\'avez pas d\'inventaire sac.');
    }

    private function assertOwnership(Player $player, PlayerShop $shop): void
    {
        if ($shop->getOwner()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cette echoppe n\'est pas la votre.');
        }
    }
}
