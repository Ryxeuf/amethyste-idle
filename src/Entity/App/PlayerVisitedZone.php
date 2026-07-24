<?php

namespace App\Entity\App;

use App\Repository\PlayerVisitedZoneRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Zones decouvertes par un joueur (pivot PBBG, ZON-06).
 * Transposition du fast travel : les liaisons rapides (requiresDiscovery)
 * ne sont utilisables que vers une zone deja visitee.
 */
#[ORM\Entity(repositoryClass: PlayerVisitedZoneRepository::class)]
#[ORM\Table(name: 'player_visited_zone')]
#[ORM\UniqueConstraint(name: 'uniq_player_visited_zone', columns: ['player_id', 'zone_id'])]
#[ORM\Index(name: 'idx_player_visited_zone_player', columns: ['player_id'])]
class PlayerVisitedZone
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $zone;

    #[ORM\Column(name: 'first_visited_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $firstVisitedAt;

    public function __construct(Player $player, Zone $zone)
    {
        $this->player = $player;
        $this->zone = $zone;
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

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getFirstVisitedAt(): \DateTimeImmutable
    {
        return $this->firstVisitedAt;
    }
}
