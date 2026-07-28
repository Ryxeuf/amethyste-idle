<?php

namespace App\Entity\App;

use App\Repository\PlayerWeeklyAttendanceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ce qu'un personnage a fait de sa semaine (RET-04).
 *
 * Une ligne par personnage et par semaine ISO, creee a la premiere depense
 * d'energie de la semaine. **Il n'y a rien a remettre a zero** : une semaine
 * nouvelle est une ligne nouvelle, et les semaines passees restent la sans
 * jamais influer sur la courante. C'est la forme qui garantit l'interdit du
 * plan — aucune mecanique de serie continue inter-semaines — plutot qu'une
 * regle qu'un jalon ulterieur pourrait oublier.
 *
 * `activeDays` compte des **jours distincts**, pas des actions : vingt recoltes
 * le meme mardi valent un jour. `lastActiveDay` est ce qui rend le comptage
 * idempotent sans avoir a stocker sept drapeaux.
 */
#[ORM\Entity(repositoryClass: PlayerWeeklyAttendanceRepository::class)]
#[ORM\Table(name: 'player_weekly_attendance')]
#[ORM\UniqueConstraint(name: 'uq_player_weekly_attendance', columns: ['player_id', 'week_key'])]
class PlayerWeeklyAttendance
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    /** Semaine ISO au format `o-\WW`, la meme clef que les autres briques hebdomadaires. */
    #[ORM\Column(name: 'week_key', type: 'string', length: 10)]
    private string $weekKey;

    #[ORM\Column(name: 'active_days', type: 'integer', options: ['default' => 0])]
    private int $activeDays = 0;

    /**
     * Dernier jour compte, au format `Y-m-d`.
     *
     * Une date et non un horodatage : ce qui est compte est le **jour**, et un
     * horodatage inviterait a comparer des heures, donc a compter deux fois une
     * meme journee traversee par un fuseau.
     */
    #[ORM\Column(name: 'last_active_day', type: 'string', length: 10, nullable: true)]
    private ?string $lastActiveDay = null;

    /**
     * Plus haut palier deja rendu, en jours.
     *
     * `0` tant qu'aucun n'a ete franchi. Garde la remise **une seule fois** par
     * palier et par semaine, y compris si le compteur repasse par la (il ne
     * peut que monter, mais un jalon futur pourrait le corriger a la baisse et
     * ne doit pas pouvoir repayer).
     */
    #[ORM\Column(name: 'granted_tier_days', type: 'integer', options: ['default' => 0])]
    private int $grantedTierDays = 0;

    public function __construct(Player $player, string $weekKey)
    {
        $this->player = $player;
        $this->weekKey = $weekKey;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getWeekKey(): string
    {
        return $this->weekKey;
    }

    public function getActiveDays(): int
    {
        return $this->activeDays;
    }

    public function setActiveDays(int $days): self
    {
        $this->activeDays = max(0, $days);

        return $this;
    }

    public function getLastActiveDay(): ?string
    {
        return $this->lastActiveDay;
    }

    public function setLastActiveDay(?string $day): self
    {
        $this->lastActiveDay = $day;

        return $this;
    }

    public function getGrantedTierDays(): int
    {
        return $this->grantedTierDays;
    }

    public function setGrantedTierDays(int $days): self
    {
        $this->grantedTierDays = max(0, $days);

        return $this;
    }
}
