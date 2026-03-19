<?php

namespace App\Entity\App;

use App\Entity\Game\Item;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'shop_stock')]
#[ORM\Entity()]
#[ORM\UniqueConstraint(name: 'uniq_shop_stock_pnj_item', columns: ['pnj_id', 'item_id'])]
class ShopStock
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Pnj::class)]
    #[ORM\JoinColumn(name: 'pnj_id', referencedColumnName: 'id', nullable: false)]
    private Pnj $pnj;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false)]
    private Item $item;

    #[ORM\Column(name: 'max_stock', type: 'integer', nullable: true)]
    private ?int $maxStock = null;

    #[ORM\Column(name: 'current_stock', type: 'integer', nullable: true)]
    private ?int $currentStock = null;

    #[ORM\Column(name: 'restock_interval_minutes', type: 'integer', nullable: true)]
    private ?int $restockIntervalMinutes = null;

    #[ORM\Column(name: 'last_restock_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastRestockAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPnj(): Pnj
    {
        return $this->pnj;
    }

    public function setPnj(Pnj $pnj): void
    {
        $this->pnj = $pnj;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): void
    {
        $this->item = $item;
    }

    public function getMaxStock(): ?int
    {
        return $this->maxStock;
    }

    public function setMaxStock(?int $maxStock): void
    {
        $this->maxStock = $maxStock;
    }

    public function getCurrentStock(): ?int
    {
        return $this->currentStock;
    }

    public function setCurrentStock(?int $currentStock): void
    {
        $this->currentStock = $currentStock;
    }

    public function getRestockIntervalMinutes(): ?int
    {
        return $this->restockIntervalMinutes;
    }

    public function setRestockIntervalMinutes(?int $restockIntervalMinutes): void
    {
        $this->restockIntervalMinutes = $restockIntervalMinutes;
    }

    public function getLastRestockAt(): ?\DateTimeInterface
    {
        return $this->lastRestockAt;
    }

    public function setLastRestockAt(?\DateTimeInterface $lastRestockAt): void
    {
        $this->lastRestockAt = $lastRestockAt;
    }

    public function hasLimitedStock(): bool
    {
        return $this->maxStock !== null;
    }

    public function isInStock(): bool
    {
        if (!$this->hasLimitedStock()) {
            return true;
        }

        return $this->currentStock > 0;
    }

    public function decrementStock(): bool
    {
        if (!$this->hasLimitedStock()) {
            return true;
        }

        if ($this->currentStock <= 0) {
            return false;
        }

        --$this->currentStock;

        return true;
    }

    public function needsRestock(): bool
    {
        if (!$this->hasLimitedStock() || $this->restockIntervalMinutes === null) {
            return false;
        }

        if ($this->currentStock >= $this->maxStock) {
            return false;
        }

        if ($this->lastRestockAt === null) {
            return true;
        }

        $now = new \DateTime();
        $diff = $now->getTimestamp() - $this->lastRestockAt->getTimestamp();

        return $diff >= ($this->restockIntervalMinutes * 60);
    }

    public function restock(): void
    {
        $this->currentStock = $this->maxStock;
        $this->lastRestockAt = new \DateTime();
    }
}
