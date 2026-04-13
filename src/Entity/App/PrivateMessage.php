<?php

namespace App\Entity\App;

use App\Repository\PrivateMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'private_message')]
#[ORM\Index(columns: ['receiver_id', 'created_at'], name: 'idx_pm_receiver_created')]
#[ORM\Index(columns: ['sender_id', 'created_at'], name: 'idx_pm_sender_created')]
#[ORM\Entity(repositoryClass: PrivateMessageRepository::class)]
class PrivateMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'id', nullable: false)]
    private Player $sender;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'receiver_id', referencedColumnName: 'id', nullable: false)]
    private Player $receiver;

    #[ORM\Column(name: 'subject', type: 'string', length: 100)]
    private string $subject;

    #[ORM\Column(name: 'body', type: 'text')]
    private string $body;

    #[ORM\Column(name: 'read_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSender(): Player
    {
        return $this->sender;
    }

    public function setSender(Player $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiver(): Player
    {
        return $this->receiver;
    }

    public function setReceiver(Player $receiver): self
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function markAsRead(): self
    {
        if ($this->readAt === null) {
            $this->readAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
