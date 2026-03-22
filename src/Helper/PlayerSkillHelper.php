<?php

namespace App\Helper;

use App\Entity\App\Player;
use App\Entity\Game\Skill;

class PlayerSkillHelper
{
    public const int MAX_TOTAL_SKILL_POINTS = 500;

    public function __construct(private readonly PlayerHelper $playerHelper, private readonly PlayerDomainHelper $playerDomainHelper)
    {
    }

    public function canAcquireSkill(Skill $skill): bool
    {
        if ($this->hasSkill($skill)) {
            return false;
        }

        $player = $this->playerHelper->getPlayer();
        $requirements = $skill->getRequirements()->toArray();

        // Limite globale multi-domaine
        if ($this->getTotalUsedPoints($player) + $skill->getRequiredPoints() > self::MAX_TOTAL_SKILL_POINTS) {
            return false;
        }

        // Multi-domaine : il faut assez de points dans AU MOINS UN des domaines
        $hasEnoughPoints = false;
        foreach ($skill->getDomains() as $domain) {
            if ($this->playerDomainHelper->getAvailableDomainExperience($domain, $player) >= $skill->getRequiredPoints()) {
                $hasEnoughPoints = true;
                break;
            }
        }

        $playerRequirementsMatching = array_intersect($player->getSkills()->toArray(), $requirements);
        $playerMeetsRequirements = count($requirements) === count($playerRequirementsMatching);

        return $hasEnoughPoints && $playerMeetsRequirements;
    }

    public function getTotalUsedPoints(?Player $player = null): int
    {
        $player = $player ?? $this->playerHelper->getPlayer();
        $total = 0;
        foreach ($player->getDomainExperiences() as $domainExperience) {
            $total += $domainExperience->getUsedExperience();
        }

        return $total;
    }

    public function hasSkill(Skill $skill): bool
    {
        $player = $this->playerHelper->getPlayer();
        // On vérifie que le skill n'a pas déjà été appris
        foreach ($player->getSkills() as $playerSkill) {
            if ($playerSkill === $skill) {
                return true;
            }
        }

        return false;
    }
}
