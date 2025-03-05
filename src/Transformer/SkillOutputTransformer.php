<?php

namespace App\Transformer;

use App\Dto\Skill\SkillModel;
use App\Entity\Game\Skill as SkillEntity;

class SkillOutputTransformer extends AbstractSkillTransformer
{
    public function transform(SkillEntity $skill): SkillModel
    {
        $output = new SkillModel($skill);

        $this->setRequirements($output, $skill);
        $this->setAchievements($output, $skill);

        return $output;
    }
}
