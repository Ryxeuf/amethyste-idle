<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'trade_offer')]
#[ORM\Entity()]
#[ORM\Index(columns: ['status'], name: 'idx_trade_status')]
class TradeOffer
{
    use TimestampableEntity;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'initiator_id', referencedColumnName: 'id', nullable: false)]
    private Player $initiator;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'receiver_id', referencedColumnName: 'id', nullable: false)]
    private Player $receiver;

    #[ORM\Column(name: 'initiator_items', type: 'json')]
    private array $initiatorItems = [];

    #[ORM\Column(name: 'receiver_items', type: 'json')]
    private array $receiverItems = [];

    #[ORM\Column(name: 'initiator_gils', type: 'integer', options: ['default' => 0])]
    private int $initiatorGils = 0;

    #[ORM\Column(name: 'receiver_gils', type: 'integer', options: ['default' => 0])]
    private int $receiverGils = 0;

    #[ORM\Column(name: 'status', type: 'string', length: 20, options: ['default' => 'pending'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'initiator_confirmed', type: 'boolean', options: ['default' => false])]
    private bool $initiatorConfirmed = false;

    #[ORM\Column(name: 'receiver_confirmed', type: 'boolean', options: ['default' => false])]
    private bool $receiverConfirmed = false;

    #[ORM\Column(name: 'completed_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getInitiator(): Player
    {
        return $this->initiator;
    }

    public function setInitiator(Player $initiator): void
    {
        $this->initiator = $initiator;
    }

    public function getReceiver(): Player
    {
        return $this->receiver;
    }

    public function setReceiver(Player $receiver): void
    {
        $this->receiver = $receiver;
    }

    public function getInitiatorItems(): array
    {
        return $this->initiatorItems;
    }

    public function setInitiatorItems(array $initiatorItems): void
    {
        $this->initiatorItems = $initiatorItems;
    }

    public function getReceiverItems(): array
    {
        return $this->receiverItems;
    }

    public function setReceiverItems(array $receiverItems): void
    {
        $this->receiverItems = $receiverItems;
    }

    public function getInitiatorGils(): int
    {
        return $this->initiatorGils;
    }

    public function setInitiatorGils(int $initiatorGils): void
    {
        $this->initiatorGils = max(0, $initiatorGils);
    }

    public function getReceiverGils(): int
    {
        return $this->receiverGils;
    }

    public function setReceiverGils(int $receiverGils): void
    {
        $this->receiverGils = max(0, $receiverGils);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isInitiatorConfirmed(): bool
    {
        return $this->initiatorConfirmed;
    }

    public function setInitiatorConfirmed(bool $initiatorConfirmed): void
    {
        $this->initiatorConfirmed = $initiatorConfirmed;
    }

    public function isReceiverConfirmed(): bool
    {
        return $this->receiverConfirmed;
    }

    public function setReceiverConfirmed(bool $receiverConfirmed): void
    {
        $this->receiverConfirmed = $receiverConfirmed;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function areBothConfirmed(): bool
    {
        return $this->initiatorConfirmed && $this->receiverConfirmed;
    }
}
