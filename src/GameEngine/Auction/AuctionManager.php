<?php

namespace App\GameEngine\Auction;

use App\Entity\App\AuctionListing;
use App\Entity\App\AuctionTransaction;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Enum\AuctionStatus;
use Doctrine\ORM\EntityManagerInterface;

class AuctionManager
{
    private const LISTING_FEE_RATE = 0.05;
    private const DEFAULT_DURATION_HOURS = 48;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createListing(Player $seller, PlayerItem $playerItem, int $pricePerUnit, int $quantity = 1): AuctionListing
    {
        if ($pricePerUnit < 1) {
            throw new \InvalidArgumentException('Le prix doit etre superieur a 0.');
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantite doit etre superieure a 0.');
        }

        $totalPrice = $pricePerUnit * $quantity;
        $listingFee = (int) ceil($totalPrice * self::LISTING_FEE_RATE);

        if (!$seller->removeGils($listingFee)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour payer les frais de mise en vente.');
        }

        $regionTaxRate = $this->getRegionTaxRate($seller);

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($playerItem);
        $listing->setQuantity($quantity);
        $listing->setPricePerUnit($pricePerUnit);
        $listing->setListingFee($listingFee);
        $listing->setRegionTaxRate($regionTaxRate);
        $listing->setExpiresAt(new \DateTimeImmutable('+' . self::DEFAULT_DURATION_HOURS . ' hours'));

        $playerItem->setInventory(null);

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        return $listing;
    }

    public function buyListing(Player $buyer, AuctionListing $listing): AuctionTransaction
    {
        if (!$listing->isActive()) {
            throw new \InvalidArgumentException('Cette annonce n\'est plus disponible.');
        }

        if ($listing->isExpired()) {
            throw new \InvalidArgumentException('Cette annonce a expire.');
        }

        if ($listing->getSeller()->getId() === $buyer->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez pas acheter votre propre annonce.');
        }

        $totalPrice = $listing->getTotalPrice();

        $regionTaxAmount = (int) floor($totalPrice * (float) $listing->getRegionTaxRate());
        $sellerRevenue = $totalPrice - $regionTaxAmount;

        if (!$buyer->removeGils($totalPrice)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour cet achat.');
        }
        $listing->getSeller()->addGils($sellerRevenue);

        $listing->setStatus(AuctionStatus::Sold);

        $this->transferItemToBuyer($buyer, $listing->getPlayerItem());

        $transaction = new AuctionTransaction();
        $transaction->setListing($listing);
        $transaction->setBuyer($buyer);
        $transaction->setTotalPrice($totalPrice);
        $transaction->setRegionTaxAmount($regionTaxAmount);
        $transaction->setPurchasedAt(new \DateTimeImmutable());

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    public function cancelListing(Player $player, AuctionListing $listing): void
    {
        if (!$listing->isActive()) {
            throw new \InvalidArgumentException('Cette annonce n\'est plus active.');
        }

        if ($listing->getSeller()->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez annuler que vos propres annonces.');
        }

        $listing->setStatus(AuctionStatus::Cancelled);

        $this->returnItemToSeller($listing);

        $this->entityManager->flush();
    }

    public function expireListings(): int
    {
        $now = new \DateTimeImmutable();

        $expired = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(AuctionListing::class, 'l')
            ->where('l.status = :status')
            ->andWhere('l.expiresAt <= :now')
            ->setParameter('status', AuctionStatus::Active)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $count = 0;
        /** @var AuctionListing $listing */
        foreach ($expired as $listing) {
            $listing->setStatus(AuctionStatus::Expired);
            $this->returnItemToSeller($listing);
            ++$count;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    private function transferItemToBuyer(Player $buyer, PlayerItem $item): void
    {
        $bagInventory = $this->getBagInventory($buyer);
        $item->setInventory($bagInventory);
    }

    private function returnItemToSeller(AuctionListing $listing): void
    {
        $bagInventory = $this->getBagInventory($listing->getSeller());
        $listing->getPlayerItem()->setInventory($bagInventory);
    }

    private function getBagInventory(Player $player): Inventory
    {
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->getType() === Inventory::TYPE_BAG) {
                return $inventory;
            }
        }

        throw new \RuntimeException('Le joueur n\'a pas d\'inventaire sac.');
    }

    private function getRegionTaxRate(Player $seller): string
    {
        $map = $seller->getMap();
        if ($map === null) {
            return '0.0000';
        }

        $region = $map->getRegion();
        if ($region === null) {
            return '0.0000';
        }

        return $region->getTaxRate();
    }
}
