<?php

namespace App\Entity\App;

use App\Entity\Game\Mount;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerMountRepository::class)]
#[ORM\Table(name: 'player_mount')]
#[ORM\UniqueConstraint(name: 'uniq_player_mount', columns: ['player_id', 'mount_id'])]
#[ORM\Index(columns: ['player_id'], name: 'idx_player_mount_player')]
class PlayerMount
{
    public const SOURCE_QUEST = 'quest';
    public const SOURCE_DROP = 'drop';
    public const SOURCE_PURCHASE = 'purchase';
    public const SOURCE_ACHIEVEMENT = 'achievement';
    public const SOURCE_ADMIN = 'admin';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Mount::class)]
    #[ORM\JoinColumn(name: 'mount_id', referencedColumnName: 'id', nullable: false)]
    private Mount $mount;

    #[ORM\Column(name: 'obtained_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $obtainedAt;

    #[ORM\Column(name: 'source', type: 'string', length: 32)]
    private string $source;

    public function __construct(Player $player, Mount $mount, string $source, ?\DateTimeImmutable $obtainedAt = null)
    {
        $this->player = $player;
        $this->mount = $mount;
        $this->setSource($source);
        $this->obtainedAt = $obtainedAt ?? new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getMount(): Mount
    {
        return $this->mount;
    }

    public function getObtainedAt(): \DateTimeImmutable
    {
        return $this->obtainedAt;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        if (!in_array($source, self::getSources(), true)) {
            throw new \InvalidArgumentException(sprintf('Source d\'obtention "%s" invalide.', $source));
        }

        $this->source = $source;

        return $this;
    }

    /**
     * @return list<string>
     */
    public static function getSources(): array
    {
        return [
            self::SOURCE_QUEST,
            self::SOURCE_DROP,
            self::SOURCE_PURCHASE,
            self::SOURCE_ACHIEVEMENT,
            self::SOURCE_ADMIN,
        ];
    }
}
