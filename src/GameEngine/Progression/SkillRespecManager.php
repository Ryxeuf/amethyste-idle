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

    /**
     * Les competences qu'un respec redistribue, c'est-a-dire celles qui ont
     * coute des points (ONB-20b).
     *
     * **Un nœud gratuit n'est pas redistribuable** : il n'a rien coute, donc il
     * n'y a rien a rembourser, et le retirer ne libere aucun point. Le retirer
     * quand meme etait un piege paye — les nœuds d'entree gratuits sont les
     * **echelons 1 de port** (ONB-20b) et les nœuds d'artisanat d'entree
     * (ECO-20). Un joueur qui payait un respec se retrouvait incapable de tenir
     * l'arme qu'il portait et sans aucune recette, pour un remboursement nul.
     *
     * @return list<\App\Entity\Game\Skill>
     */
    private function paidSkills(Player $player): array
    {
        $paid = [];
        foreach ($player->getSkills() as $skill) {
            if ($skill->getRequiredPoints() > 0) {
                $paid[] = $skill;
            }
        }

        return $paid;
    }

    public function getRespecCost(Player $player): int
    {
        $skillCount = \count($this->paidSkills($player));

        if ($skillCount === 0) {
            return 0;
        }

        $baseCost = 50 * $skillCount;
        $multiplier = 1.25 ** $player->getRespecCount();

        return (int) ceil($baseCost * $multiplier);
    }

    public function canRespec(Player $player): bool
    {
        if ($this->paidSkills($player) === []) {
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

        // Calculer le total de vie bonus des skills redistribues
        $paid = $this->paidSkills($player);

        $totalLifeBonus = 0;
        foreach ($paid as $skill) {
            $totalLifeBonus += $skill->getLife();
        }

        // Retirer les skills payes, et eux seuls : les nœuds d'entree gratuits
        // ne se remboursent pas, donc les retirer ne rend rien.
        foreach ($paid as $skill) {
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
