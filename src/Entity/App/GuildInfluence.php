<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: 'guild_influence')]
#[ORM\UniqueConstraint(name: 'uq_guild_influence_guild_region_season', columns: ['guild_id', 'region_id', 'season_id'])]
#[ORM\Index(name: 'idx_guild_influence_ranking', columns: ['region_id', 'season_id', 'points'])]
class GuildInfluence
{
    use TimestampableEntity;

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

    #[ORM\Column(name: 'points', type: 'integer', options: ['default' => 0])]
    private int $points = 0;

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

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }

    public function addPoints(int $points): self
    {
        $this->points += $points;

        return $this;
    }
}
