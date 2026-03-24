<?php

namespace App\Entity\App;

use App\Entity\Game\Quest;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'daily_quest_selection')]
#[ORM\UniqueConstraint(name: 'daily_quest_selection_unique', columns: ['quest_id', 'date'])]
class DailyQuestSelection
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quest::class)]
    #[ORM\JoinColumn(name: 'quest_id', referencedColumnName: 'id', nullable: false)]
    private Quest $quest;

    #[ORM\Column(name: 'date', type: 'date')]
    private \DateTimeInterface $date;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuest(): Quest
    {
        return $this->quest;
    }

    public function setQuest(Quest $quest): self
    {
        $this->quest = $quest;

        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
