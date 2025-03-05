<?php

namespace App\Dto\Skill;

use App\Dto\Domain\DomainModel;

class SkillModel extends SkillModelLight
{
    /**
     * @var DomainModel
     */
    public $domain;

    /**
     * @var SkillModel[]
     */
    public $requirements = [];

    /**
     * @var SkillModel[]
     */
    public $achievements = [];

    /**
     * @var array
     */
    public $requirementIds;

    /**
     * @var array
     */
    public $achievementIds;
}
