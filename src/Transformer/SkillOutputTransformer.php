<?php

namespace App\Transformer;

use App\Dto\Skill\SkillModel;
use App\Entity\Game\Skill as SkillEntity;
use App\GameEngine\Progression\SkillLeverPresenter;

class SkillOutputTransformer extends AbstractSkillTransformer
{
    public function __construct(
        private readonly SkillLeverPresenter $leverPresenter,
    ) {
    }

    public function transform(SkillEntity $skill): SkillModel
    {
        $output = new SkillModel($skill);

        $this->setRequirements($output, $skill);
        $this->setAchievements($output, $skill);

        // ARC-12b — ce que le nœud rapporterait **si sa condition etait
        // remplie**, et ce qu'il faudrait porter. Sans cette ligne, un passif
        // conditionnel est indiscernable d'un passif mort : le joueur voit un
        // chiffre qui ne bouge pas et conclut que le nœud ne sert a rien.
        $output->leverReadouts = $this->leverPresenter->readoutsOf($skill, $skill->getDomain()?->getRegister());

        return $output;
    }
}
