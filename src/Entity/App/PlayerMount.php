<?php

namespace App\Entity\App;

use App\Entity\Game\Mount;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerMountRepository::class)]
#[ORM\Table(name: 'player_mount')]
#[ORM\UniqueConstraint(name: 'uniq_player_mount', columns: ['player_id', 'mount_id'])]
#[ORM\Index(name: 'idx_player_mount_player', columns: ['player_id'])]
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
    #[ORM\JoinColumn(name: 'mount_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Mount $mount;

    #[ORM\Column(name: 'acquired_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $acquiredAt;

    #[ORM\Column(name: 'source', type: 'string', length: 32)]
    private string $source;

    public function __construct(Player $player, Mount $mount, string $source)
    {
        if (!in_array($source, self::getSources(), true)) {
            throw new \InvalidArgumentException(sprintf('Source d\'acquisition "%s" invalide.', $source));
        }

        $this->player = $player;
        $this->mount = $mount;
        $this->source = $source;
        $this->acquiredAt = new \DateTimeImmutable();
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

    public function getAcquiredAt(): \DateTimeImmutable
    {
        return $this->acquiredAt;
    }

    public function getSource(): string
    {
        return $this->source;
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
