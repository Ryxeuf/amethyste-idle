<?php

namespace App\Dto\Skill;

use App\Entity\Game\Skill;

class SkillModelLight
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $requiredPoints;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $description;

    public function __construct(Skill $skill)
    {
        $this->id = $skill->getId();
        $this->title = $skill->getTitle();
        $this->requiredPoints = $skill->getRequiredPoints();
        $this->description = $skill->getDescription();
    }
}
