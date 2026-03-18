<?php

namespace App\Dto\Skill;

use App\Dto\Domain\DomainModel;

class SkillModel extends SkillModelLight
{
    /**
     * @var DomainModel|null
     */
    public $domain;

    /**
     * @var DomainModel[]
     */
    public array $domains = [];

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
