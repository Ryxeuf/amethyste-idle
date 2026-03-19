<?php

namespace App\GameEngine\Progression;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Helper\PlayerDomainHelper;
use Doctrine\ORM\EntityManagerInterface;

class CrossDomainSkillResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerDomainHelper $playerDomainHelper,
    ) {
    }

    /**
     * Vérifie si un joueur peut débloquer automatiquement une compétence multi-domaine.
     * Auto-unlock = le joueur a assez d'XP dans AU MOINS UN des domaines de la compétence.
     */
    public function checkAutoUnlock(Player $player, Skill $skill): bool
    {
        foreach ($skill->getDomains() as $domain) {
            $available = $this->playerDomainHelper->getAvailableDomainExperience($domain, $player);
            if ($available >= $skill->getRequiredPoints()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Accorde l'XP utilisée à TOUS les domaines de la compétence (100% chaque).
     * Crée les DomainExperience manquants.
     *
     * @return DomainExperience[] les domain experiences modifiées
     */
    public function grantXpToAllDomains(Player $player, Skill $skill): array
    {
        $domainExperiences = [];

        foreach ($skill->getDomains() as $domain) {
            $domainExperience = $this->getOrCreateDomainExperience($player, $domain);

            $domainExperience->setUsedExperience(
                $domainExperience->getUsedExperience() + $skill->getRequiredPoints()
            );
            $domainExperience->setHit($domainExperience->getHit() + $skill->getHit());
            $domainExperience->setCritical($domainExperience->getCritical() + $skill->getCritical());
            $domainExperience->setDamage($domainExperience->getDamage() + $skill->getDamage());
            $domainExperience->setHeal($domainExperience->getHeal() + $skill->getHeal());

            $this->entityManager->persist($domainExperience);
            $domainExperiences[] = $domainExperience;
        }

        return $domainExperiences;
    }

    private function getOrCreateDomainExperience(Player $player, Domain $domain): DomainExperience
    {
        $domainExperience = $this->playerDomainHelper->getDomainExperience($domain, $player);

        if ($domainExperience === null) {
            $domainExperience = new DomainExperience();
            $domainExperience->setPlayer($player);
            $domainExperience->setDomain($domain);
            $player->addDomainExperience($domainExperience);
        }

        return $domainExperience;
    }
}
