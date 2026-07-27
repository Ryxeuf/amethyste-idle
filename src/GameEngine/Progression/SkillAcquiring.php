<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Skill;
use App\GameEngine\Player\PlayerActionHelper;
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
        private readonly PlayerActionHelper $playerActionHelper,
    ) {
    }

    /**
     * Apprend une competence, ou dit pourquoi elle ne peut pas l'etre.
     *
     * Retournait `void` : un refus etait silencieux, et l'appelant felicitait le
     * joueur pour une acquisition qui n'avait pas eu lieu.
     */
    public function acquireSkill(Skill $skill): SkillAcquisitionResult
    {
        $refusal = $this->skillHelper->refusalFor($skill);
        if (null !== $refusal) {
            return SkillAcquisitionResult::refused($refusal);
        }

        $player = $this->playerHelper->getPlayer();
        $player->addSkill($skill);

        // XP 100% à chaque domaine de la compétence
        $this->crossDomainSkillResolver->grantXpToAllDomains($player, $skill);

        $player->setLife($player->getLife() + $skill->getLife());
        $player->setMaxLife($player->getMaxLife() + $skill->getLife());

        // Synchroniser les emplacements d'outils débloqués
        $this->playerActionHelper->syncToolSlots();

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return SkillAcquisitionResult::acquired();
    }
}
