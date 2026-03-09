<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: "game_monsters")]
class Monster
{
    use TimestampableEntity;

    public function __construct()
    {
        $this->monsterItems = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id", type: "integer")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 255)]
    private $name;

    #[ORM\Column(name: "slug", type: "string", length: 255)]
    private $slug;

    #[ORM\Column(name: "life", type: "integer")]
    private $life;

    #[ORM\OneToMany(targetEntity: MonsterItem::class, mappedBy: "monster")]
    private $monsterItems;

    #[ORM\ManyToOne(targetEntity: Spell::class)]
    #[ORM\JoinColumn(name: "attack_id", referencedColumnName: "id")]
    private $attack;

    #[ORM\ManyToMany(targetEntity: Spell::class)]
    #[ORM\JoinTable(name: "monster_spells")]
    private $spells;

    #[ORM\Column(name: "speed", type: "integer", options: ["default" => 10])]
    private $speed = 10;

    #[ORM\Column(name: "hit", type: "integer", options: ["default" => 20])]
    private $hit = 20;

    #[ORM\Column(name: "level", type: "integer", options: ["default" => 1])]
    private $level = 1;

    /**
     * @return int
     */
    public function getSpeed(): int
    {
        return $this->speed;
    }

    /**
     * @param int $speed
     */
    public function setSpeed(int $speed): void
    {
        $this->speed = $speed;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Monster
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set life
     *
     * @param integer $life
     *
     * @return Monster
     */
    public function setLife($life)
    {
        $this->life = $life;

        return $this;
    }

    /**
     * Get life
     *
     * @return int
     */
    public function getLife()
    {
        return $this->life;
    }

    /**
     * Add monsterItem
     *
     * @param MonsterItem $monsterItem
     *
     * @return Monster
     */
    public function addMonsterItem(MonsterItem $monsterItem)
    {
        $this->monsterItems[] = $monsterItem;

        return $this;
    }

    /**
     * Remove monsterItem
     *
     * @param MonsterItem $monsterItem
     */
    public function removeMonsterItem(MonsterItem $monsterItem)
    {
        $this->monsterItems->removeElement($monsterItem);
    }

    /**
     * Get monsterItems
     *
     * @return Collection|MonsterItem[]
     */
    public function getMonsterItems()
    {
        return $this->monsterItems;
    }

    /**
     * @return Spell
     */
    public function getAttack(): Spell
    {
        return $this->attack;
    }

    /**
     * @param Spell $attack
     */
    public function setAttack(Spell $attack): void
    {
        $this->attack = $attack;
    }

    /**
     * @return Spell[]|ArrayCollection
     */
    public function getSpells()
    {
        return $this->spells;
    }

    /**
     * @param Spell[]|ArrayCollection $spells
     */
    public function setSpells($spells): void
    {
        $this->spells = $spells;
    }

    /**
     * @return int
     */
    public function getHit(): int
    {
        return $this->hit;
    }

    /**
     * @param int $hit
     */
    public function setHit(int $hit): void
    {
        $this->hit = $hit;
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
