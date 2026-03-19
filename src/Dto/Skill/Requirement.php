<?php

namespace App\Dto\Skill;

use App\Dto\Domain\DomainModel;
use App\Entity\Game\Skill;

class Requirement extends SkillModelLight
{
    /** @var DomainModel */
    public $domain;

    /** @var DomainModel[] */
    public array $domains = [];

    public function __construct(Skill $skill)
    {
        parent::__construct($skill);

        $firstDomain = $skill->getDomain();
        if ($firstDomain !== null) {
            $this->domain = new DomainModel($firstDomain);
        }
        foreach ($skill->getDomains() as $domain) {
            $this->domains[] = new DomainModel($domain);
        }
    }
}
