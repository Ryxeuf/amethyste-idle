<?php

namespace App\Dto\Quest;

use App\Entity\App\PlayerQuestCompleted;

class PlayerQuestCompletedModel
{
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var \DateTime|null */
    public $completedAt;

    public function __construct(PlayerQuestCompleted $playerQuestCompleted)
    {
        $this->id = $playerQuestCompleted->getQuest()->getId();
        $this->name = $playerQuestCompleted->getQuest()->getName();
        $this->completedAt = $playerQuestCompleted->getCreatedAt();
    }
}
