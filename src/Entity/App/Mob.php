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
#[ORM\Index(columns: ['map_id'], name: 'idx_mob_map')]
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
    private ?Fight $fight;

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
        return $this->getMonster()->getLife();
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
}
