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
     * Le registre de combat du domaine, ou `null` pour un metier (DOM-01).
     */
    public ?string $register;

    /**
     * Le build porte-t-il une source de ce domaine ? (DOM-02).
     *
     * `null` pour un metier : la question ne le concerne pas, et repondre
     * « non » ferait afficher « inactif » sur l'arbre du mineur — ce qui se
     * lirait comme une sanction alors que c'est un hors-sujet.
     */
    public ?bool $activeInBuild = null;

    /**
     * Ce qu'il faudrait porter pour l'exprimer, quand il ne l'est pas.
     *
     * L'ecran doit dire **quoi faire**, pas seulement constater. Un « inactif »
     * sans suite serait exactement l'etat vide que le systeme de design refuse.
     */
    public ?string $activationHint = null;

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
        $this->register = $domain->getRegister()?->value;
        $this->entity = $domain;
        $this->skills = [];
    }
}
