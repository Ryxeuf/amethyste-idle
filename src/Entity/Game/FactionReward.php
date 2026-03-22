<?php

namespace App\Entity\Game;

use App\Enum\ReputationTier;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_faction_rewards')]
class FactionReward
{
    use TimestampableEntity;

    public const TYPE_RECIPE_UNLOCK = 'recipe_unlock';
    public const TYPE_ITEM = 'item';
    public const TYPE_DISCOUNT = 'discount';
    public const TYPE_ZONE_ACCESS = 'zone_access';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: 'faction_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Faction $faction;

    #[ORM\Column(name: 'required_tier', type: 'string', length: 32, enumType: ReputationTier::class)]
    private ReputationTier $requiredTier;

    #[ORM\Column(name: 'reward_type', type: 'string', length: 64)]
    private string $rewardType;

    #[ORM\Column(name: 'reward_data', type: 'json', nullable: true)]
    private ?array $rewardData = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'description', type: 'text')]
    private string $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFaction(): Faction
    {
        return $this->faction;
    }

    public function setFaction(Faction $faction): self
    {
        $this->faction = $faction;

        return $this;
    }

    public function getRequiredTier(): ReputationTier
    {
        return $this->requiredTier;
    }

    public function setRequiredTier(ReputationTier $requiredTier): self
    {
        $this->requiredTier = $requiredTier;

        return $this;
    }

    public function getRewardType(): string
    {
        return $this->rewardType;
    }

    public function setRewardType(string $rewardType): self
    {
        $this->rewardType = $rewardType;

        return $this;
    }

    public function getRewardData(): ?array
    {
        return $this->rewardData;
    }

    public function setRewardData(?array $rewardData): self
    {
        $this->rewardData = $rewardData;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
