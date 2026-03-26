<?php

namespace App\Entity\App;

use App\Enum\InfluenceActivityType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'influence_log')]
#[ORM\Index(name: 'idx_influence_log_guild_season', columns: ['guild_id', 'season_id'])]
#[ORM\Index(name: 'idx_influence_log_player', columns: ['player_id'])]
#[ORM\Index(name: 'idx_influence_log_created', columns: ['created_at'])]
class InfluenceLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Guild::class)]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: false)]
    private Guild $guild;

    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(name: 'region_id', referencedColumnName: 'id', nullable: false)]
    private Region $region;

    #[ORM\ManyToOne(targetEntity: InfluenceSeason::class)]
    #[ORM\JoinColumn(name: 'season_id', referencedColumnName: 'id', nullable: false)]
    private InfluenceSeason $season;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false)]
    private Player $player;

    #[ORM\Column(name: 'activity_type', type: 'string', length: 20, enumType: InfluenceActivityType::class)]
    private InfluenceActivityType $activityType;

    #[ORM\Column(name: 'points_earned', type: 'integer')]
    private int $pointsEarned;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'details', type: 'json', nullable: true)]
    private ?array $details = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuild(): Guild
    {
        return $this->guild;
    }

    public function setGuild(Guild $guild): self
    {
        $this->guild = $guild;

        return $this;
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function setRegion(Region $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getSeason(): InfluenceSeason
    {
        return $this->season;
    }

    public function setSeason(InfluenceSeason $season): self
    {
        $this->season = $season;

        return $this;
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

    public function getActivityType(): InfluenceActivityType
    {
        return $this->activityType;
    }

    public function setActivityType(InfluenceActivityType $activityType): self
    {
        $this->activityType = $activityType;

        return $this;
    }

    public function getPointsEarned(): int
    {
        return $this->pointsEarned;
    }

    public function setPointsEarned(int $pointsEarned): self
    {
        $this->pointsEarned = $pointsEarned;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public function setDetails(?array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
