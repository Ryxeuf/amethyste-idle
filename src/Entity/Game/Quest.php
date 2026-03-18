<?php

namespace App\Entity\Game;

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

    public function __toString(): string
    {
        return $this->name;
    }
}
