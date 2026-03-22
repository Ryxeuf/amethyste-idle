<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'game_monster_items')]
class MonsterItem
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'probability', type: 'float')]
    private $probability;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    private $item;

    #[ORM\Column(name: 'guaranteed', type: 'boolean', options: ['default' => false])]
    private bool $guaranteed = false;

    #[ORM\Column(name: 'min_difficulty', type: 'integer', nullable: true)]
    private ?int $minDifficulty = null;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: 'monsterItems')]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'id')]
    private $monster;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set probability.
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
     * Get probability.
     *
     * @return float
     */
    public function getProbability()
    {
        return $this->probability;
    }

    /**
     * Set item.
     *
     * @return MonsterItem
     */
    public function setItem(?Item $item = null)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item.
     *
     * @return Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set monster.
     *
     * @return MonsterItem
     */
    public function setMonster(?Monster $monster = null)
    {
        $this->monster = $monster;

        return $this;
    }

    /**
     * Get monster.
     *
     * @return Monster
     */
    public function getMonster()
    {
        return $this->monster;
    }

    public function isGuaranteed(): bool
    {
        return $this->guaranteed;
    }

    public function setGuaranteed(bool $guaranteed): self
    {
        $this->guaranteed = $guaranteed;

        return $this;
    }

    public function getMinDifficulty(): ?int
    {
        return $this->minDifficulty;
    }

    public function setMinDifficulty(?int $minDifficulty): self
    {
        $this->minDifficulty = $minDifficulty;

        return $this;
    }
}
