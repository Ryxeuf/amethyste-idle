<?php

namespace App\Entity\App;

use App\Entity\Game\Item;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'transaction_log')]
#[ORM\Entity()]
#[ORM\Index(columns: ['type'], name: 'idx_transaction_type')]
#[ORM\Index(columns: ['created_at'], name: 'idx_transaction_date')]
class TransactionLog
{
    use TimestampableEntity;

    public const TYPE_SHOP_BUY = 'shop_buy';
    public const TYPE_SHOP_SELL = 'shop_sell';
    public const TYPE_AUCTION_LIST = 'auction_list';
    public const TYPE_AUCTION_BUY = 'auction_buy';
    public const TYPE_AUCTION_CANCEL = 'auction_cancel';
    public const TYPE_AUCTION_EXPIRE = 'auction_expire';
    public const TYPE_TRADE = 'trade';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'type', type: 'string', length: 30)]
    private string $type;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'other_player_id', referencedColumnName: 'id', nullable: true)]
    private ?Player $otherPlayer = null;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: true)]
    private ?Item $item = null;

    #[ORM\Column(name: 'quantity', type: 'integer', options: ['default' => 1])]
    private int $quantity = 1;

    #[ORM\Column(name: 'gils_amount', type: 'integer', options: ['default' => 0])]
    private int $gilsAmount = 0;

    #[ORM\Column(name: 'tax_amount', type: 'integer', options: ['default' => 0])]
    private int $taxAmount = 0;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    private string $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    public function getOtherPlayer(): ?Player
    {
        return $this->otherPlayer;
    }

    public function setOtherPlayer(?Player $otherPlayer): void
    {
        $this->otherPlayer = $otherPlayer;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): void
    {
        $this->item = $item;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getGilsAmount(): int
    {
        return $this->gilsAmount;
    }

    public function setGilsAmount(int $gilsAmount): void
    {
        $this->gilsAmount = $gilsAmount;
    }

    public function getTaxAmount(): int
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(int $taxAmount): void
    {
        $this->taxAmount = $taxAmount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
