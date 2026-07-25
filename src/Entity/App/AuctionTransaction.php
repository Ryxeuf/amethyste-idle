<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\AuctionTransactionRepository::class)]
#[ORM\Table(name: 'auction_transaction')]
#[ORM\Index(name: 'idx_auction_transaction_buyer', columns: ['buyer_id'])]
#[ORM\Index(name: 'idx_auction_transaction_listing', columns: ['listing_id'])]
// ECO-16b : le journal economique interroge par date, sur toutes les fenetres.
#[ORM\Index(name: 'idx_auction_transaction_purchased_at', columns: ['purchased_at'])]
class AuctionTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AuctionListing::class)]
    #[ORM\JoinColumn(name: 'listing_id', referencedColumnName: 'id', nullable: false)]
    private AuctionListing $listing;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'buyer_id', referencedColumnName: 'id', nullable: false)]
    private Player $buyer;

    #[ORM\Column(name: 'total_price', type: 'integer')]
    private int $totalPrice;

    #[ORM\Column(name: 'region_tax_amount', type: 'integer', options: ['default' => 0])]
    private int $regionTaxAmount = 0;

    /**
     * Ristourne accordee a l'acheteur membre de la guilde controlante (ECO-04).
     *
     * Conservee sur la transaction plutot que recalculee : la taxe, le controle
     * de la region et l'appartenance de l'acheteur peuvent tous changer apres
     * coup, et la detection d'anomalies (ECO-16) a besoin du montant reellement
     * consenti, pas d'une reconstitution.
     */
    #[ORM\Column(name: 'member_rebate_amount', type: 'integer', options: ['default' => 0])]
    private int $memberRebateAmount = 0;

    #[ORM\Column(name: 'purchased_at', type: 'datetime')]
    private \DateTimeInterface $purchasedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getListing(): AuctionListing
    {
        return $this->listing;
    }

    public function setListing(AuctionListing $listing): self
    {
        $this->listing = $listing;

        return $this;
    }

    public function getBuyer(): Player
    {
        return $this->buyer;
    }

    public function setBuyer(Player $buyer): self
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getRegionTaxAmount(): int
    {
        return $this->regionTaxAmount;
    }

    public function setRegionTaxAmount(int $regionTaxAmount): self
    {
        $this->regionTaxAmount = $regionTaxAmount;

        return $this;
    }

    public function getMemberRebateAmount(): int
    {
        return $this->memberRebateAmount;
    }

    public function setMemberRebateAmount(int $memberRebateAmount): self
    {
        $this->memberRebateAmount = $memberRebateAmount;

        return $this;
    }

    /**
     * Ce que l'acheteur a reellement paye, ristourne membre deduite.
     */
    public function getAmountPaid(): int
    {
        return $this->totalPrice - $this->memberRebateAmount;
    }

    public function getPurchasedAt(): \DateTimeInterface
    {
        return $this->purchasedAt;
    }

    public function setPurchasedAt(\DateTimeInterface $purchasedAt): self
    {
        $this->purchasedAt = $purchasedAt;

        return $this;
    }
}
