<?php

namespace App\GameEngine\Auction;

use App\Entity\App\AuctionListing;
use App\Entity\App\AuctionTransaction;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Enum\AuctionStatus;
use App\Enum\AuctionType;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Notification\NotificationService;
use App\Repository\AuctionListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AuctionManager
{
    private const LISTING_FEE_RATE = 0.05;
    private const DEFAULT_DURATION_HOURS = 48;
    public const AUCTION_DURATION_HOURS = 24;
    public const AUCTION_MIN_INCREMENT = 1;
    public const MAX_ACTIVE_LISTINGS = 20;
    public const CANCEL_COOLDOWN_MINUTES = 5;
    public const FLASH_SALE_MIN_DURATION_HOURS = 1;
    public const FLASH_SALE_MAX_DURATION_HOURS = 12;
    public const FLASH_SALE_DEFAULT_DURATION_HOURS = 2;

    /** @var array<string, array{min: int, max: int}> */
    public const PRICE_LIMITS_BY_RARITY = [
        'common' => ['min' => 1, 'max' => 10_000],
        'uncommon' => ['min' => 5, 'max' => 50_000],
        'rare' => ['min' => 50, 'max' => 500_000],
        'epic' => ['min' => 200, 'max' => 2_000_000],
        'legendary' => ['min' => 1_000, 'max' => 10_000_000],
        'amethyst' => ['min' => 5_000, 'max' => 50_000_000],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuctionListingRepository $listingRepository,
        private readonly TownControlManager $townControlManager,
        private readonly LoggerInterface $logger,
        private readonly NotificationService $notificationService,
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

        $this->validatePriceLimits($playerItem, $pricePerUnit);
        $this->validateActiveListingsLimit($seller);
        $this->validateCancelCooldown($seller);

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

        $this->logger->info('Auction listing created', [
            'listing_id' => $listing->getId(),
            'seller_id' => $seller->getId(),
            'item' => $playerItem->getGenericItem()->getName(),
            'price_per_unit' => $pricePerUnit,
            'quantity' => $quantity,
            'listing_fee' => $listingFee,
        ]);

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

        if ($listing->isAuction()) {
            throw new \InvalidArgumentException('Cette annonce est une enchere : utilisez la mise pour enchereir.');
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

        $this->transferTaxToGuildTreasury($listing, $regionTaxAmount);

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

        $this->logger->info('Auction listing purchased', [
            'listing_id' => $listing->getId(),
            'buyer_id' => $buyer->getId(),
            'seller_id' => $listing->getSeller()->getId(),
            'total_price' => $totalPrice,
            'region_tax' => $regionTaxAmount,
        ]);

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

        if ($listing->isAuction() && $listing->getCurrentBidder() !== null) {
            throw new \InvalidArgumentException('Impossible d\'annuler une enchere avec des mises en cours.');
        }

        $listing->setStatus(AuctionStatus::Cancelled);
        $listing->setCancelledAt(new \DateTimeImmutable());

        $this->returnItemToSeller($listing);

        $this->entityManager->flush();

        $this->logger->info('Auction listing cancelled', [
            'listing_id' => $listing->getId(),
            'seller_id' => $player->getId(),
        ]);
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
            if ($listing->isAuction() && $listing->getCurrentBidder() !== null) {
                $this->finalizeAuction($listing);
            } else {
                $listing->setStatus(AuctionStatus::Expired);
                $this->returnItemToSeller($listing);
            }
            ++$count;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * Cree une annonce de type enchere.
     * L'acheteur verrouille les Gils en placant une mise (escrow).
     * A l'expiration, le plus offrant remporte l'objet (cf. finalizeAuction).
     */
    public function createAuctionListing(Player $seller, PlayerItem $playerItem, int $startingPrice, int $minIncrement = self::AUCTION_MIN_INCREMENT, int $quantity = 1): AuctionListing
    {
        if ($startingPrice < 1) {
            throw new \InvalidArgumentException('Le prix de depart doit etre superieur a 0.');
        }

        if ($minIncrement < 1) {
            throw new \InvalidArgumentException('L\'increment minimum doit etre superieur a 0.');
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantite doit etre superieure a 0.');
        }

        $this->validatePriceLimits($playerItem, $startingPrice);
        $this->validateActiveListingsLimit($seller);
        $this->validateCancelCooldown($seller);

        $totalPrice = $startingPrice * $quantity;
        $listingFee = (int) ceil($totalPrice * self::LISTING_FEE_RATE);

        if (!$seller->removeGils($listingFee)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour payer les frais de mise en vente.');
        }

        $regionTaxRate = $this->getRegionTaxRate($seller);

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($playerItem);
        $listing->setQuantity($quantity);
        $listing->setPricePerUnit($startingPrice);
        $listing->setListingFee($listingFee);
        $listing->setRegionTaxRate($regionTaxRate);
        $listing->setType(AuctionType::Auction);
        $listing->setMinIncrement($minIncrement);
        $listing->setExpiresAt(new \DateTimeImmutable('+' . self::AUCTION_DURATION_HOURS . ' hours'));

        $playerItem->setInventory(null);

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        $this->logger->info('Auction listing created', [
            'listing_id' => $listing->getId(),
            'seller_id' => $seller->getId(),
            'item' => $playerItem->getGenericItem()->getName(),
            'starting_price' => $startingPrice,
            'min_increment' => $minIncrement,
            'quantity' => $quantity,
            'listing_fee' => $listingFee,
        ]);

        return $listing;
    }

    /**
     * Cree une vente flash administrative : item vendu par l'admin (seller) a prix reduit,
     * pour une duree courte et limitee. Les ventes flash :
     *  - ignorent les frais de mise en vente (LISTING_FEE_RATE)
     *  - ignorent la limite d'annonces actives et le cooldown d'annulation
     *  - ignorent les bornes de prix par rarete (prix libre, potentiellement tres bas)
     *  - conservent la taxe regionale pour coherence avec les autres ventes.
     */
    public function createFlashSaleListing(Player $adminSeller, PlayerItem $playerItem, int $pricePerUnit, int $durationHours = self::FLASH_SALE_DEFAULT_DURATION_HOURS, int $quantity = 1): AuctionListing
    {
        if ($pricePerUnit < 1) {
            throw new \InvalidArgumentException('Le prix doit etre superieur a 0.');
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantite doit etre superieure a 0.');
        }

        if ($durationHours < self::FLASH_SALE_MIN_DURATION_HOURS || $durationHours > self::FLASH_SALE_MAX_DURATION_HOURS) {
            throw new \InvalidArgumentException(sprintf('La duree d\'une vente flash doit etre comprise entre %d et %d heures.', self::FLASH_SALE_MIN_DURATION_HOURS, self::FLASH_SALE_MAX_DURATION_HOURS));
        }

        $regionTaxRate = $this->getRegionTaxRate($adminSeller);

        $listing = new AuctionListing();
        $listing->setSeller($adminSeller);
        $listing->setPlayerItem($playerItem);
        $listing->setQuantity($quantity);
        $listing->setPricePerUnit($pricePerUnit);
        $listing->setListingFee(0);
        $listing->setRegionTaxRate($regionTaxRate);
        $listing->setType(AuctionType::Flash);
        $listing->setExpiresAt(new \DateTimeImmutable('+' . $durationHours . ' hours'));

        $playerItem->setInventory(null);

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        $this->logger->info('Flash sale listing created', [
            'listing_id' => $listing->getId(),
            'admin_id' => $adminSeller->getId(),
            'item' => $playerItem->getGenericItem()->getName(),
            'price_per_unit' => $pricePerUnit,
            'quantity' => $quantity,
            'duration_hours' => $durationHours,
        ]);

        return $listing;
    }

    /**
     * Annulation d'une vente flash par l'administrateur proprietaire.
     * Ignore le cooldown standard et retourne l'objet au seller admin.
     */
    public function cancelFlashSale(Player $adminSeller, AuctionListing $listing): void
    {
        if (!$listing->isFlash()) {
            throw new \InvalidArgumentException('Cette annonce n\'est pas une vente flash.');
        }

        if (!$listing->isActive()) {
            throw new \InvalidArgumentException('Cette annonce n\'est plus active.');
        }

        if ($listing->getSeller()->getId() !== $adminSeller->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez annuler que vos propres ventes flash.');
        }

        $listing->setStatus(AuctionStatus::Cancelled);
        $listing->setCancelledAt(new \DateTimeImmutable());

        $this->returnItemToSeller($listing);

        $this->entityManager->flush();

        $this->logger->info('Flash sale cancelled', [
            'listing_id' => $listing->getId(),
            'admin_id' => $adminSeller->getId(),
        ]);
    }

    /**
     * Place une mise sur une enchere. La mise est verrouillee en escrow
     * (Gils deduits du bidder) ; en cas de surenchere, la mise precedente
     * est remboursee au bidder precedent.
     */
    public function placeBid(Player $bidder, AuctionListing $listing, int $bidAmount): void
    {
        if (!$listing->isActive()) {
            throw new \InvalidArgumentException('Cette annonce n\'est plus disponible.');
        }

        if ($listing->isExpired()) {
            throw new \InvalidArgumentException('Cette enchere a expire.');
        }

        if (!$listing->isAuction()) {
            throw new \InvalidArgumentException('Cette annonce n\'est pas une enchere.');
        }

        if ($listing->getSeller()->getId() === $bidder->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez pas enchereir sur votre propre annonce.');
        }

        $currentBidder = $listing->getCurrentBidder();
        if ($currentBidder !== null && $currentBidder->getId() === $bidder->getId()) {
            throw new \InvalidArgumentException('Vous etes deja le plus offrant.');
        }

        $increment = $listing->getMinIncrement() ?? self::AUCTION_MIN_INCREMENT;
        $currentBid = $listing->getCurrentBid();
        $minAllowed = $currentBid !== null
            ? $currentBid + $increment
            : $listing->getPricePerUnit() * $listing->getQuantity();

        if ($bidAmount < $minAllowed) {
            throw new \InvalidArgumentException(sprintf('La mise doit etre d\'au moins %d Gils.', $minAllowed));
        }

        if (!$bidder->removeGils($bidAmount)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour cette mise.');
        }

        // Rembourser l'ancien plus offrant
        if ($currentBidder !== null && $currentBid !== null) {
            $currentBidder->addGils($currentBid);
        }

        $listing->setCurrentBid($bidAmount);
        $listing->setCurrentBidder($bidder);

        $this->entityManager->flush();

        $this->logger->info('Auction bid placed', [
            'listing_id' => $listing->getId(),
            'bidder_id' => $bidder->getId(),
            'bid_amount' => $bidAmount,
            'previous_bidder_id' => $currentBidder?->getId(),
        ]);

        if ($currentBidder !== null && $currentBid !== null) {
            $this->notifyOutbid($currentBidder, $listing, $currentBid, $bidAmount);
        }
    }

    /**
     * Notifie l'ancien plus offrant qu'il a ete depasse sur une enchere.
     * La notification inclut le montant rembourse et la nouvelle mise,
     * et renvoie vers la liste de l'hotel des ventes pour reagir vite.
     */
    private function notifyOutbid(Player $outbidBidder, AuctionListing $listing, int $refundedAmount, int $newBid): void
    {
        $itemName = $listing->getPlayerItem()->getGenericItem()->getName();

        $this->notificationService->notify(
            $outbidBidder,
            'auction_outbid',
            'Enchere depassee',
            sprintf(
                'Votre mise de %d Gils sur "%s" a ete depassee (nouvelle mise : %d Gils). Vos Gils ont ete rembourses.',
                $refundedAmount,
                $itemName,
                $newBid,
            ),
            icon: 'gavel',
            link: '/game/auction',
        );
    }

    /**
     * Finalise une enchere expiree avec un gagnant : l'objet part au bidder,
     * les Gils verrouilles (diminues de la taxe regionale) vont au vendeur.
     */
    public function finalizeAuction(AuctionListing $listing): AuctionTransaction
    {
        if (!$listing->isAuction()) {
            throw new \InvalidArgumentException('Cette annonce n\'est pas une enchere.');
        }

        $winner = $listing->getCurrentBidder();
        $winningBid = $listing->getCurrentBid();

        if ($winner === null || $winningBid === null) {
            throw new \InvalidArgumentException('Cette enchere n\'a pas de gagnant.');
        }

        $regionTaxAmount = (int) floor($winningBid * (float) $listing->getRegionTaxRate());
        $sellerRevenue = $winningBid - $regionTaxAmount;

        // Les Gils etaient deja verrouilles chez le bidder : on les transfere au vendeur.
        $listing->getSeller()->addGils($sellerRevenue);
        $this->transferTaxToGuildTreasury($listing, $regionTaxAmount);

        $listing->setStatus(AuctionStatus::Sold);
        $this->transferItemToBuyer($winner, $listing->getPlayerItem());

        $transaction = new AuctionTransaction();
        $transaction->setListing($listing);
        $transaction->setBuyer($winner);
        $transaction->setTotalPrice($winningBid);
        $transaction->setRegionTaxAmount($regionTaxAmount);
        $transaction->setPurchasedAt(new \DateTimeImmutable());

        $this->entityManager->persist($transaction);

        $this->logger->info('Auction finalized', [
            'listing_id' => $listing->getId(),
            'winner_id' => $winner->getId(),
            'winning_bid' => $winningBid,
            'region_tax' => $regionTaxAmount,
        ]);

        return $transaction;
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

    private function validatePriceLimits(PlayerItem $playerItem, int $pricePerUnit): void
    {
        $rarity = $playerItem->getGenericItem()->getRarityEnum();
        if ($rarity === null) {
            return;
        }

        $limits = self::PRICE_LIMITS_BY_RARITY[$rarity->value];

        if ($pricePerUnit < $limits['min']) {
            throw new \InvalidArgumentException(sprintf('Le prix minimum pour un objet %s est de %d Gils.', $rarity->label(), $limits['min']));
        }

        if ($pricePerUnit > $limits['max']) {
            throw new \InvalidArgumentException(sprintf('Le prix maximum pour un objet %s est de %s Gils.', $rarity->label(), number_format($limits['max'], 0, ',', ' ')));
        }
    }

    private function validateActiveListingsLimit(Player $seller): void
    {
        $activeCount = $this->listingRepository->countActiveBySeller($seller);

        if ($activeCount >= self::MAX_ACTIVE_LISTINGS) {
            throw new \InvalidArgumentException(sprintf('Vous avez atteint la limite de %d annonces actives.', self::MAX_ACTIVE_LISTINGS));
        }
    }

    private function validateCancelCooldown(Player $seller): void
    {
        $lastCancelledAt = $this->listingRepository->findLastCancelledAt($seller);

        if ($lastCancelledAt === null) {
            return;
        }

        $now = new \DateTimeImmutable();
        $cooldownThreshold = $now->modify('-' . self::CANCEL_COOLDOWN_MINUTES . ' minutes');

        if ($lastCancelledAt > $cooldownThreshold) {
            $cooldownEnd = \DateTimeImmutable::createFromInterface($lastCancelledAt)->modify('+' . self::CANCEL_COOLDOWN_MINUTES . ' minutes');
            $remaining = $now->diff($cooldownEnd);
            $minutes = $remaining->i;
            $seconds = $remaining->s;

            throw new \InvalidArgumentException(sprintf('Vous devez attendre %d min %02d s apres avoir annule une annonce avant d\'en creer une nouvelle.', $minutes, $seconds));
        }
    }

    private function transferTaxToGuildTreasury(AuctionListing $listing, int $taxAmount): void
    {
        if ($taxAmount <= 0) {
            return;
        }

        $map = $listing->getSeller()->getMap();
        if ($map === null) {
            return;
        }

        $region = $map->getRegion();
        if ($region === null) {
            return;
        }

        $guild = $this->townControlManager->getControllingGuild($region);
        if ($guild === null) {
            return;
        }

        $guild->addGilsTreasury($taxAmount);

        $this->logger->info('Tax transferred to guild treasury', [
            'region' => $region->getSlug(),
            'guild' => $guild->getName(),
            'amount' => $taxAmount,
        ]);
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
