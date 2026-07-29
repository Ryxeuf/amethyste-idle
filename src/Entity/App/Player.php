<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CharacterStatsTrait;
use App\Entity\App\Traits\CoordinatesTrait;
use App\Entity\CharacterInterface;
use App\Entity\Game\Mount;
use App\Entity\Game\Race;
use App\Entity\Game\Skill;
use App\Entity\User;
use App\Enum\CraftSpecialization;
use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'player')]
#[ORM\Index(columns: ['map_id'], name: 'idx_player_map')]
#[ORM\Index(columns: ['current_zone_id'], name: 'idx_player_current_zone')]
#[ORM\Index(columns: ['fight_id'], name: 'idx_player_fight')]
#[ORM\Index(columns: ['user_id'], name: 'idx_player_user')]
#[ORM\Index(columns: ['updated_at'], name: 'idx_player_updated_at')]
#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player implements CharacterInterface
{
    use CharacterStatsTrait;
    use CoordinatesTrait;
    use TimestampableEntity;

    /**
     * Plafond d'energie d'action par defaut : 24 h de regeneration
     * (86400 s / `zone.energy.regen_seconds` a 360 s = 240 points).
     *
     * Le plafond couvre volontairement une journee entiere : en dessous, le
     * joueur qui ne se connecte qu'une fois par 24 h perd l'energie regeneree
     * au-dela du plafond, ce qui penalise l'absence longue (cf. docs/BALANCE.md
     * section 8). Ce plafond ne donne rien de plus au joueur assidu, qui
     * dispose du meme budget quotidien — il le depense simplement plus tot.
     */
    public const DEFAULT_MAX_ACTION_ENERGY = 240;

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
        $this->craftSpecializations = new ArrayCollection();
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

    #[ORM\ManyToOne(targetEntity: Mount::class)]
    #[ORM\JoinColumn(name: 'active_mount_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Mount $activeMount = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map = null;

    /**
     * Zone courante du joueur — reference de position du modele PBBG (ZON-03).
     * Remplace progressivement les coordonnees `x.y`, qui subsistent uniquement
     * pour la carte gelee jusqu'a sa suppression (ZON-21).
     */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'current_zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Zone $currentZone = null;

    /**
     * Voyage en cours (pivot PBBG, ZON-06) : destination + horodatage d'arrivee.
     * L'arrivee est resolue paresseusement par ZoneTravelService::settleArrival.
     */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'travel_to_zone_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Zone $travelToZone = null;

    #[ORM\Column(name: 'travel_arrives_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $travelArrivesAt = null;

    /**
     * Energie d'action PBBG (ZON-07) : gate l'acces aux rencontres (explorer,
     * chasser, recolter, rejoindre un evenement...), JAMAIS le combat lui-meme.
     * Distincte de `energy` (ressource de combat consommee par les sorts).
     * Regeneration paresseuse via ActionEnergyManager (aucun cron).
     */
    #[ORM\Column(name: 'action_energy', type: 'integer', options: ['default' => self::DEFAULT_MAX_ACTION_ENERGY])]
    private int $actionEnergy = self::DEFAULT_MAX_ACTION_ENERGY;

    #[ORM\Column(name: 'max_action_energy', type: 'integer', options: ['default' => self::DEFAULT_MAX_ACTION_ENERGY])]
    private int $maxActionEnergy = self::DEFAULT_MAX_ACTION_ENERGY;

    /**
     * Dernier point de calcul de la regeneration (null = jamais calcule).
     */
    #[ORM\Column(name: 'action_energy_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $actionEnergyUpdatedAt = null;

    /**
     * Derniere activite reelle du personnage (FOY-17).
     *
     * Mis a jour a la **depense d'energie**, jamais a la connexion : agir sur le
     * monde coute de l'energie, s'y connecter non. Remplace le proxy
     * `Player::updatedAt` qu'utilisait `InfluenceAntiExploit`, et que le code
     * lui-meme signalait comme approximatif — `updatedAt` est un champ de cycle
     * de vie Doctrine, il bouge sur des ecritures **systeme** (regeneration,
     * respawn, backfill), si bien qu'une seule connexion valait sept jours
     * d'activite.
     */
    #[ORM\Column(name: 'last_activity_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastActivityAt = null;

    /**
     * Cumul de l'energie d'action depensee depuis la creation (FOY-17).
     *
     * C'est la matiere premiere du dimensionnement du monde : la somme sur tous
     * les personnages, relevee chaque jour par `WorldLoadService`, donne la
     * charge du monde (BALANCE § 22.5). Porte par le joueur et non par un
     * compteur global pour ne pas faire d'une ligne unique un point de
     * contention sur le chemin le plus chaud du jeu.
     *
     * Monotone croissante : jamais remise a zero, la difference entre deux
     * releves suffit.
     */
    #[ORM\Column(name: 'action_energy_spent_total', type: 'bigint', options: ['default' => 0])]
    private string $actionEnergySpentTotal = '0';

    /**
     * Ancre de la regeneration paresseuse des PV hors combat (ZON-12).
     * Reinitialisee a la sortie de chaque combat : la regen ne compte le temps
     * ecoule qu'a partir du moment ou le joueur quitte le combat blesse.
     * null = jamais calcule.
     */
    #[ORM\Column(name: 'life_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lifeUpdatedAt = null;

    /**
     * Suspension d'acces aux canaux d'echange entre joueurs (ECO-16b).
     *
     * Sanction **proportionnee** : le bannissement de compte existe deja mais
     * coupe tout. Un joueur qui truque des prix doit pouvoir continuer a jouer
     * pendant que le marche lui est ferme.
     */
    #[ORM\Column(name: 'trade_suspended_until', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $tradeSuspendedUntil = null;

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

    #[ORM\Column(name: 'renown_score', type: 'integer', options: ['default' => 0])]
    private int $renownScore = 0;

    /**
     * Colonne heritee — le metier unique et irreversible d'avant DOM-04.
     *
     * Elle n'est plus lue par le jeu : la specialisation vit desormais dans
     * `craftSpecializations`, une ligne par arbre. Elle reste en base parce que
     * la migration de donnees s'appuie dessus, et qu'une colonne qu'on retire le
     * jour meme ou on la migre ne laisse aucun recours si la migration s'est
     * trompee.
     */
    #[ORM\Column(name: 'craft_specialization', type: 'string', length: 20, nullable: true, enumType: CraftSpecialization::class)]
    private ?CraftSpecialization $craftSpecialization = null;

    /**
     * La branche prise dans chaque arbre d'artisanat (DOM-04).
     *
     * Aucune exclusivite entre arbres : on peut etre forgeron d'armes **et**
     * alchimiste des remedes. L'exclusivite est au sein de l'arbre, et c'est le
     * schema qui la tient (contrainte unique `(player, craft)`).
     *
     * @var Collection<int, PlayerCraftSpecialization>
     */
    #[ORM\OneToMany(targetEntity: PlayerCraftSpecialization::class, mappedBy: 'player', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $craftSpecializations;

    #[ORM\Column(name: 'discovered_recipes', type: 'json', nullable: true)]
    private ?array $discoveredRecipes = [];

    #[ORM\Column(name: 'unlocked_tool_slots', type: 'json', options: ['default' => '[]'])]
    private array $unlockedToolSlots = [];

    #[ORM\Column(name: 'tutorial_step', type: 'smallint', nullable: true)]
    private ?int $tutorialStep = null;

    /** @var int[] */
    #[ORM\Column(name: 'blocked_players', type: 'json', options: ['default' => '[]'])]
    private array $blockedPlayers = [];

    /** @var array<string, string>|null */
    #[ORM\Column(name: 'avatar_appearance', type: 'json', nullable: true)]
    private ?array $avatarAppearance = null;

    #[ORM\Column(name: 'avatar_hash', type: 'string', length: 64, nullable: true)]
    private ?string $avatarHash = null;

    #[ORM\Column(name: 'avatar_version', type: 'integer', options: ['default' => 1])]
    private int $avatarVersion = 1;

    #[ORM\Column(name: 'avatar_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $avatarUpdatedAt = null;

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

    public function getCurrentZone(): ?Zone
    {
        return $this->currentZone;
    }

    public function setCurrentZone(?Zone $currentZone): void
    {
        $this->currentZone = $currentZone;
    }

    public function getTravelToZone(): ?Zone
    {
        return $this->travelToZone;
    }

    public function setTravelToZone(?Zone $travelToZone): void
    {
        $this->travelToZone = $travelToZone;
    }

    public function getTravelArrivesAt(): ?\DateTimeImmutable
    {
        return $this->travelArrivesAt;
    }

    public function setTravelArrivesAt(?\DateTimeImmutable $travelArrivesAt): void
    {
        $this->travelArrivesAt = $travelArrivesAt;
    }

    public function isTraveling(): bool
    {
        return null !== $this->travelToZone;
    }

    public function getActionEnergy(): int
    {
        return $this->actionEnergy;
    }

    public function setActionEnergy(int $actionEnergy): void
    {
        $this->actionEnergy = max(0, $actionEnergy);
    }

    public function getMaxActionEnergy(): int
    {
        return $this->maxActionEnergy;
    }

    public function setMaxActionEnergy(int $maxActionEnergy): void
    {
        $this->maxActionEnergy = max(1, $maxActionEnergy);
    }

    public function getActionEnergyUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->actionEnergyUpdatedAt;
    }

    public function setActionEnergyUpdatedAt(?\DateTimeImmutable $actionEnergyUpdatedAt): void
    {
        $this->actionEnergyUpdatedAt = $actionEnergyUpdatedAt;
    }

    public function getLastActivityAt(): ?\DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeImmutable $lastActivityAt): self
    {
        $this->lastActivityAt = $lastActivityAt;

        return $this;
    }

    public function getActionEnergySpentTotal(): int
    {
        return (int) $this->actionEnergySpentTotal;
    }

    public function setActionEnergySpentTotal(int $total): self
    {
        $this->actionEnergySpentTotal = (string) max(0, $total);

        return $this;
    }

    public function addActionEnergySpent(int $amount): self
    {
        if ($amount > 0) {
            $this->actionEnergySpentTotal = (string) ($this->getActionEnergySpentTotal() + $amount);
        }

        return $this;
    }

    public function getLifeUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->lifeUpdatedAt;
    }

    public function setLifeUpdatedAt(?\DateTimeImmutable $lifeUpdatedAt): void
    {
        $this->lifeUpdatedAt = $lifeUpdatedAt;
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

    public function getTradeSuspendedUntil(): ?\DateTimeImmutable
    {
        return $this->tradeSuspendedUntil;
    }

    public function setTradeSuspendedUntil(?\DateTimeImmutable $until): self
    {
        $this->tradeSuspendedUntil = $until;

        return $this;
    }

    /**
     * La suspension expire d'elle-meme : une sanction qu'il faut penser a lever
     * finit par ne jamais l'etre.
     */
    public function isTradeSuspended(?\DateTimeImmutable $now = null): bool
    {
        return null !== $this->tradeSuspendedUntil
            && $this->tradeSuspendedUntil > ($now ?? new \DateTimeImmutable());
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

    /**
     * Le joueur possede-t-il cette competence ?
     *
     * La comparaison porte sur l'identifiant, pas sur l'identite d'objet : les
     * competences arrivent ici par deux chemins d'hydratation differents (la
     * collection du joueur, et celle du domaine chargee en JOIN FETCH), et un
     * `===` n'a de sens que tant que les deux passent par la meme carte
     * d'identite. Une competence non encore persistee retombe sur l'identite.
     */
    public function hasSkill(Skill $skill): bool
    {
        $skillId = self::skillId($skill);

        foreach ($this->getSkills() as $playerSkill) {
            if ($playerSkill === $skill) {
                return true;
            }
            if (null !== $skillId && self::skillId($playerSkill) === $skillId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Identifiant d'une competence, ou `null` si elle n'est pas persistee.
     *
     * `Skill::getId()` est declare `int` sur une propriete sans valeur par
     * defaut : une competence transiente — cas courant en test — fait lever
     * l'accesseur plutot que de rendre `null`.
     */
    private static function skillId(Skill $skill): ?int
    {
        try {
            return $skill->getId();
        } catch (\Error) {
            return null;
        }
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

    public function getRenownScore(): int
    {
        return $this->renownScore;
    }

    public function setRenownScore(int $renownScore): void
    {
        $this->renownScore = max(0, $renownScore);
    }

    public function addRenownScore(int $amount): void
    {
        $this->renownScore = max(0, $this->renownScore + $amount);
    }

    public function getCraftSpecialization(): ?CraftSpecialization
    {
        return $this->craftSpecialization;
    }

    public function setCraftSpecialization(?CraftSpecialization $craftSpecialization): void
    {
        $this->craftSpecialization = $craftSpecialization;
    }

    public function hasCraftSpecialization(): bool
    {
        return !$this->craftSpecializations->isEmpty();
    }

    /**
     * Le joueur a-t-il pris une branche dans cet arbre ?
     *
     * La signature n'a pas bouge : les appelants qui gataient une recette sur
     * « etre specialise en forgeron » continuent de fonctionner, et gagnent au
     * passage le droit de l'etre aussi ailleurs.
     */
    public function isSpecializedIn(string $craft): bool
    {
        return $this->getCraftSpecializationFor($craft) !== null;
    }

    public function getCraftSpecializationFor(string $craft): ?PlayerCraftSpecialization
    {
        foreach ($this->craftSpecializations as $specialization) {
            if ($specialization->getCraft()->value === $craft) {
                return $specialization;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, PlayerCraftSpecialization>
     */
    public function getCraftSpecializations(): Collection
    {
        return $this->craftSpecializations;
    }

    public function addCraftSpecialization(PlayerCraftSpecialization $specialization): void
    {
        if (!$this->craftSpecializations->contains($specialization)) {
            $this->craftSpecializations->add($specialization);
        }
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

    public function getActiveMount(): ?Mount
    {
        return $this->activeMount;
    }

    public function setActiveMount(?Mount $activeMount): self
    {
        $this->activeMount = $activeMount;

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

    /** @return int[] */
    public function getBlockedPlayers(): array
    {
        return $this->blockedPlayers;
    }

    public function isPlayerBlocked(int $playerId): bool
    {
        return \in_array($playerId, $this->blockedPlayers, true);
    }

    public function blockPlayer(int $playerId): void
    {
        if (!$this->isPlayerBlocked($playerId)) {
            $this->blockedPlayers[] = $playerId;
        }
    }

    public function unblockPlayer(int $playerId): void
    {
        $this->blockedPlayers = array_values(array_filter(
            $this->blockedPlayers,
            fn (int $id) => $id !== $playerId,
        ));
    }

    /** @return array<string, string>|null */
    public function getAvatarAppearance(): ?array
    {
        return $this->avatarAppearance;
    }

    /** @param array<string, string>|null $avatarAppearance */
    public function setAvatarAppearance(?array $avatarAppearance): void
    {
        $this->avatarAppearance = $avatarAppearance;
        $this->avatarUpdatedAt = new \DateTimeImmutable();
    }

    public function getAvatarHash(): ?string
    {
        return $this->avatarHash;
    }

    public function setAvatarHash(?string $avatarHash): void
    {
        if ($this->avatarHash === $avatarHash) {
            return;
        }

        $this->avatarHash = $avatarHash;
        $this->avatarUpdatedAt = new \DateTimeImmutable();
    }

    public function getAvatarVersion(): int
    {
        return $this->avatarVersion;
    }

    public function setAvatarVersion(int $avatarVersion): void
    {
        $this->avatarVersion = $avatarVersion;
    }

    public function getAvatarUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->avatarUpdatedAt;
    }

    public function hasAvatar(): bool
    {
        return $this->avatarAppearance !== null;
    }
}
