<?php

namespace App\Entity\App;

use App\Entity\Game\Quest;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\Table(name: 'player_quest')]
#[ORM\UniqueConstraint(name: 'player_quest_unique', columns: ['player_id', 'quest_id'])]
#[UniqueEntity(
    fields: ['quest', 'player'],
    message: 'The player is currently making the quest.'
)]
class PlayerQuest
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'quests')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id')]
    private $player;

    #[ORM\ManyToOne(targetEntity: Quest::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'quest_id', referencedColumnName: 'id')]
    private $quest;

    #[ORM\Column(name: 'tracking', type: 'json', nullable: true)]
    private $tracking = [];

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    public function getQuest(): Quest
    {
        return $this->quest;
    }

    public function setQuest(Quest $quest): void
    {
        $this->quest = $quest;
    }

    public function getTracking(): array
    {
        return $this->tracking;
    }

    public function setTracking(array $tracking): void
    {
        $this->tracking = $tracking;
    }
}
