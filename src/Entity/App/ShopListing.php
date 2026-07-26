<?php

namespace App\Entity\App;

use App\Repository\ShopListingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lot expose dans une echoppe (ECO-10).
 *
 * L'objet est mis en **escrow** : son `PlayerItem` quitte l'inventaire du
 * vendeur des le depot, exactement comme a l'hotel des ventes. Sans cela, un
 * artisan pourrait exposer un objet, le consommer, et le vendre quand meme —
 * l'echoppe vendant en son absence, il n'y a personne pour arbitrer.
 */
#[ORM\Entity(repositoryClass: ShopListingRepository::class)]
#[ORM\Table(name: 'shop_listing')]
#[ORM\Index(name: 'idx_shop_listing_shop', columns: ['shop_id'])]
class ShopListing
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlayerShop::class)]
    #[ORM\JoinColumn(name: 'shop_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PlayerShop $shop;

    #[ORM\ManyToOne(targetEntity: PlayerItem::class)]
    #[ORM\JoinColumn(name: 'player_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PlayerItem $playerItem;

    #[ORM\Column(name: 'quantity', type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'unit_price', type: 'integer')]
    private int $unitPrice;

    #[ORM\Column(name: 'listed_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $listedAt;

    public function __construct(PlayerShop $shop, PlayerItem $playerItem, int $quantity, int $unitPrice)
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantite doit etre superieure a 0.');
        }
        if ($unitPrice < 1) {
            throw new \InvalidArgumentException('Le prix doit etre superieur a 0.');
        }

        $this->shop = $shop;
        $this->playerItem = $playerItem;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->listedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShop(): PlayerShop
    {
        return $this->shop;
    }

    public function getPlayerItem(): PlayerItem
    {
        return $this->playerItem;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): self
    {
        if ($unitPrice < 1) {
            throw new \InvalidArgumentException('Le prix doit etre superieur a 0.');
        }

        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getTotalPrice(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    public function getListedAt(): \DateTimeImmutable
    {
        return $this->listedAt;
    }
}
