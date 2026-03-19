<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Skill;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\ORM\EntityManagerInterface;

class SkillAcquiring
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerSkillHelper $skillHelper,
        private readonly CrossDomainSkillResolver $crossDomainSkillResolver,
    ) {
    }

    public function acquireSkill(Skill $skill): void
    {
        if ($this->skillHelper->canAcquireSkill($skill)) {
            $player = $this->playerHelper->getPlayer();
            $player->addSkill($skill);

            // XP 100% à chaque domaine de la compétence
            $this->crossDomainSkillResolver->grantXpToAllDomains($player, $skill);

            $player->setLife($player->getLife() + $skill->getLife());
            $player->setMaxLife($player->getMaxLife() + $skill->getLife());

            $this->entityManager->persist($player);
            $this->entityManager->flush();
        }
    }
}
