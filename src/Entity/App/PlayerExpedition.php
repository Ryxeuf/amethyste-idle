<?php

namespace App\Entity\App;

use App\Repository\PlayerExpeditionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Expedition time-gated (pivot PBBG, ZON-13).
 *
 * Le joueur envoie son personnage explorer une zone pendant N heures reelles ;
 * au retour, un butin l'attend (a recuperer). Etat exclusif : un seul par
 * joueur a la fois (contrainte UNIQUE sur player_id), supprime a la
 * recuperation du butin. Le deroulement est time-gated en temps reel, resolu
 * paresseusement au chargement de l'ecran de zone — aucun cron par joueur,
 * comme le voyage (ZON-06) et les regens (ZON-07/12).
 */
#[ORM\Entity(repositoryClass: PlayerExpeditionRepository::class)]
#[ORM\Table(name: 'player_expedition')]
#[ORM\UniqueConstraint(name: 'uniq_player_expedition_player', columns: ['player_id'])]
class PlayerExpedition
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

    #[ORM\Column(name: 'duration_key', type: 'string', length: 32)]
    private string $durationKey;

    #[ORM\Column(name: 'started_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'ends_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $endsAt;

    /**
     * Renseigne quand la notification de fin a ete emise (in-game + Mercure),
     * pour ne notifier qu'une seule fois lors de la resolution paresseuse.
     */
    #[ORM\Column(name: 'notified_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $notifiedAt = null;

    public function __construct(Player $player, Zone $zone, string $durationKey, \DateTimeImmutable $startedAt, \DateTimeImmutable $endsAt)
    {
        $this->player = $player;
        $this->zone = $zone;
        $this->durationKey = $durationKey;
        $this->startedAt = $startedAt;
        $this->endsAt = $endsAt;
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

    public function getDurationKey(): string
    {
        return $this->durationKey;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(?\DateTimeImmutable $notifiedAt): void
    {
        $this->notifiedAt = $notifiedAt;
    }

    /**
     * L'expedition est terminee (butin a recuperer) des que l'heure de retour
     * est passee.
     */
    public function isComplete(?\DateTimeImmutable $now = null): bool
    {
        return ($now ?? new \DateTimeImmutable()) >= $this->endsAt;
    }
}
