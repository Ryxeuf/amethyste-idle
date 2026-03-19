<?php

namespace App\Entity\App;

use App\Entity\Game\Item;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'auction_listing')]
#[ORM\Entity()]
#[ORM\Index(columns: ['status'], name: 'idx_auction_status')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_auction_expires')]
#[ORM\Index(columns: ['seller_id'], name: 'idx_auction_seller')]
class AuctionListing
{
    use TimestampableEntity;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SOLD = 'sold';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const DURATION_24H = 24;
    public const DURATION_48H = 48;
    public const DURATION_72H = 72;

    public const TAX_RATE = 0.05;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'seller_id', referencedColumnName: 'id', nullable: false)]
    private Player $seller;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'buyer_id', referencedColumnName: 'id', nullable: true)]
    private ?Player $buyer = null;

    #[ORM\ManyToOne(targetEntity: PlayerItem::class)]
    #[ORM\JoinColumn(name: 'player_item_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PlayerItem $playerItem = null;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false)]
    private Item $item;

    #[ORM\Column(name: 'price', type: 'integer')]
    private int $price;

    #[ORM\Column(name: 'status', type: 'string', length: 20, options: ['default' => 'active'])]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(name: 'duration_hours', type: 'integer')]
    private int $durationHours;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    #[ORM\Column(name: 'sold_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $soldAt = null;

    #[ORM\Column(name: 'tax_amount', type: 'integer', options: ['default' => 0])]
    private int $taxAmount = 0;

    #[ORM\Column(name: 'quantity', type: 'integer', options: ['default' => 1])]
    private int $quantity = 1;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSeller(): Player
    {
        return $this->seller;
    }

    public function setSeller(Player $seller): void
    {
        $this->seller = $seller;
    }

    public function getBuyer(): ?Player
    {
        return $this->buyer;
    }

    public function setBuyer(?Player $buyer): void
    {
        $this->buyer = $buyer;
    }

    public function getPlayerItem(): ?PlayerItem
    {
        return $this->playerItem;
    }

    public function setPlayerItem(?PlayerItem $playerItem): void
    {
        $this->playerItem = $playerItem;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): void
    {
        $this->item = $item;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = max(1, $price);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getDurationHours(): int
    {
        return $this->durationHours;
    }

    public function setDurationHours(int $durationHours): void
    {
        $this->durationHours = $durationHours;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getSoldAt(): ?\DateTimeInterface
    {
        return $this->soldAt;
    }

    public function setSoldAt(?\DateTimeInterface $soldAt): void
    {
        $this->soldAt = $soldAt;
    }

    public function getTaxAmount(): int
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(int $taxAmount): void
    {
        $this->taxAmount = $taxAmount;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = max(1, $quantity);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function calculateTax(): int
    {
        return (int) ceil($this->price * self::TAX_RATE);
    }

    public function getSellerProceeds(): int
    {
        return $this->price - $this->taxAmount;
    }
}
