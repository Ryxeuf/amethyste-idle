<?php

namespace App\Entity\App;

use App\Repository\PlayerVisitedRegionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerVisitedRegionRepository::class)]
#[ORM\Table(name: 'player_visited_region')]
#[ORM\UniqueConstraint(name: 'uniq_player_visited_region', columns: ['player_id', 'region_id'])]
#[ORM\Index(name: 'idx_player_visited_region_player', columns: ['player_id'])]
class PlayerVisitedRegion
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(name: 'region_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Region $region;

    #[ORM\Column(name: 'first_visited_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $firstVisitedAt;

    public function __construct(Player $player, Region $region)
    {
        $this->player = $player;
        $this->region = $region;
        $this->firstVisitedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function getFirstVisitedAt(): \DateTimeImmutable
    {
        return $this->firstVisitedAt;
    }
}
