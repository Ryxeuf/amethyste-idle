<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'chat_message')]
#[ORM\Index(columns: ['channel'], name: 'idx_chat_channel')]
#[ORM\Index(columns: ['created_at'], name: 'idx_chat_created_at')]
#[ORM\Index(columns: ['channel', 'created_at'], name: 'idx_chat_channel_created')]
#[ORM\Index(columns: ['guild_id', 'created_at'], name: 'idx_chat_guild_created')]
#[ORM\Entity()]
class ChatMessage
{
    use TimestampableEntity;

    public const CHANNEL_GLOBAL = 'global';
    public const CHANNEL_MAP = 'map';
    public const CHANNEL_PRIVATE = 'private';
    public const CHANNEL_GUILD = 'guild';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'channel', type: 'string', length: 20)]
    private string $channel;

    #[ORM\Column(name: 'content', type: 'text')]
    private string $content;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'id', nullable: false)]
    private Player $sender;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'recipient_id', referencedColumnName: 'id', nullable: true)]
    private ?Player $recipient = null;

    #[ORM\ManyToOne(targetEntity: Map::class)]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id', nullable: true)]
    private ?Map $map = null;

    #[ORM\ManyToOne(targetEntity: Guild::class)]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Guild $guild = null;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private bool $isDeleted = false;

    #[ORM\Column(name: 'deleted_by', type: 'string', length: 255, nullable: true)]
    private ?string $deletedBy = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
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

    public function getRecipient(): ?Player
    {
        return $this->recipient;
    }

    public function setRecipient(?Player $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setMap(?Map $map): self
    {
        $this->map = $map;

        return $this;
    }

    public function getGuild(): ?Guild
    {
        return $this->guild;
    }

    public function setGuild(?Guild $guild): self
    {
        $this->guild = $guild;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getDeletedBy(): ?string
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?string $deletedBy): self
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }
}
