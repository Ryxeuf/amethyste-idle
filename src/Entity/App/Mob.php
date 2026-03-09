<?php

namespace App\Entity\App;

use App\Entity\App\Traits\CharacterStatsTrait;
use App\Entity\App\Traits\CoordinatesTrait;
use App\Entity\CharacterInterface;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'mob')]
#[ORM\Entity()]
class Mob implements CharacterInterface
{
    use CharacterStatsTrait;
    use CoordinatesTrait;
    use TimestampableEntity;

    /**
     * Constructor
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

    /**
     * @var Monster
     */
    #[ORM\ManyToOne(targetEntity: Monster::class)]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'id')]
    private Monster $monster;

    #[ORM\ManyToOne(targetEntity: Map::class, inversedBy: 'mobs')]
    #[ORM\JoinColumn(name: 'map_id', referencedColumnName: 'id')]
    private ?Map $map;

    /**
     * Combat dans lequel le monstre est engagé
     */
    #[ORM\ManyToOne(targetEntity: Fight::class, inversedBy: 'mobs')]
    #[ORM\JoinColumn(name: 'fight_id', referencedColumnName: 'id')]
    private ?Fight $fight;

    /**
     * Items générés à la mort du mob
     * @var PlayerItem[]|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: PlayerItem::class, mappedBy: 'mob')]
    private $items;

    /**
     * Niveau du mob
     */
    #[ORM\Column(name: 'level', type: 'integer')]
    protected int $level;

    public function getName(): string
    {
        return $this->getMonster()->getName();
    }

    /**
     * Get id
     *
     * @return integer
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

    /**
     * @return Monster
     */
    public function getMonster(): Monster
    {
        return $this->monster;
    }

    /**
     * @param Monster $monster
     */
    public function setMonster(Monster $monster): void
    {
        $this->monster = $monster;
    }

    /**
     * @return Fight|null
     */
    public function getFight(): ?Fight
    {
        return $this->fight;
    }

    /**
     * @param Fight|null $fight
     */
    public function setFight(?Fight $fight): void
    {
        $this->fight = $fight;
    }

    /**
     * @return int
     */
    public function getMaxLife(): int
    {
        return $this->getMonster()->getLife();
    }

    /**
     * @return Spell
     */
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

    /**
     * @return int
     */
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

    /**
     * @param PlayerItem $item
     */
    public function addItem(PlayerItem $item): void
    {
        $this->items->add($item);
    }

    public function removeItem(PlayerItem $item): void
    {
        $this->items->removeElement($item);
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel(int $level): void
    {
        $this->level = $level;
    }
}
