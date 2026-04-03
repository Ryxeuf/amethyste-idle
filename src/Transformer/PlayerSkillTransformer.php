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

        $firstDomain = $skill->getDomain();
        if ($firstDomain !== null) {
            $output->domain = new DomainModel($firstDomain);
        }
        foreach ($skill->getDomains() as $domain) {
            $output->domains[] = new DomainModel($domain);
        }

        $this->setRequirements($output, $skill);
        $this->setAchievements($output, $skill);

        $player = $this->playerHelper->getPlayer();
        $output->acquired = $player->hasSkill($skill);

        // Multi-domaine : vérifier l'XP disponible dans au moins un des domaines
        foreach ($skill->getDomains() as $domain) {
            foreach ($domain->getPlayerExperiences() as $playerExperience) {
                if ($playerExperience->getPlayer() === $player) {
                    if ($playerExperience->getAvailableExperience() >= $skill->getRequiredPoints()) {
                        $output->canBeAcquired = true;
                        break 2;
                    }
                }
            }
        }

        return $output;
    }

    protected function setRequirements(SkillModel $output, SkillEntity $skill): void
    {
        $player = $this->playerHelper->getPlayer();
        foreach ($skill->getRequirements() as $requirement) {
            $model = new SkillPlayer($requirement);
            $model->acquired = $player->hasSkill($requirement);
            $output->requirements[] = $model;
            $output->requirementIds[] = $requirement->getId();
        }
    }
}
