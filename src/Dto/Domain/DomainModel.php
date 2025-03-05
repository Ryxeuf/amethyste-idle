<?php

namespace App\Dto\Domain;

use App\Dto\Skill\SkillModel;
use App\Entity\Game\Domain;

class DomainModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var SkillModel[]
     */
    public $skills;

    public function __construct(Domain $domain)
    {
        $this->id = $domain->getId();
        $this->title = $domain->getTitle();
    }

}