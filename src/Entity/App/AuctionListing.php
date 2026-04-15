<?php

namespace App\Entity\App;

use App\Enum\AuctionStatus;
use App\Enum\AuctionType;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: \App\Repository\AuctionListingRepository::class)]
#[ORM\Table(name: 'auction_listing')]
#[ORM\Index(name: 'idx_auction_listing_status', columns: ['status'])]
#[ORM\Index(name: 'idx_auction_listing_seller', columns: ['seller_id'])]
#[ORM\Index(name: 'idx_auction_listing_expires', columns: ['status', 'expires_at'])]
class AuctionListing
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'seller_id', referencedColumnName: 'id', nullable: false)]
    private Player $seller;

    #[ORM\ManyToOne(targetEntity: PlayerItem::class)]
    #[ORM\JoinColumn(name: 'player_item_id', referencedColumnName: 'id', nullable: false)]
    private PlayerItem $playerItem;

    #[ORM\Column(name: 'quantity', type: 'integer')]
    private int $quantity = 1;

    #[ORM\Column(name: 'price_per_unit', type: 'integer')]
    private int $pricePerUnit;

    #[ORM\Column(name: 'listing_fee', type: 'integer')]
    private int $listingFee;

    #[ORM\Column(name: 'status', type: 'string', length: 20, enumType: AuctionStatus::class)]
    private AuctionStatus $status = AuctionStatus::Active;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    #[ORM\Column(name: 'region_tax_rate', type: 'decimal', precision: 5, scale: 4, options: ['default' => '0.0000'])]
    private string $regionTaxRate = '0.0000';

    #[ORM\Column(name: 'cancelled_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(name: 'type', type: 'string', length: 20, enumType: AuctionType::class, options: ['default' => 'fixed'])]
    private AuctionType $type = AuctionType::Fixed;

    #[ORM\Column(name: 'min_increment', type: 'integer', nullable: true)]
    private ?int $minIncrement = null;

    #[ORM\Column(name: 'current_bid', type: 'integer', nullable: true)]
    private ?int $currentBid = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'current_bidder_id', referencedColumnName: 'id', nullable: true)]
    private ?Player $currentBidder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeller(): Player
    {
        return $this->seller;
    }

    public function setSeller(Player $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function getPlayerItem(): PlayerItem
    {
        return $this->playerItem;
    }

    public function setPlayerItem(PlayerItem $playerItem): self
    {
        $this->playerItem = $playerItem;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPricePerUnit(): int
    {
        return $this->pricePerUnit;
    }

    public function setPricePerUnit(int $pricePerUnit): self
    {
        $this->pricePerUnit = $pricePerUnit;

        return $this;
    }

    public function getListingFee(): int
    {
        return $this->listingFee;
    }

    public function setListingFee(int $listingFee): self
    {
        $this->listingFee = $listingFee;

        return $this;
    }

    public function getStatus(): AuctionStatus
    {
        return $this->status;
    }

    public function setStatus(AuctionStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getRegionTaxRate(): string
    {
        return $this->regionTaxRate;
    }

    public function setRegionTaxRate(string $regionTaxRate): self
    {
        $this->regionTaxRate = $regionTaxRate;

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getTotalPrice(): int
    {
        return $this->pricePerUnit * $this->quantity;
    }

    public function isActive(): bool
    {
        return $this->status === AuctionStatus::Active;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function getType(): AuctionType
    {
        return $this->type;
    }

    public function setType(AuctionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMinIncrement(): ?int
    {
        return $this->minIncrement;
    }

    public function setMinIncrement(?int $minIncrement): self
    {
        $this->minIncrement = $minIncrement;

        return $this;
    }

    public function getCurrentBid(): ?int
    {
        return $this->currentBid;
    }

    public function setCurrentBid(?int $currentBid): self
    {
        $this->currentBid = $currentBid;

        return $this;
    }

    public function getCurrentBidder(): ?Player
    {
        return $this->currentBidder;
    }

    public function setCurrentBidder(?Player $currentBidder): self
    {
        $this->currentBidder = $currentBidder;

        return $this;
    }

    public function isAuction(): bool
    {
        return $this->type === AuctionType::Auction;
    }

    public function isFlash(): bool
    {
        return $this->type === AuctionType::Flash;
    }

    /**
     * Pour les encheres: prix courant a payer = currentBid s'il existe, sinon startingPrice (= pricePerUnit).
     * Pour les prix fixes: pricePerUnit.
     */
    public function getCurrentPrice(): int
    {
        if ($this->type === AuctionType::Auction && $this->currentBid !== null) {
            return $this->currentBid;
        }

        return $this->pricePerUnit;
    }
}
