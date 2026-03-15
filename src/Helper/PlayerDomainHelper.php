<?php

namespace App\Helper;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;

class PlayerDomainHelper
{
    public function __construct(private readonly PlayerHelper $playerHelper)
    {
    }

    public function getDomainExperience(Domain $domain, ?Player $character = null): ?DomainExperience
    {
        $player = $character ?? $this->playerHelper->getPlayer();
        foreach ($player->getDomainExperiences() as $domainExperience) {
            if ($domainExperience->getDomain() === $domain) {
                return $domainExperience;
            }
        }

        return null;
    }

    public function getAvailableDomainExperience(Domain $domain, ?Player $character = null): int
    {
        $player = $character ?? $this->playerHelper->getPlayer();
        foreach ($player->getDomainExperiences() as $domainExperience) {
            if ($domainExperience->getDomain() === $domain) {
                return $domainExperience->getAvailableExperience();
            }
        }

        return 0;
    }

    /**
     * @return Domain[]
     */
    public function getDomains(): array
    {
        $domains = [];
        foreach ($this->playerHelper->getPlayer()->getDomainExperiences() as $domainExperience) {
            $domains[] = $domainExperience->getDomain();
        }

        return $domains;
    }

    public function getDomainBySkillAction(string $action, array $options = []): ?Domain
    {
        foreach ($this->playerHelper->getPlayer()->getSkills() as $skill) {
            $actions = $skill->getActions() ?? [];
            foreach ($actions as $playerAction) {
                if ($action === ($playerAction['action'] ?? null)) {
                    switch ($action) {
                        // Dans le cas d'un harvest, on check le spot
                        case 'harvest':
                            if (null !== $spot = $options['spot'] ?? null) {
                                if (in_array($spot, $playerAction['spots'])) {
                                    return $skill->getDomain();
                                }
                            }
                            break;
                        default:
                            return $skill->getDomain();
                    }
                }
            }

        }

        return null;
    }
}
