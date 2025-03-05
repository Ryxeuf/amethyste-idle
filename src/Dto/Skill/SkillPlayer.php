<?php

namespace App\Dto\Skill;

class SkillPlayer extends SkillModel
{
    /** @var bool */
    public $canBeAcquired = false;

    /** @var bool */
    public $acquired = false;
}
