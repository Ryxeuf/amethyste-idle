<?php

namespace App\GameEngine\Auction;

use App\Entity\App\AuctionListing;
use App\Entity\App\AuctionTransaction;
use App\Entity\App\Guild;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use App\Enum\AuctionStatus;
use App\Enum\AuctionType;
use App\Event\Game\AuctionSaleEvent;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\RegionBonusProvider;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Notification\NotificationService;
use App\GameEngine\Region\PlayerRegionResolver;
use App\Repository\AuctionListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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
        private readonly PlayerRegionResolver $regionResolver,
        private readonly GuildManager $guildManager,
        private readonly AuctionAntiExploit $antiExploit,
        private readonly GameMasterPolicy $gameMasterPolicy,
        private readonly EventDispatcherInterface $eventDispatcher,
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

        // ECO-01 : garde-fou cote service. Le formulaire de vente filtre deja les
        // objets liables, mais rien n'empechait une requete forgee de mettre en
        // vente un objet lie — l'UI n'est pas une regle metier.
        if (!$playerItem->isExchangeable()) {
            throw new \InvalidArgumentException('Cet objet est lie a son proprietaire et ne peut pas etre mis en vente.');
        }

        // FAC-07 : le HV refuse toute contrefacon — identifiee ou non. La
        // borne absolue du canon : un joueur ne trompe jamais un autre joueur.
        if ($playerItem->isCounterfeit()) {
            throw new \InvalidArgumentException('L\'expertise de l\'hotel des ventes refuse cet objet : c\'est une contrefacon.');
        }

        $this->gameMasterPolicy->assertMayTrade($seller);
        $this->assertNotSuspended($seller);
        $this->validatePriceLimits($playerItem, $pricePerUnit);
        $this->validateActiveListingsLimit($seller);
        $this->validateCancelCooldown($seller);

        $totalPrice = $pricePerUnit * $quantity;
        $listingFee = (int) ceil($totalPrice * self::LISTING_FEE_RATE);

        if (!$seller->removeGils($listingFee)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour payer les frais de mise en vente.');
        }

        $region = $this->resolveMarketRegion($seller);
        $regionTaxRate = $this->getRegionTaxRate($region);

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($playerItem);
        $listing->setQuantity($quantity);
        $listing->setPricePerUnit($pricePerUnit);
        $listing->setListingFee($listingFee);
        $listing->setRegionTaxRate($regionTaxRate);
        $listing->setRegion($region);
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

        $this->assertSameMarket($buyer, $listing);
        $this->assertTradeAllowed($buyer, $listing);

        $totalPrice = $listing->getTotalPrice();
        $ruler = $this->resolveRuler($listing);
        $settlement = $this->settle($listing, $buyer, $totalPrice, $ruler);

        if (!$buyer->removeGils($settlement->buyerCharge)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour cet achat.');
        }
        $listing->getSeller()->addGils($settlement->sellerRevenue);

        $this->applyTax($listing, $settlement, $ruler);

        $listing->setStatus(AuctionStatus::Sold);

        $this->transferItemToBuyer($buyer, $listing->getPlayerItem());

        $transaction = new AuctionTransaction();
        $transaction->setListing($listing);
        $transaction->setBuyer($buyer);
        $transaction->setTotalPrice($totalPrice);
        $transaction->setRegionTaxAmount($settlement->taxAmount);
        $transaction->setMemberRebateAmount($settlement->memberRebate);
        $transaction->setPurchasedAt(new \DateTimeImmutable());

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->logger->info('Auction listing purchased', [
            'listing_id' => $listing->getId(),
            'buyer_id' => $buyer->getId(),
            'seller_id' => $listing->getSeller()->getId(),
            'total_price' => $totalPrice,
            'region_tax' => $settlement->taxAmount,
            'member_rebate' => $settlement->memberRebate,
        ]);

        $this->eventDispatcher->dispatch(new AuctionSaleEvent($listing, $transaction), AuctionSaleEvent::NAME);

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
        $expired = $this->listingRepository->findExpirable(new \DateTimeImmutable());

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

        // FAC-07 : meme refus que la vente directe — les deux entrees du HV
        // sont verrouillees, pas une seule.
        if ($playerItem->isCounterfeit()) {
            throw new \InvalidArgumentException('L\'expertise de l\'hotel des ventes refuse cet objet : c\'est une contrefacon.');
        }

        $this->gameMasterPolicy->assertMayTrade($seller);
        $this->assertNotSuspended($seller);
        $this->validatePriceLimits($playerItem, $startingPrice);
        $this->validateActiveListingsLimit($seller);
        $this->validateCancelCooldown($seller);

        $totalPrice = $startingPrice * $quantity;
        $listingFee = (int) ceil($totalPrice * self::LISTING_FEE_RATE);

        if (!$seller->removeGils($listingFee)) {
            throw new \InvalidArgumentException('Fonds insuffisants pour payer les frais de mise en vente.');
        }

        $region = $this->resolveMarketRegion($seller);
        $regionTaxRate = $this->getRegionTaxRate($region);

        $listing = new AuctionListing();
        $listing->setSeller($seller);
        $listing->setPlayerItem($playerItem);
        $listing->setQuantity($quantity);
        $listing->setPricePerUnit($startingPrice);
        $listing->setListingFee($listingFee);
        $listing->setRegionTaxRate($regionTaxRate);
        $listing->setRegion($region);
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

        // ECO-03 : la vente flash porte bien une region (celle de l'admin, pour la
        // taxe) mais reste **visible partout** — c'est un canal systeme, pas un
        // marche joueur. La segmentation ne s'applique qu'aux annonces de joueurs.
        $region = $this->resolveMarketRegion($adminSeller);
        $regionTaxRate = $this->getRegionTaxRate($region);

        $listing = new AuctionListing();
        $listing->setSeller($adminSeller);
        $listing->setPlayerItem($playerItem);
        $listing->setQuantity($quantity);
        $listing->setPricePerUnit($pricePerUnit);
        $listing->setListingFee(0);
        $listing->setRegionTaxRate($regionTaxRate);
        $listing->setRegion($region);
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

        $this->assertSameMarket($bidder, $listing);
        // Le controle porte sur la mise et non sur la finalisation : celle-ci est
        // declenchee par l'expiration, pas par un joueur — y refuser l'operation
        // laisserait l'objet et les Gils bloques indefiniment.
        $this->assertTradeAllowed($bidder, $listing);

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

        $ruler = $this->resolveRuler($listing);
        $settlement = $this->settle($listing, $winner, $winningBid, $ruler);

        // Les Gils etaient deja verrouilles chez le bidder : on les transfere au vendeur.
        $listing->getSeller()->addGils($settlement->sellerRevenue);
        // La ristourne membre ne pouvait pas etre deduite au moment de la mise —
        // l'issue de l'enchere n'etait pas connue. Elle est rendue au gagnant.
        if ($settlement->memberRebate > 0) {
            $winner->addGils($settlement->memberRebate);
        }
        $this->applyTax($listing, $settlement, $ruler);

        $listing->setStatus(AuctionStatus::Sold);
        $this->transferItemToBuyer($winner, $listing->getPlayerItem());

        $transaction = new AuctionTransaction();
        $transaction->setListing($listing);
        $transaction->setBuyer($winner);
        $transaction->setTotalPrice($winningBid);
        $transaction->setRegionTaxAmount($settlement->taxAmount);
        $transaction->setMemberRebateAmount($settlement->memberRebate);
        $transaction->setPurchasedAt(new \DateTimeImmutable());

        $this->entityManager->persist($transaction);

        $this->logger->info('Auction finalized', [
            'listing_id' => $listing->getId(),
            'winner_id' => $winner->getId(),
            'winning_bid' => $winningBid,
            'region_tax' => $settlement->taxAmount,
            'member_rebate' => $settlement->memberRebate,
        ]);

        $this->eventDispatcher->dispatch(new AuctionSaleEvent($listing, $transaction), AuctionSaleEvent::NAME);

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

    /**
     * Refuse un echange entre deux personnages d'un meme compte, ou au-dela du
     * plafond d'echanges entre un meme couple de joueurs (ECO-16).
     *
     * Les ventes flash echappent a la regle : le vendeur y est l'administration,
     * pas un joueur.
     */
    private function assertTradeAllowed(Player $buyer, AuctionListing $listing): void
    {
        // Le MJ regarde la salle des ventes, il n'y engage rien : ni achat, ni
        // enchere. Le controle est ici, donc sur les deux chemins a la fois.
        $this->gameMasterPolicy->assertMayTrade($buyer);

        // ECO-16b : la suspension vaut aussi face au canal systeme. Elle ferme
        // le marche, pas seulement le commerce entre joueurs.
        $this->assertNotSuspended($buyer);

        // Canal systeme : le vendeur est l'administration, pas un joueur.
        if ($listing->isFlash()) {
            return;
        }

        $seller = $listing->getSeller();

        if ($this->antiExploit->isSameAccount($buyer, $seller)) {
            throw new \InvalidArgumentException('Vous ne pouvez pas commercer avec un autre de vos personnages.');
        }

        if ($this->antiExploit->isPairCapReached($buyer, $seller)) {
            throw new \InvalidArgumentException(sprintf('Vous avez atteint la limite d\'echanges avec ce joueur (%d sur %d heures). Reessayez plus tard.', $this->antiExploit->getPairTransactionCap(), $this->antiExploit->getPairWindowHours()));
        }
    }

    /**
     * Un joueur suspendu ne peut ni deposer, ni acheter, ni encherir (ECO-16b).
     */
    private function assertNotSuspended(Player $player): void
    {
        if (!$player->isTradeSuspended()) {
            return;
        }

        throw new \InvalidArgumentException(sprintf("Votre acces au marche est suspendu jusqu'au %s.", $player->getTradeSuspendedUntil()?->format('d/m/Y H:i')));
    }

    /**
     * Annulation par la moderation (ECO-16b).
     *
     * Se distingue de `cancelListing` sur deux points : aucun controle de
     * propriete, et **les encheres en cours ne l'empechent pas** — une annonce
     * frauduleuse doit pouvoir disparaitre meme si quelqu'un a mise dessus. Le
     * dernier encherisseur est rembourse, sinon la moderation lui volerait ses
     * Gils au passage.
     */
    public function cancelListingAsModerator(AuctionListing $listing, string $reason): void
    {
        if (!$listing->isActive()) {
            throw new \InvalidArgumentException('Cette annonce n\'est plus active.');
        }

        $bidder = $listing->getCurrentBidder();
        $bid = $listing->getCurrentBid();
        if (null !== $bidder && null !== $bid) {
            $bidder->addGils($bid);
            $listing->setCurrentBidder(null);
            $listing->setCurrentBid(null);
        }

        $listing->setStatus(AuctionStatus::Cancelled);
        $listing->setCancelledAt(new \DateTimeImmutable());
        $this->returnItemToSeller($listing);

        $this->entityManager->flush();

        $this->logger->warning('Auction listing cancelled by moderation', [
            'listing_id' => $listing->getId(),
            'seller_id' => $listing->getSeller()->getId(),
            'refunded_bidder_id' => $bidder?->getId(),
            'refunded_amount' => $bidder !== null ? $bid : 0,
            'reason' => $reason,
        ]);
    }

    /**
     * Taux de ristourne dont beneficie ce joueur sur ce marche (ECO-04).
     *
     * Expose pour l'affichage : un avantage que le joueur ne voit pas ne
     * l'incite a rien. Retourne 0.0 hors region, sans guilde controlante, ou
     * quand le joueur n'en est pas membre.
     */
    public function getMemberRebateRate(Player $player, ?Region $region): float
    {
        if (null === $region) {
            return 0.0;
        }

        $guild = $this->townControlManager->getControllingGuild($region);
        if (null === $guild) {
            return 0.0;
        }

        $playerGuild = $this->guildManager->getPlayerGuild($player);
        if (null === $playerGuild || $playerGuild->getId() !== $guild->getId()) {
            return 0.0;
        }

        return RegionBonusProvider::MEMBER_DISCOUNT;
    }

    /**
     * Calcule la repartition des Gils d'une vente (ECO-04).
     *
     * La taxe revient au marche **ou l'annonce a ete deposee** (ECO-03) : elle se
     * lisait auparavant sur la carte courante du vendeur, qui pouvait avoir change
     * entre le depot et l'achat.
     */
    private function settle(AuctionListing $listing, Player $buyer, int $totalPrice, ?Guild $ruler): AuctionSettlement
    {
        $buyerGuild = null !== $ruler ? $this->guildManager->getPlayerGuild($buyer) : null;
        $buyerIsMember = null !== $ruler && null !== $buyerGuild && $buyerGuild->getId() === $ruler->getId();

        return AuctionSettlement::compute(
            $totalPrice,
            (float) $listing->getRegionTaxRate(),
            null !== $ruler,
            $buyerIsMember,
            RegionBonusProvider::MEMBER_DISCOUNT,
        );
    }

    /**
     * Guilde controlant le marche de l'annonce, resolue **une seule fois** par
     * vente : le calcul de la repartition et le versement en ont tous deux
     * besoin, et deux lectures ouvriraient la porte a une incoherence si le
     * controle basculait entre les deux.
     */
    private function resolveRuler(AuctionListing $listing): ?Guild
    {
        $region = $listing->getRegion();

        return null !== $region ? $this->townControlManager->getControllingGuild($region) : null;
    }

    /**
     * Verse la part de taxe revenant a la guilde controlante — ou constate la
     * destruction des Gils quand la region n'a pas de maitre.
     *
     * Le gold sink n'est pas un effet de bord : les Gils ont ete retires a
     * l'acheteur et ne sont pas alles au vendeur. Sans guilde pour les recevoir,
     * ils **sortent du jeu**. On le journalise explicitement, sans quoi une
     * refonte pourrait les rendre au vendeur en croyant corriger une fuite.
     */
    private function applyTax(AuctionListing $listing, AuctionSettlement $settlement, ?Guild $ruler): void
    {
        $region = $listing->getRegion();

        if ($settlement->burnedAmount > 0) {
            $this->logger->info('Auction tax burned (region has no ruling guild)', [
                'region' => $region?->getSlug(),
                'amount' => $settlement->burnedAmount,
            ]);

            return;
        }

        if (null === $ruler || $settlement->treasuryAmount <= 0) {
            return;
        }

        $ruler->addGilsTreasury($settlement->treasuryAmount);

        $this->logger->info('Tax transferred to guild treasury', [
            'region' => $region?->getSlug(),
            'guild' => $ruler->getName(),
            'amount' => $settlement->treasuryAmount,
            'member_rebate' => $settlement->memberRebate,
        ]);
    }

    private function getRegionTaxRate(?Region $region): string
    {
        return $region?->getTaxRate() ?? '0.0000';
    }

    /**
     * Marche regional du vendeur au moment du depot (ECO-03).
     *
     * Un joueur hors region (personnage pas encore rattache a une zone du
     * graphe) depose sur le marche « sans region », visible des seuls joueurs
     * dans le meme cas : le depot n'est jamais refuse, mais il n'atterrit pas
     * non plus dans un marche auquel le vendeur n'appartient pas.
     */
    private function resolveMarketRegion(Player $seller): ?Region
    {
        return $this->regionResolver->resolve($seller);
    }

    /**
     * Refuse une operation sur une annonce d'un autre marche (ECO-03).
     *
     * Le filtre de l'ecran n'est pas une regle metier : sans ce garde-fou, une
     * requete forgee avec l'identifiant d'une annonce distante contournait
     * entierement la segmentation.
     */
    private function assertSameMarket(Player $player, AuctionListing $listing): void
    {
        // Les ventes flash sont un canal systeme, volontairement global.
        if ($listing->isFlash()) {
            return;
        }

        if (!$this->regionResolver->isSameMarket($this->regionResolver->resolve($player), $listing->getRegion())) {
            throw new \InvalidArgumentException('Cette annonce appartient au marche d\'une autre region : rendez-vous sur place pour y acceder.');
        }
    }
}
