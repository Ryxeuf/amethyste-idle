<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CharacterStatsTrait;
use App\Entity\App\Traits\CoordinatesTrait;
use App\Entity\CharacterInterface;
use App\Entity\Game\Race;
use App\Entity\Game\Skill;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'player')]
#[ORM\Index(columns: ['map_id'], name: 'idx_player_map')]
#[ORM\Index(columns: ['fight_id'], name: 'idx_player_fight')]
#[ORM\Index(columns: ['user_id'], name: 'idx_player_user')]
#[ORM\Entity()]
class Player implements CharacterInterface
{
    use CharacterStatsTrait;
    use CoordinatesTrait;
    use TimestampableEntity;

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->inventories = new ArrayCollection();
        $this->domainExperiences = new ArrayCollection();
        $this->quests = new ArrayCollection();
        $this->completedQuests = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->statusEffects = new ArrayCollection();
        $this->bestiaryEntries = new ArrayCollection();
        $this->resourceCatalogEntries = new ArrayCollection();
        $this->achievements = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255, unique: true)]
    private string $name;

    #[ORM\Column(name: 'max_life', type: 'integer')]
    private int $maxLife;

    #[ORM\Column(name: 'energy', type: 'integer')]
    private int $energy;

    #[ORM\Column(name: 'hit', type: 'integer', options: ['default' => 50])]
    private int $hit = 50;

    #[ORM\Column(name: 'max_energy', type: 'integer')]
    private int $maxEnergy;

    #[ORM\Column(name: 'speed', type: 'integer', options: ['default' => 10])]
    private int $speed = 10;

    #[ORM\Column(name: 'is_moving', type: 'boolean', options: ['default' => false])]
    private bool $isMoving = false;

    #[ORM\Column(name: 'class_type', type: 'string', length: 255)]
    private string $classType;

    #[ORM\ManyToOne(targetEntity: Race::class)]
    #[ORM\JoinColumn(name: 'race_id', referencedColumnName: 'id', nullable: true)]
    private ?Race $race = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map = null;

    #[ORM\Column(name: 'lastCoordinates', type: 'string')]
    private string $lastCoordinates;

    #[ORM\OneToMany(targetEntity: Inventory::class, mappedBy: 'player')]
    private $inventories;

    #[ORM\OneToMany(targetEntity: DomainExperience::class, mappedBy: 'player')]
    private $domainExperiences;

    #[ORM\ManyToOne(targetEntity: Fight::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'fight_id', referencedColumnName: 'id')]
    private ?Fight $fight = null;

    #[ORM\OneToMany(targetEntity: PlayerQuest::class, mappedBy: 'player')]
    private $quests;

    #[ORM\OneToMany(targetEntity: PlayerQuestCompleted::class, mappedBy: 'player')]
    private $completedQuests;

    #[ORM\ManyToMany(targetEntity: Skill::class)]
    #[ORM\JoinTable(name: 'player_skill')]
    private $skills;

    #[ORM\Column(name: 'gils', type: 'integer', options: ['default' => 0])]
    private int $gils = 0;

    #[ORM\Column(name: 'respec_count', type: 'integer', options: ['default' => 0])]
    private int $respecCount = 0;

    #[ORM\Column(name: 'prestige_title', type: 'string', length: 100, nullable: true)]
    private ?string $prestigeTitle = null;

    #[ORM\Column(name: 'discovered_recipes', type: 'json', nullable: true)]
    private ?array $discoveredRecipes = [];

    #[ORM\Column(name: 'unlocked_tool_slots', type: 'json', options: ['default' => '[]'])]
    private array $unlockedToolSlots = [];

    #[ORM\Column(name: 'tutorial_step', type: 'smallint', nullable: true)]
    private ?int $tutorialStep = null;

    #[ORM\OneToMany(targetEntity: PlayerStatusEffect::class, mappedBy: 'player', cascade: ['remove'])]
    private Collection $statusEffects;

    #[ORM\OneToMany(targetEntity: PlayerBestiary::class, mappedBy: 'player', cascade: ['remove'])]
    private Collection $bestiaryEntries;

    #[ORM\OneToMany(targetEntity: PlayerResourceCatalog::class, mappedBy: 'player', cascade: ['remove'])]
    private Collection $resourceCatalogEntries;

    #[ORM\OneToMany(targetEntity: PlayerAchievement::class, mappedBy: 'player')]
    private Collection $achievements;

    /** @return Collection<int, PlayerAchievement> */
    public function getAchievements(): Collection
    {
        return $this->achievements;
    }

    /** @return Collection<int, PlayerStatusEffect> */
    public function getStatusEffects(): Collection
    {
        return $this->statusEffects;
    }

    public function addStatusEffect(PlayerStatusEffect $statusEffect): void
    {
        if (!$this->statusEffects->contains($statusEffect)) {
            $this->statusEffects->add($statusEffect);
            $statusEffect->setPlayer($this);
        }
    }

    public function removeStatusEffect(PlayerStatusEffect $statusEffect): void
    {
        $this->statusEffects->removeElement($statusEffect);
    }

    /** @return Collection<int, PlayerBestiary> */
    public function getBestiaryEntries(): Collection
    {
        return $this->bestiaryEntries;
    }

    /** @return Collection<int, PlayerResourceCatalog> */
    public function getResourceCatalogEntries(): Collection
    {
        return $this->resourceCatalogEntries;
    }

    public function getSpeed(): int
    {
        return $this->speed;
    }

    public function setSpeed(int $speed): void
    {
        $this->speed = $speed;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }

    public function getLastCoordinates(): string
    {
        return $this->lastCoordinates;
    }

    public function setLastCoordinates(string $lastCoordinates): void
    {
        $this->lastCoordinates = $lastCoordinates;
    }

    public function setEnergy(int $energy): self
    {
        $this->energy = $energy;

        return $this;
    }

    public function getEnergy(): int
    {
        return $this->energy;
    }

    public function setUser(?User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setMaxLife(int $maxLife): self
    {
        $this->maxLife = $maxLife;

        return $this;
    }

    public function getMaxLife(): int
    {
        return $this->maxLife;
    }

    public function setMaxEnergy(int $maxEnergy): self
    {
        $this->maxEnergy = $maxEnergy;

        return $this;
    }

    public function getMaxEnergy(): int
    {
        return $this->maxEnergy;
    }

    public function addInventory(Inventory $inventory): self
    {
        $this->inventories[] = $inventory;

        return $this;
    }

    public function removeInventory(Inventory $inventory): void
    {
        $this->inventories->removeElement($inventory);
    }

    /**
     * Get inventories.
     *
     * @return Collection|Inventory[]
     */
    public function getInventories()
    {
        return $this->inventories;
    }

    /**
     * Add domainExperience.
     *
     * @return Player
     */
    public function addDomainExperience(DomainExperience $domainExperience)
    {
        $this->domainExperiences[] = $domainExperience;

        return $this;
    }

    /**
     * Remove domainExperience.
     */
    public function removeDomainExperience(DomainExperience $domainExperience)
    {
        $this->domainExperiences->removeElement($domainExperience);
    }

    /**
     * Get domainExperiences.
     *
     * @return Collection|DomainExperience[]
     */
    public function getDomainExperiences()
    {
        return $this->domainExperiences;
    }

    public function hasSkill(Skill $skill): bool
    {
        foreach ($this->getSkills() as $playerSkill) {
            if ($playerSkill === $skill) {
                return true;
            }
        }

        return false;
    }

    /** @return Collection<int, Skill> */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function setSkills(Collection $skills): void
    {
        $this->skills = $skills;
    }

    public function addSkill(Skill $skill): void
    {
        $this->skills->add($skill);
    }

    public function removeSkill(Skill $skill): void
    {
        $this->skills->removeElement($skill);
    }

    public function getFight(): ?Fight
    {
        return $this->fight;
    }

    public function setFight(?Fight $fight): void
    {
        $this->fight = $fight;
    }

    public function getHit(): int
    {
        return $this->hit;
    }

    public function setHit(int $hit): void
    {
        $this->hit = $hit;
    }

    public function getClassType(): string
    {
        return $this->classType;
    }

    public function setClassType(string $classType): void
    {
        $this->classType = $classType;
    }

    /** @return Collection<int, PlayerQuest> */
    public function getQuests(): Collection
    {
        return $this->quests;
    }

    public function setQuests(Collection $quests): void
    {
        $this->quests = $quests;
    }

    /** @return Collection<int, PlayerQuestCompleted> */
    public function getCompletedQuests(): Collection
    {
        return $this->completedQuests;
    }

    public function isMoving(): bool
    {
        return $this->isMoving;
    }

    public function setIsMoving(bool $isMoving): void
    {
        $this->isMoving = $isMoving;
    }

    public function getDiscoveredRecipes(): array
    {
        return $this->discoveredRecipes ?? [];
    }

    public function setDiscoveredRecipes(array $discoveredRecipes): void
    {
        $this->discoveredRecipes = $discoveredRecipes;
    }

    public function getGils(): int
    {
        return $this->gils;
    }

    public function setGils(int $gils): void
    {
        $this->gils = max(0, $gils);
    }

    public function addGils(int $amount): void
    {
        $this->gils += $amount;
    }

    public function removeGils(int $amount): bool
    {
        if ($this->gils < $amount) {
            return false;
        }
        $this->gils -= $amount;

        return true;
    }

    public function getRespecCount(): int
    {
        return $this->respecCount;
    }

    public function setRespecCount(int $respecCount): void
    {
        $this->respecCount = $respecCount;
    }

    public function incrementRespecCount(): void
    {
        ++$this->respecCount;
    }

    public function getPrestigeTitle(): ?string
    {
        return $this->prestigeTitle;
    }

    public function setPrestigeTitle(?string $prestigeTitle): void
    {
        $this->prestigeTitle = $prestigeTitle;
    }

    public function getRace(): ?Race
    {
        return $this->race;
    }

    public function setRace(?Race $race): self
    {
        $this->race = $race;

        return $this;
    }

    public function getUnlockedToolSlots(): array
    {
        return $this->unlockedToolSlots;
    }

    public function setUnlockedToolSlots(array $unlockedToolSlots): void
    {
        $this->unlockedToolSlots = $unlockedToolSlots;
    }

    public function hasToolSlot(string $toolType): bool
    {
        return \in_array($toolType, $this->unlockedToolSlots, true);
    }

    public function unlockToolSlot(string $toolType): void
    {
        if (!$this->hasToolSlot($toolType)) {
            $this->unlockedToolSlots[] = $toolType;
        }
    }

    public function getTutorialStep(): ?int
    {
        return $this->tutorialStep;
    }

    public function setTutorialStep(?int $tutorialStep): void
    {
        $this->tutorialStep = $tutorialStep;
    }
}
