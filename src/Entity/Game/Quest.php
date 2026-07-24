<?php

namespace App\Entity\Game;

use App\Entity\App\GameEvent;
use App\Entity\App\PlayerQuest;
use App\Enum\PlayerRenownTier;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: \App\Repository\QuestRepository::class)]
#[ORM\Table(name: 'game_quests')]
#[ORM\Index(name: 'idx_game_quests_story_arc', columns: ['story_arc'])]
class Quest
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'name_translations', type: 'json', nullable: true)]
    private ?array $nameTranslations = null;

    #[ORM\Column(name: 'description', type: 'text')]
    private $description;

    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(name: 'description_translations', type: 'json', nullable: true)]
    private ?array $descriptionTranslations = null;

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

    /**
     * Score de renommee minimum requis pour voir et accepter la quete.
     * null = aucune restriction (quete disponible des le palier Novice).
     * Sinon, compare au score courant du joueur (Player::getRenownScore()).
     */
    #[ORM\Column(name: 'min_renown_score', type: 'integer', nullable: true)]
    private ?int $minRenownScore = null;

    /**
     * Slug de l'arc narratif regroupant cette quete (intro, saison, zone...).
     * null = quete isolee, hors de tout arc.
     */
    #[ORM\Column(name: 'story_arc', type: 'string', length: 100, nullable: true)]
    private ?string $storyArc = null;

    /**
     * Position de la quete dans son arc (1 = premiere etape).
     * null si la quete n'appartient a aucun arc ou n'a pas de position definie.
     */
    #[ORM\Column(name: 'arc_order', type: 'integer', nullable: true)]
    private ?int $arcOrder = null;

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
     * Get the name translated for the requested locale, or fall back to the base `name` column.
     */
    public function getLocalizedName(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->nameTranslations === null) {
            return $this->name;
        }
        $translation = $this->nameTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getNameTranslations(): array
    {
        return $this->nameTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setNameTranslations(?array $translations): self
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->nameTranslations = $normalized === [] ? null : $normalized;

        return $this;
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

    /**
     * Get the description translated for the requested locale, or fall back to the base `description` column.
     */
    public function getLocalizedDescription(?string $locale): string
    {
        if ($locale === null || $locale === '' || $this->descriptionTranslations === null) {
            return $this->description;
        }
        $translation = $this->descriptionTranslations[$locale] ?? null;

        return \is_string($translation) && trim($translation) !== '' ? $translation : $this->description;
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptionTranslations(): array
    {
        return $this->descriptionTranslations ?? [];
    }

    /**
     * @param array<string, mixed>|null $translations
     */
    public function setDescriptionTranslations(?array $translations): self
    {
        $normalized = [];
        foreach ($translations ?? [] as $locale => $value) {
            if ($locale !== '' && \is_string($value) && trim($value) !== '') {
                $normalized[$locale] = $value;
            }
        }
        $this->descriptionTranslations = $normalized === [] ? null : $normalized;

        return $this;
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

    public function getMinRenownScore(): ?int
    {
        return $this->minRenownScore;
    }

    public function setMinRenownScore(?int $minRenownScore): self
    {
        $this->minRenownScore = $minRenownScore !== null && $minRenownScore > 0 ? $minRenownScore : null;

        return $this;
    }

    public function hasRenownRequirement(): bool
    {
        return $this->minRenownScore !== null && $this->minRenownScore > 0;
    }

    public function isUnlockedForRenownScore(int $playerRenownScore): bool
    {
        return !$this->hasRenownRequirement() || $playerRenownScore >= $this->minRenownScore;
    }

    /**
     * Palier de renommee minimum necessaire pour cette quete (null si pas de restriction).
     * Utilise le score seuil pour resoudre le palier exact correspondant au seuil requis.
     */
    public function getRequiredRenownTier(): ?PlayerRenownTier
    {
        if (!$this->hasRenownRequirement()) {
            return null;
        }

        return PlayerRenownTier::fromScore($this->minRenownScore);
    }

    public function getStoryArc(): ?string
    {
        return $this->storyArc;
    }

    public function setStoryArc(?string $storyArc): self
    {
        $normalized = $storyArc !== null ? trim($storyArc) : null;
        $this->storyArc = $normalized === '' ? null : $normalized;

        return $this;
    }

    public function hasStoryArc(): bool
    {
        return $this->storyArc !== null;
    }

    public function belongsToArc(string $storyArc): bool
    {
        return $this->storyArc !== null && $this->storyArc === $storyArc;
    }

    public function getArcOrder(): ?int
    {
        return $this->arcOrder;
    }

    public function setArcOrder(?int $arcOrder): self
    {
        $this->arcOrder = $arcOrder;

        return $this;
    }

    /**
     * Trie une liste de quetes par `arcOrder` croissant. Les quetes sans position
     * (`arcOrder = null`) sont rejetees en fin de liste, dans un ordre stable.
     *
     * @param Quest[] $quests
     *
     * @return Quest[] liste reindexee, triee par position d'arc
     */
    public static function sortByArcOrder(array $quests): array
    {
        $quests = array_values($quests);

        usort($quests, static function (Quest $a, Quest $b): int {
            $orderA = $a->getArcOrder();
            $orderB = $b->getArcOrder();

            if ($orderA === $orderB) {
                return 0;
            }
            if ($orderA === null) {
                return 1;
            }
            if ($orderB === null) {
                return -1;
            }

            return $orderA <=> $orderB;
        });

        return $quests;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
