<?php

namespace App\Dto\Quest;

use App\Entity\App\PlayerQuest;

class PlayerQuestModel extends QuestModel
{
    public $tracking = [];

    public $completed = false;

    public $progress = 0;

    public function __construct(PlayerQuest $playerQuest)
    {
        parent::__construct($playerQuest->getQuest());
    }
}
