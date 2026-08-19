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
        private readonly PortAccessDiscount $portAccessDiscount,
    ) {
    }

    /**
     * Vérifie si un joueur peut débloquer automatiquement une compétence multi-domaine.
     * Auto-unlock = le joueur a assez d'XP dans AU MOINS UN des domaines de la compétence.
     */
    public function checkAutoUnlock(Player $player, Skill $skill): bool
    {
        $cost = $this->portAccessDiscount->effectiveRequiredPointsOf($player, $skill);
        foreach ($skill->getDomains() as $domain) {
            $available = $this->playerDomainHelper->getAvailableDomainExperience($domain, $player);
            if ($available >= $cost) {
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

        // ARC-16b : la depense lit le meme cout que le refus — remise
        // d'accointance comprise. Un cout verifie a 25 et debite a 50 serait
        // le pire des mensonges, celui qu'on ne decouvre qu'a son solde.
        $cost = $this->portAccessDiscount->effectiveRequiredPointsOf($player, $skill);

        foreach ($skill->getDomains() as $domain) {
            $domainExperience = $this->getOrCreateDomainExperience($player, $domain);

            $domainExperience->setUsedExperience(
                $domainExperience->getUsedExperience() + $cost
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
