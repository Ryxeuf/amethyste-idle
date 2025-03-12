<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: "game_monster_items")]
class MonsterItem
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private $id;

    #[ORM\Column(name: "probability", type: "float")]
    private $probability;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: "item_id", referencedColumnName: "id")]
    private $item;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: "monster_items")]
    #[ORM\JoinColumn(name: "monster_id", referencedColumnName: "id")]
    private $monster;

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
     * Set probability
     *
     * @param float $probability
     *
     * @return MonsterItem
     */
    public function setProbability($probability)
    {
        $this->probability = $probability;

        return $this;
    }

    /**
     * Get probability
     *
     * @return float
     */
    public function getProbability()
    {
        return $this->probability;
    }

    /**
     * Set item
     *
     * @param Item $item
     *
     * @return MonsterItem
     */
    public function setItem(Item $item = null)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item
     *
     * @return Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set monster
     *
     * @param Monster $monster
     *
     * @return MonsterItem
     */
    public function setMonster(Monster $monster = null)
    {
        $this->monster = $monster;

        return $this;
    }

    /**
     * Get monster
     *
     * @return Monster
     */
    public function getMonster()
    {
        return $this->monster;
    }
}
