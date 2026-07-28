<?php

namespace App\Entity\App;

use App\Enum\InfluenceActivityType;
use App\Enum\WeeklyCommissionReward;
use App\Enum\WeeklyCommissionStatus;
use App\Repository\PlayerWeeklyCommissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * La commission de la semaine d'un personnage (RET-02).
 *
 * Le rendez-vous hebdomadaire **personnel**, pendant du defi de guilde. Il
 * existe pour une raison precise : sans lui, le joueur solo n'a aucune part au
 * chantier collectif, et le passage critique des semaines 3 a 6
 * (GAME_PROGRESSION § 3) se joue sans lui.
 *
 * La commission se livre a un **foyer** (FOY-01), pas a un guichet abstrait :
 * ce qu'on rapporte se depose quelque part, dans une ville qui monte.
 *
 * L'unicite porte sur le couple joueur/semaine — c'est elle qui interdit le
 * reroll. Ce qui se choisit une fois se retient ; ce qui se rejoue jusqu'a
 * tomber juste ne se retient pas.
 */
#[ORM\Entity(repositoryClass: PlayerWeeklyCommissionRepository::class)]
#[ORM\Table(name: 'player_weekly_commission')]
#[ORM\UniqueConstraint(name: 'uq_player_weekly_commission', columns: ['player_id', 'week_key'])]
class PlayerWeeklyCommission
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    /**
     * Semaine ISO (`2026-W31`) — la meme clef que la rotation des defis, pour
     * que les deux rendez-vous tombent le meme lundi.
     */
    #[ORM\Column(name: 'week_key', type: 'string', length: 10)]
    private string $weekKey;

    #[ORM\Column(name: 'template_slug', type: 'string', length: 64)]
    private string $templateSlug;

    #[ORM\Column(name: 'activity', type: 'string', length: 20, enumType: InfluenceActivityType::class)]
    private InfluenceActivityType $activity;

    #[ORM\Column(name: 'target', type: 'integer')]
    private int $target = 1;

    #[ORM\Column(name: 'progress', type: 'integer', options: ['default' => 0])]
    private int $progress = 0;

    /**
     * Zone de livraison — toujours une zone **qui a un foyer**. Livrer a Lumiere
     * n'aurait aucun effet : rien ne s'y depose (GAME_WORLD § 3.4).
     */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'delivery_zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Zone $deliveryZone = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20, enumType: WeeklyCommissionStatus::class)]
    private WeeklyCommissionStatus $status = WeeklyCommissionStatus::Open;

    #[ORM\Column(name: 'delivered_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    /**
     * Recompense choisie a la livraison (RET-02b) — `null` tant que la
     * commission n'est pas livree.
     *
     * Le choix est garde parce qu'il **se lit** : une commission livree doit
     * pouvoir dire ce qu'elle a rendu, sans quoi le joueur qui revient une
     * semaine plus tard ne sait plus s'il a pris la bourse ou paye le tribut.
     */
    #[ORM\Column(name: 'reward', type: 'string', length: 20, nullable: true, enumType: WeeklyCommissionReward::class)]
    private ?WeeklyCommissionReward $reward = null;

    public function __construct(Player $player, string $weekKey, string $templateSlug, InfluenceActivityType $activity, int $target)
    {
        $this->player = $player;
        $this->weekKey = $weekKey;
        $this->templateSlug = $templateSlug;
        $this->activity = $activity;
        $this->target = max(1, $target);
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

    public function getTemplateSlug(): string
    {
        return $this->templateSlug;
    }

    public function getActivity(): InfluenceActivityType
    {
        return $this->activity;
    }

    public function getTarget(): int
    {
        return $this->target;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function addProgress(int $amount): self
    {
        if ($amount > 0) {
            $this->progress += $amount;
        }

        return $this;
    }

    public function isComplete(): bool
    {
        return $this->progress >= $this->target;
    }

    /**
     * Avancement en pourcentage, borne a 100.
     *
     * Depasser l'objectif est normal — on ne compte pas ses prises au dernier
     * poisson pres — mais une jauge a 140 % dirait au joueur qu'il a gaspille.
     */
    public function getProgressPercent(): int
    {
        return (int) min(100, round($this->progress * 100 / max(1, $this->target)));
    }

    public function getDeliveryZone(): ?Zone
    {
        return $this->deliveryZone;
    }

    public function setDeliveryZone(?Zone $zone): self
    {
        $this->deliveryZone = $zone;

        return $this;
    }

    public function getStatus(): WeeklyCommissionStatus
    {
        return $this->status;
    }

    public function setStatus(WeeklyCommissionStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): self
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }

    public function getReward(): ?WeeklyCommissionReward
    {
        return $this->reward;
    }

    public function setReward(?WeeklyCommissionReward $reward): self
    {
        $this->reward = $reward;

        return $this;
    }
}
