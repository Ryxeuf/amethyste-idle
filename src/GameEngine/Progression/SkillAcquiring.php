<?php

namespace App\GameEngine\Progression;

use App\Entity\App\DomainExperience;
use App\Entity\Game\Skill;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\ORM\EntityManagerInterface;

class SkillAcquiring
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PlayerDomainHelper $playerDomainHelper, private readonly PlayerHelper $playerHelper, private readonly PlayerSkillHelper $skillHelper)
    {
    }

    public function acquireSkill(Skill $skill): void
    {
        if ($this->skillHelper->canAcquireSkill($skill)) {
            $player = $this->playerHelper->getPlayer();
            if (!$domainExperience = $this->playerDomainHelper->getDomainExperience($skill->getDomain())) {
                $domainExperience = new DomainExperience();
                $domainExperience->setPlayer($player);
                $domainExperience->setDomain($skill->getDomain());

                $player->addDomainExperience($domainExperience);
            }
            $player->addSkill($skill);
            $domainExperience->setUsedExperience($domainExperience->getUsedExperience() + $skill->getRequiredPoints());
            $domainExperience->setHit($domainExperience->getHit() + $skill->getHit());
            $domainExperience->setCritical($domainExperience->getCritical() + $skill->getCritical());
            $domainExperience->setDamage($domainExperience->getDamage() + $skill->getDamage());
            $domainExperience->setHeal($domainExperience->getHeal() + $skill->getHeal());
            $player->setLife($player->getLife() + $skill->getLife());
            $player->setMaxLife($player->getMaxLife() + $skill->getLife());

            $this->entityManager->persist($player);
            $this->entityManager->persist($domainExperience);
            $this->entityManager->flush();
        }
    }
}