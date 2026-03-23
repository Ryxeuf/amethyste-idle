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

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Faction::class)]
    #[ORM\JoinColumn(name: 'faction_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Faction $faction;

    #[ORM\Column(name: 'required_tier', type: 'string', length: 32)]
    private string $requiredTier;

    #[ORM\Column(name: 'reward_type', type: 'string', length: 64)]
    private string $rewardType;

    #[ORM\Column(name: 'reward_data', type: 'json')]
    private array $rewardData = [];

    #[ORM\Column(name: 'label', type: 'string', length: 255)]
    private string $label;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

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
        return ReputationTier::from($this->requiredTier);
    }

    public function setRequiredTier(ReputationTier $tier): self
    {
        $this->requiredTier = $tier->value;

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

    public function getRewardData(): array
    {
        return $this->rewardData;
    }

    public function setRewardData(array $rewardData): self
    {
        $this->rewardData = $rewardData;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
