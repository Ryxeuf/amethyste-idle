<?php

namespace App\Entity\App;

use App\Repository\ShopSaleLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal des ventes d'une echoppe (ECO-10).
 *
 * L'echoppe vend en l'absence de son proprietaire : sans journal, il
 * retrouverait une caisse pleine sans savoir ce qui est parti, ni a quel prix.
 * Le journal est ce qui rend la vente asynchrone **lisible**.
 *
 * Les libelles sont **copies** et non references : un objet vendu sort du jeu
 * du cote du vendeur, et le journal doit rester lisible quand la ligne
 * d'inventaire correspondante n'existe plus.
 */
#[ORM\Entity(repositoryClass: ShopSaleLogRepository::class)]
#[ORM\Table(name: 'shop_sale_log')]
#[ORM\Index(name: 'idx_shop_sale_log_shop', columns: ['shop_id', 'sold_at'])]
class ShopSaleLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlayerShop::class)]
    #[ORM\JoinColumn(name: 'shop_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PlayerShop $shop;

    /**
     * L'acheteur peut disparaitre ; la vente, elle, a eu lieu.
     */
    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'buyer_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Player $buyer = null;

    #[ORM\Column(name: 'buyer_name', type: 'string', length: 100)]
    private string $buyerName;

    #[ORM\Column(name: 'item_name', type: 'string', length: 150)]
    private string $itemName;

    #[ORM\Column(name: 'quantity', type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'unit_price', type: 'integer')]
    private int $unitPrice;

    /**
     * Part prelevee par la cite (ECO-04) : elle explique l'ecart entre ce que
     * l'acheteur paie et ce que la caisse recoit.
     */
    #[ORM\Column(name: 'tax_gils', type: 'integer', options: ['default' => 0])]
    private int $taxGils = 0;

    #[ORM\Column(name: 'net_gils', type: 'integer')]
    private int $netGils;

    #[ORM\Column(name: 'sold_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $soldAt;

    public function __construct(
        PlayerShop $shop,
        ?Player $buyer,
        string $buyerName,
        string $itemName,
        int $quantity,
        int $unitPrice,
        int $taxGils,
        int $netGils,
    ) {
        $this->shop = $shop;
        $this->buyer = $buyer;
        $this->buyerName = $buyerName;
        $this->itemName = $itemName;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->taxGils = $taxGils;
        $this->netGils = $netGils;
        $this->soldAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShop(): PlayerShop
    {
        return $this->shop;
    }

    public function getBuyer(): ?Player
    {
        return $this->buyer;
    }

    public function getBuyerName(): string
    {
        return $this->buyerName;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function getTaxGils(): int
    {
        return $this->taxGils;
    }

    public function getNetGils(): int
    {
        return $this->netGils;
    }

    public function getGrossGils(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    public function getSoldAt(): \DateTimeImmutable
    {
        return $this->soldAt;
    }
}
