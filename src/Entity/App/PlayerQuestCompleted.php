<?php

namespace App\Entity\App;

use App\Entity\Game\Quest;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: "player_quest_completed")]
#[ORM\UniqueConstraint(name: "player_quest_completed_unique", columns: ["player_id", "quest_id"])]
#[UniqueEntity(
    fields: ["quest", "player"],
    message: "The player already completed the quest."
)]
class PlayerQuestCompleted
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id", type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: "quests")]
    #[ORM\JoinColumn(name: "player_id", referencedColumnName: "id")]
    private $player;

    #[ORM\ManyToOne(targetEntity: Quest::class, inversedBy: "players")]
    #[ORM\JoinColumn(name: "quest_id", referencedColumnName: "id")]
    private $quest;

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
}
