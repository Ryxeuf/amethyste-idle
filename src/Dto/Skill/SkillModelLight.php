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

    /**
     * @var int
     */
    public $damage = 0;

    /**
     * @var int
     */
    public $heal = 0;

    /**
     * @var int
     */
    public $hit = 0;

    /**
     * @var int
     */
    public $critical = 0;

    /**
     * @var int
     */
    public $life = 0;

    /**
     * Ce que les nœuds passifs rapportent, dit a l'ecran (ARC-12b).
     *
     * Vide tant que le nœud ne porte aucun levier — ce qui est le cas de tous
     * les nœuds livres : ARC-03a a pose la colonne, ARC-07/08 l'ecrira. Le
     * remplir se fait par `SkillLeverPresenter`, jamais dans ce constructeur :
     * un DTO ne lit pas une grammaire.
     *
     * @var list<\App\GameEngine\Progression\SkillLeverReadout>
     */
    public array $leverReadouts = [];

    public function __construct(Skill $skill)
    {
        $this->id = $skill->getId();
        $this->title = $skill->getTitle();
        $this->requiredPoints = $skill->getRequiredPoints();
        $this->description = $skill->getDescription();
        $this->damage = $skill->getDamage();
        $this->heal = $skill->getHeal();
        $this->hit = $skill->getHit();
        $this->critical = $skill->getCritical();
        $this->life = $skill->getLife();
    }
}
