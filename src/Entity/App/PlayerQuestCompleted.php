<?php

namespace App\Entity\App;

use App\Entity\Game\Quest;
use App\Repository\PlayerQuestCompletedRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PlayerQuestCompletedRepository::class)]
#[ORM\Table(name: 'player_quest_completed')]
#[ORM\UniqueConstraint(name: 'player_quest_completed_unique', columns: ['player_id', 'quest_id'])]
#[UniqueEntity(
    fields: ['quest', 'player'],
    message: 'The player already completed the quest.'
)]
class PlayerQuestCompleted
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'completedQuests')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id')]
    private $player;

    #[ORM\ManyToOne(targetEntity: Quest::class)]
    #[ORM\JoinColumn(name: 'quest_id', referencedColumnName: 'id')]
    private $quest;

    #[ORM\Column(name: 'choice_made', type: 'string', length: 100, nullable: true)]
    private ?string $choiceMade = null;

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

    public function getChoiceMade(): ?string
    {
        return $this->choiceMade;
    }

    public function setChoiceMade(?string $choiceMade): void
    {
        $this->choiceMade = $choiceMade;
    }
}
