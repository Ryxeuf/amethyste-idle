<?php

namespace App\Transformer;

use App\Dto\Skill\SkillModel;
use App\Entity\Game\Skill as SkillEntity;

class AbstractSkillTransformer
{
    protected function setRequirements(SkillModel $output, SkillEntity $skill): void
    {
        if ($skill->getRequirements()) {
            foreach ($skill->getRequirements() as $requirement) {
                $output->requirements[] = new SkillModel($requirement);
                $output->requirementIds[] = $requirement->getId();
            }
        }
    }

    protected function setAchievements(SkillModel $output, SkillEntity $skill): void
    {
        if ($skill->getAchievements()) {
            foreach ($skill->getAchievements() as $achievement) {
                $output->achievements[] = new SkillModel($achievement);
                $output->achievementIds[] = $achievement->getId();
            }
        }
    }

}