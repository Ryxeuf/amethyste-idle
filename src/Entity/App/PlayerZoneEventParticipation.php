<?php

namespace App\Entity\App;

use App\Repository\PlayerZoneEventParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Participation d'un joueur a un evenement de zone (pivot PBBG, ZON-15).
 *
 * Enregistree quand le joueur rejoint un evenement annonce (cout en energie).
 * Le champ `contribution` (degats/assauts cumules) prepare la distribution du
 * loot a la contribution des boss de zone asynchrones (ZON-18) : ZON-15 pose la
 * participation, ZON-18 l'alimente et la recompense.
 */
#[ORM\Entity(repositoryClass: PlayerZoneEventParticipationRepository::class)]
#[ORM\Table(name: 'player_zone_event_participation')]
#[ORM\UniqueConstraint(name: 'uniq_player_zone_event', columns: ['player_id', 'game_event_id'])]
#[ORM\Index(name: 'idx_zone_event_participation_event', columns: ['game_event_id'])]
class PlayerZoneEventParticipation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: GameEvent::class)]
    #[ORM\JoinColumn(name: 'game_event_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private GameEvent $gameEvent;

    #[ORM\Column(name: 'joined_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(name: 'contribution', type: 'integer', options: ['default' => 0])]
    private int $contribution = 0;

    public function __construct(Player $player, GameEvent $gameEvent)
    {
        $this->player = $player;
        $this->gameEvent = $gameEvent;
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getGameEvent(): GameEvent
    {
        return $this->gameEvent;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function getContribution(): int
    {
        return $this->contribution;
    }

    public function setContribution(int $contribution): void
    {
        $this->contribution = max(0, $contribution);
    }

    public function addContribution(int $amount): void
    {
        $this->contribution = max(0, $this->contribution + $amount);
    }
}
