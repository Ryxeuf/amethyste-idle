<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CharacterStatsTrait;
use App\Entity\App\Traits\CoordinatesTrait;
use App\Entity\CharacterInterface;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\Enum\WeatherType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'mob')]
#[ORM\Index(columns: ['map_id'], name: 'idx_mob_map')]
#[ORM\Index(columns: ['fight_id'], name: 'idx_mob_fight')]
#[ORM\Index(columns: ['monster_id'], name: 'idx_mob_monster')]
#[ORM\Entity(repositoryClass: \App\Repository\MobRepository::class)]
class Mob implements CharacterInterface
{
    use CharacterStatsTrait;
    use CoordinatesTrait;
    use TimestampableEntity;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Monster::class)]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'id')]
    private Monster $monster;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'mobs')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map;

    /**
     * Combat dans lequel le monstre est engagé.
     */
    #[ORM\ManyToOne(targetEntity: Fight::class, inversedBy: 'mobs')]
    #[ORM\JoinColumn(name: 'fight_id', referencedColumnName: 'id')]
    private ?Fight $fight = null;

    /**
     * Items générés à la mort du mob.
     *
     * @var PlayerItem[]|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: PlayerItem::class, mappedBy: 'mob')]
    private $items;

    /**
     * Niveau du mob.
     */
    #[ORM\Column(name: 'level', type: 'integer')]
    protected int $level;

    /**
     * Si true, le mob n'apparait que la nuit (20h-6h in-game).
     */
    #[ORM\Column(name: 'nocturnal', type: 'boolean', options: ['default' => false])]
    private bool $nocturnal = false;

    /**
     * Si non-null, le mob n'apparait que sous cette condition météo.
     */
    #[ORM\Column(name: 'spawn_weather', type: 'string', length: 20, nullable: true, enumType: WeatherType::class)]
    private ?WeatherType $spawnWeather = null;

    /**
     * Tag de groupe : les mobs avec le même groupTag sur la même map combattent ensemble.
     */
    #[ORM\Column(name: 'group_tag', type: 'string', length: 50, nullable: true)]
    private ?string $groupTag = null;

    /**
     * True si le mob a été invoqué en combat (non présent sur la carte, pas de loot, pas de XP).
     */
    #[ORM\Column(name: 'summoned', type: 'boolean', options: ['default' => false])]
    private bool $summoned = false;

    /**
     * True si le mob est un world boss (spawné par un GameEvent boss_spawn).
     */
    #[ORM\Column(name: 'is_world_boss', type: 'boolean', options: ['default' => false])]
    private bool $isWorldBoss = false;

    /**
     * GameEvent qui a spawné ce world boss (null pour les mobs normaux).
     */
    #[ORM\ManyToOne(targetEntity: GameEvent::class)]
    #[ORM\JoinColumn(name: 'game_event_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GameEvent $gameEvent = null;

    public function getName(): string
    {
        return $this->getMonster()->getName();
    }

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }

    public function getMonster(): Monster
    {
        return $this->monster;
    }

    public function setMonster(Monster $monster): void
    {
        $this->monster = $monster;
    }

    public function getFight(): ?Fight
    {
        return $this->fight;
    }

    public function setFight(?Fight $fight): void
    {
        $this->fight = $fight;
    }

    public function getMaxLife(): int
    {
        $base = $this->getMonster()->getLife();
        $multiplier = $this->fight?->getMetadataValue('difficulty_multiplier', 1.0) ?? 1.0;

        return (int) round($base * $multiplier);
    }

    public function getAttack(): Spell
    {
        return $this->getMonster()->getAttack();
    }

    /**
     * @return Spell[]|ArrayCollection
     */
    public function getSpells()
    {
        return $this->getMonster()->getSpells();
    }

    public function getSpeed(): int
    {
        return $this->getMonster()->getSpeed();
    }

    /**
     * @return PlayerItem[]|ArrayCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param PlayerItem[]|ArrayCollection $items
     */
    public function setItems($items): void
    {
        $this->items = $items;
    }

    public function addItem(PlayerItem $item): void
    {
        $this->items->add($item);
    }

    public function removeItem(PlayerItem $item): void
    {
        $this->items->removeElement($item);
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function isNocturnal(): bool
    {
        return $this->nocturnal;
    }

    public function setNocturnal(bool $nocturnal): void
    {
        $this->nocturnal = $nocturnal;
    }

    public function getSpawnWeather(): ?WeatherType
    {
        return $this->spawnWeather;
    }

    public function setSpawnWeather(?WeatherType $spawnWeather): void
    {
        $this->spawnWeather = $spawnWeather;
    }

    public function getGroupTag(): ?string
    {
        return $this->groupTag;
    }

    public function setGroupTag(?string $groupTag): void
    {
        $this->groupTag = $groupTag;
    }

    public function isSummoned(): bool
    {
        return $this->summoned;
    }

    public function setSummoned(bool $summoned): void
    {
        $this->summoned = $summoned;
    }

    public function isWorldBoss(): bool
    {
        return $this->isWorldBoss;
    }

    public function setIsWorldBoss(bool $isWorldBoss): void
    {
        $this->isWorldBoss = $isWorldBoss;
    }

    public function getGameEvent(): ?GameEvent
    {
        return $this->gameEvent;
    }

    public function setGameEvent(?GameEvent $gameEvent): void
    {
        $this->gameEvent = $gameEvent;
    }
}
