<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use Doctrine\ORM\EntityManagerInterface;

class SkillRespecManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getRespecCost(Player $player): int
    {
        $skillCount = $player->getSkills()->count();

        if ($skillCount === 0) {
            return 0;
        }

        $baseCost = 50 * $skillCount;
        $multiplier = 1.25 ** $player->getRespecCount();

        return (int) ceil($baseCost * $multiplier);
    }

    public function canRespec(Player $player): bool
    {
        if ($player->getSkills()->isEmpty()) {
            return false;
        }

        if ($player->getFight() !== null) {
            return false;
        }

        return $player->getGils() >= $this->getRespecCost($player);
    }

    public function respec(Player $player): bool
    {
        if (!$this->canRespec($player)) {
            return false;
        }

        $cost = $this->getRespecCost($player);

        // Retirer les gils
        $player->removeGils($cost);

        // Calculer le total de vie bonus des skills
        $totalLifeBonus = 0;
        foreach ($player->getSkills() as $skill) {
            $totalLifeBonus += $skill->getLife();
        }

        // Retirer tous les skills
        foreach ($player->getSkills()->toArray() as $skill) {
            $player->removeSkill($skill);
        }

        // Rembourser l'XP et les stats sur chaque DomainExperience
        foreach ($player->getDomainExperiences() as $domainExperience) {
            $domainExperience->setUsedExperience(0);
            $domainExperience->setDamage(0);
            $domainExperience->setHeal(0);
            $domainExperience->setHit(0);
            $domainExperience->setCritical(0);
        }

        // Retirer le bonus de vie des skills
        $player->setMaxLife(max(1, $player->getMaxLife() - $totalLifeBonus));
        $player->setLife(min($player->getLife(), $player->getMaxLife()));

        // Incrementer le compteur de respec
        $player->incrementRespecCount();

        $this->entityManager->flush();

        return true;
    }
}
