<?php

namespace App\Entity\App;

use App\Repository\PlayerNotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PlayerNotificationRepository::class)]
#[ORM\Table(name: 'player_notification')]
#[ORM\Index(columns: ['player_id', 'read_at'], name: 'idx_player_notification_unread')]
class PlayerNotification
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $comment;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $admin_comment = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(name: 'read_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $readAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getAdminComment(): ?string
    {
        return $this->admin_comment;
    }

    public function setAdminComment(?string $adminComment): self
    {
        $this->admin_comment = $adminComment;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getReadAt(): ?\DateTimeInterface
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeInterface $readAt): self
    {
        $this->readAt = $readAt;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    public function markAsRead(): self
    {
        if ($this->readAt === null) {
            $this->readAt = new \DateTime();
        }

        return $this;
    }
}
