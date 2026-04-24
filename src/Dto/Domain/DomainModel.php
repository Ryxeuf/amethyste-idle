<?php

namespace App\Dto\Domain;

use App\Dto\Skill\SkillModel;
use App\Entity\Game\Domain;

class DomainModel
{
    public int $id;
    public string $title;
    public string $slug;
    public ?string $element;
    public readonly Domain $entity;

    /**
     * @var SkillModel[]
     */
    public array $skills;

    public function __construct(Domain $domain)
    {
        $this->id = $domain->getId();
        $this->title = $domain->getTitle();
        $this->slug = $domain->getSlug();
        $this->element = $domain->getElement();
        $this->entity = $domain;
        $this->skills = [];
    }
}
