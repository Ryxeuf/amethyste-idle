<?php

namespace App\Dto\Skill;

use App\Dto\Domain\DomainModel;
use App\Entity\Game\Skill;

class Requirement extends SkillModelLight
{
    /** @var DomainModel */
    public $domain;

    public function __construct(Skill $skill)
    {
        parent::__construct($skill);

        $this->domain = new DomainModel($skill->getDomain());
    }
}
