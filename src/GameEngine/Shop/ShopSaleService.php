<?php

namespace App\GameEngine\Shop;

use App\Entity\App\Guild;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerShop;
use App\Entity\App\ShopListing;
use App\Entity\App\ShopSaleLog;
use App\GameEngine\Auction\AuctionSettlement;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\RegionBonusProvider;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Region\PlayerRegionResolver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Achat en echoppe et encaissement de la caisse (ECO-11).
 *
 * La vente est **asynchrone** : elle a lieu sans que le proprietaire soit
 * connecte. C'est ce qui distingue l'echoppe d'un troc, et ce qui impose trois
 * choses — l'escrow (ECO-10), une caisse ou deposer les recettes, et un
 * journal pour que le proprietaire comprenne ce qui s'est passe en son absence.
 *
 * La repartition des Gils reutilise `AuctionSettlement` : la taxe de cite ne
 * doit pas dependre du canal de vente. Deux calculs concurrents auraient fini
 * par diverger, et un vendeur aurait appris a arbitrer entre HV et echoppe sur
 * un detail d'implementation.
 */
class ShopSaleService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerRegionResolver $regionResolver,
        private readonly TownControlManager $townControlManager,
        private readonly GuildManager $guildManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Achete un lot expose.
     *
     * @throws \InvalidArgumentException si l'achat est refuse
     */
    public function buy(Player $buyer, ShopListing $listing): ShopSaleLog
    {
        $shop = $listing->getShop();

        if (!$shop->sells()) {
            throw new \InvalidArgumentException('Cette echoppe est fermee.');
        }

        if ($shop->getOwner()->getId() === $buyer->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez pas acheter dans votre propre echoppe.');
        }

        // La position d'un joueur est sa zone (regle #7). Une echoppe est une
        // adresse : l'acheter a distance en ferait un second hotel des ventes,
        // et annulerait le cout de voyage qui donne son sens a la geographie.
        if ($buyer->getCurrentZone()?->getSlug() !== $shop->getZone()->getSlug()) {
            throw new \InvalidArgumentException('Rendez-vous sur place pour acheter dans cette echoppe.');
        }

        $ruler = $this->resolveRuler($shop);
        $settlement = $this->settle($shop, $buyer, $listing->getTotalPrice(), $ruler);

        if (!$buyer->removeGils($settlement->buyerCharge)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour cet achat.');
        }

        // Les recettes vont a la **caisse**, pas a la bourse du proprietaire :
        // il n'est pas la pour les recevoir, et l'encaissement differe est
        // precisement ce qui rend la vente asynchrone lisible.
        $shop->creditVault($settlement->sellerRevenue);

        $this->applyTax($shop, $settlement, $ruler);

        $item = $listing->getPlayerItem();
        $itemName = $item->getGenericItem()->getName();
        $item->setInventory($this->bagInventory($buyer));

        $log = new ShopSaleLog(
            $shop,
            $buyer,
            $buyer->getName(),
            $itemName,
            $listing->getQuantity(),
            $listing->getUnitPrice(),
            $settlement->taxAmount,
            $settlement->sellerRevenue,
        );

        $this->entityManager->persist($log);
        $this->entityManager->remove($listing);
        $this->entityManager->flush();

        $this->logger->info('Shop sale', [
            'shop_id' => $shop->getId(),
            'buyer_id' => $buyer->getId(),
            'item' => $itemName,
            'total' => $listing->getTotalPrice(),
            'tax' => $settlement->taxAmount,
        ]);

        return $log;
    }

    /**
     * Encaisse la caisse dans la bourse du proprietaire.
     *
     * @return int montant encaisse
     */
    public function collectVault(Player $player, PlayerShop $shop): int
    {
        if ($shop->getOwner()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cette echoppe n\'est pas la votre.');
        }

        $amount = $shop->emptyVault();
        if ($amount <= 0) {
            throw new \InvalidArgumentException('La caisse est vide.');
        }

        $player->addGils($amount);
        $this->entityManager->flush();

        $this->logger->info('Shop vault collected', [
            'shop_id' => $shop->getId(),
            'amount' => $amount,
        ]);

        return $amount;
    }

    private function settle(PlayerShop $shop, Player $buyer, int $totalPrice, ?Guild $ruler): AuctionSettlement
    {
        $buyerGuild = null !== $ruler ? $this->guildManager->getPlayerGuild($buyer) : null;
        $buyerIsMember = null !== $ruler && null !== $buyerGuild && $buyerGuild->getId() === $ruler->getId();

        return AuctionSettlement::compute(
            $totalPrice,
            (float) ($this->regionResolver->resolveForZone($shop->getZone())?->getTaxRate() ?? '0.0000'),
            null !== $ruler,
            $buyerIsMember,
            RegionBonusProvider::MEMBER_DISCOUNT,
        );
    }

    /**
     * Guilde controlant la cite ou se tient l'echoppe, resolue **une seule
     * fois** par vente : la repartition et le versement en ont tous deux
     * besoin, et deux lectures ouvriraient la porte a une incoherence si le
     * controle basculait entre les deux.
     */
    private function resolveRuler(PlayerShop $shop): ?Guild
    {
        $region = $this->regionResolver->resolveForZone($shop->getZone());

        return null !== $region ? $this->townControlManager->getControllingGuild($region) : null;
    }

    /**
     * Verse la part de taxe a la guilde controlante — ou constate la
     * destruction des Gils quand la cite n'a pas de maitre.
     *
     * Meme regle qu'a l'hotel des ventes (ECO-04) : les Gils ont ete retires a
     * l'acheteur sans aller au vendeur. Sans guilde pour les recevoir, ils
     * **sortent du jeu**. On le journalise, sans quoi une refonte pourrait les
     * rendre au vendeur en croyant corriger une fuite.
     */
    private function applyTax(PlayerShop $shop, AuctionSettlement $settlement, ?Guild $ruler): void
    {
        if ($settlement->burnedAmount > 0) {
            $this->logger->info('Shop tax burned (city has no ruling guild)', [
                'zone' => $shop->getZone()->getSlug(),
                'amount' => $settlement->burnedAmount,
            ]);

            return;
        }

        if (null === $ruler || $settlement->treasuryAmount <= 0) {
            return;
        }

        $ruler->addGilsTreasury($settlement->treasuryAmount);

        $this->logger->info('Shop tax transferred to guild treasury', [
            'zone' => $shop->getZone()->getSlug(),
            'guild' => $ruler->getName(),
            'amount' => $settlement->treasuryAmount,
        ]);
    }

    private function bagInventory(Player $player): Inventory
    {
        foreach ($player->getInventories() as $inventory) {
            if (Inventory::TYPE_BAG === $inventory->getType()) {
                return $inventory;
            }
        }

        throw new \InvalidArgumentException('Vous n\'avez pas d\'inventaire sac.');
    }
}
