<?php

namespace App\Entity\Game;

use App\Entity\App\GameEvent;
use App\Entity\App\PlayerQuest;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_quests')]
class Quest
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    #[ORM\Column(name: 'description', type: 'text')]
    private $description;

    #[ORM\Column(name: 'requirements', type: 'json', nullable: true)]
    private $requirements;

    #[ORM\Column(name: 'rewards', type: 'json', nullable: true)]
    private $rewards;

    #[ORM\Column(name: 'prerequisite_quests', type: 'json', nullable: true)]
    private ?array $prerequisiteQuests = null;

    #[ORM\Column(name: 'choice_outcome', type: 'json', nullable: true)]
    private ?array $choiceOutcome = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDaily = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $dailyPool = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isHidden = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $triggerCondition = null;

    #[ORM\ManyToOne(targetEntity: GameEvent::class)]
    #[ORM\JoinColumn(name: 'game_event_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GameEvent $gameEvent = null;

    #[ORM\OneToMany(targetEntity: PlayerQuest::class, mappedBy: 'quest')]
    private $players;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set name.
     */
    public function setName(string $name): Quest
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set description.
     */
    public function setDescription(string $description): Quest
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRequirements(): array
    {
        return $this->requirements ?? [];
    }

    public function setRequirements(array $requirements): Quest
    {
        $this->requirements = $requirements;

        return $this;
    }

    public function getRewards(): array
    {
        return $this->rewards ?? [];
    }

    public function setRewards(array $rewards): Quest
    {
        $this->rewards = $rewards;

        return $this;
    }

    /**
     * @return PlayerQuest[]|ArrayCollection
     */
    public function getPlayers()
    {
        return $this->players;
    }

    /**
     * @param PlayerQuest[]|ArrayCollection $players
     */
    public function setPlayers($players): void
    {
        $this->players = $players;
    }

    public function getPrerequisiteQuests(): ?array
    {
        return $this->prerequisiteQuests;
    }

    public function setPrerequisiteQuests(?array $prerequisiteQuests): Quest
    {
        $this->prerequisiteQuests = $prerequisiteQuests;

        return $this;
    }

    public function hasPrerequisites(): bool
    {
        return !empty($this->prerequisiteQuests);
    }

    public function getChoiceOutcome(): ?array
    {
        return $this->choiceOutcome;
    }

    public function setChoiceOutcome(?array $choiceOutcome): Quest
    {
        $this->choiceOutcome = $choiceOutcome;

        return $this;
    }

    public function hasChoices(): bool
    {
        return !empty($this->choiceOutcome);
    }

    public function isDaily(): bool
    {
        return $this->isDaily;
    }

    public function setIsDaily(bool $isDaily): Quest
    {
        $this->isDaily = $isDaily;

        return $this;
    }

    public function getDailyPool(): ?string
    {
        return $this->dailyPool;
    }

    public function setDailyPool(?string $dailyPool): Quest
    {
        $this->dailyPool = $dailyPool;

        return $this;
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(bool $isHidden): Quest
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    public function getTriggerCondition(): ?array
    {
        return $this->triggerCondition;
    }

    public function setTriggerCondition(?array $triggerCondition): Quest
    {
        $this->triggerCondition = $triggerCondition;

        return $this;
    }

    public function getGameEvent(): ?GameEvent
    {
        return $this->gameEvent;
    }

    public function setGameEvent(?GameEvent $gameEvent): Quest
    {
        $this->gameEvent = $gameEvent;

        return $this;
    }

    public function isEventQuest(): bool
    {
        return $this->gameEvent !== null;
    }

    public function isEventActive(): bool
    {
        return $this->gameEvent === null || $this->gameEvent->isActive();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
