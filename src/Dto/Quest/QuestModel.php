<?php

namespace App\Dto\Quest;

use App\Entity\Game\Quest;

class QuestModel
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $description;
    /**
     * @var array
     */
    public $requirements;
    /**
     * @var array
     */
    public $rewards;

    public function __construct(Quest $quest)
    {
        $this->id = $quest->getId();
        $this->name = $quest->getName();
        $this->description = $quest->getDescription();
        $this->requirements = $quest->getRequirements();
        $this->rewards = $quest->getRewards();
    }
}
