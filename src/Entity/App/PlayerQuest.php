<?php

namespace App\Entity\App;

use App\Entity\Game\Quest;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\App\Player;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: "player_quest")]
#[ORM\UniqueConstraint(name: "player_quest_unique", columns: ["player_id", "quest_id"])]
#[UniqueEntity(
    fields: ["quest", "player"],
    message: "The player is currently making the quest."
)]
class PlayerQuest
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: "quests")]
    #[ORM\JoinColumn(name: "player_id", referencedColumnName: "id")]
    private $player;

    #[ORM\ManyToOne(targetEntity: Quest::class, inversedBy: "players")]
    #[ORM\JoinColumn(name: "quest_id", referencedColumnName: "id")]
    private $quest;

    #[ORM\Column(name: "tracking", type: "json", nullable: true)]
    private $tracking = [];

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @param Player $player
     */
    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    /**
     * @return Quest
     */
    public function getQuest(): Quest
    {
        return $this->quest;
    }

    /**
     * @param Quest $quest
     */
    public function setQuest(Quest $quest): void
    {
        $this->quest = $quest;
    }

    /**
     * @return array
     */
    public function getTracking(): array
    {
        return $this->tracking;
    }

    /**
     * @param array $tracking
     */
    public function setTracking(array $tracking): void
    {
        $this->tracking = $tracking;
    }
}
