<?php

namespace App\Transformer;

use App\Dto\Domain\DomainModel;
use App\Dto\Skill\SkillModel;
use App\Dto\Skill\SkillPlayer;
use App\Entity\Game\Skill as SkillEntity;
use App\Helper\PlayerHelper;

class PlayerSkillTransformer extends AbstractSkillTransformer
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function transform(SkillEntity $skill): SkillPlayer
    {
        $output = new SkillPlayer($skill);

        $output->domain = new DomainModel($skill->getDomain());

        $this->setRequirements($output, $skill);
        $this->setAchievements($output, $skill);

        $player = $this->playerHelper->getPlayer();
        $output->acquired = $player->hasSkill($skill);

        foreach ($skill->getDomain()->getPlayerExperiences() as $playerExperience) {
            if ($playerExperience->getPlayer() === $player) {
                $output->canBeAcquired = $playerExperience->getAvailableExperience() >= $skill->getRequiredPoints();
            }
        }

        return $output;
    }

    protected function setRequirements(SkillModel $output, SkillEntity $skill): void
    {
        $player = $this->playerHelper->getPlayer();
        if ($skill->getRequirements()) {
            foreach ($skill->getRequirements() as $requirement) {
                $model = new SkillPlayer($requirement);
                $model->acquired = $player->hasSkill($requirement);
                $output->requirements[] = $model;
                $output->requirementIds[] = $requirement->getId();
            }
        }
    }
}
