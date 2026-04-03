<?php

namespace App\Helper;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Repository\DomainExperienceRepository;

class PlayerDomainHelper
{
    /** @var DomainExperience[]|null Cache des domain experiences pré-chargées */
    private ?array $cachedDomainExperiences = null;
    private ?int $cachedPlayerId = null;

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly DomainExperienceRepository $domainExperienceRepository,
    ) {
    }

    public function getDomainExperience(Domain $domain, ?Player $character = null): ?DomainExperience
    {
        // Si un joueur spécifique est passé, utiliser le chemin classique
        if ($character !== null) {
            foreach ($character->getDomainExperiences() as $domainExperience) {
                if ($domainExperience->getDomain() === $domain) {
                    return $domainExperience;
                }
            }

            return null;
        }

        // Pour le joueur courant, utiliser le cache pré-chargé avec JOIN FETCH
        foreach ($this->getPreloadedDomainExperiences() as $domainExperience) {
            if ($domainExperience->getDomain() === $domain) {
                return $domainExperience;
            }
        }

        return null;
    }

    public function getAvailableDomainExperience(Domain $domain, ?Player $character = null): int
    {
        $domainExperience = $this->getDomainExperience($domain, $character);

        return $domainExperience?->getAvailableExperience() ?? 0;
    }

    /**
     * @return Domain[]
     */
    public function getDomains(): array
    {
        $domains = [];
        foreach ($this->getPreloadedDomainExperiences() as $domainExperience) {
            $domains[] = $domainExperience->getDomain();
        }

        return $domains;
    }

    /**
     * Pré-charge toutes les DomainExperience avec Domain + Skills via JOIN FETCH.
     * Élimine les requêtes N+1 sur la page skills.
     *
     * @return DomainExperience[]
     */
    private function getPreloadedDomainExperiences(): array
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return [];
        }

        if ($this->cachedDomainExperiences !== null && $this->cachedPlayerId === $player->getId()) {
            return $this->cachedDomainExperiences;
        }

        $this->cachedDomainExperiences = $this->domainExperienceRepository->findByPlayerWithDomainsAndSkills($player);
        $this->cachedPlayerId = $player->getId();

        return $this->cachedDomainExperiences;
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

    /**
     * Retourne TOUS les domaines associés à un skill ayant l'action donnée.
     *
     * @return Domain[]
     */
    public function getDomainsBySkillAction(string $action, array $options = []): array
    {
        $domains = [];
        foreach ($this->playerHelper->getPlayer()->getSkills() as $skill) {
            $actions = $skill->getActions() ?? [];
            foreach ($actions as $playerAction) {
                if ($action === ($playerAction['action'] ?? null)) {
                    $match = false;
                    switch ($action) {
                        case 'harvest':
                            if (null !== $spot = $options['spot'] ?? null) {
                                if (in_array($spot, $playerAction['spots'])) {
                                    $match = true;
                                }
                            }
                            break;
                        default:
                            $match = true;
                    }
                    if ($match) {
                        foreach ($skill->getDomains() as $domain) {
                            $domains[$domain->getId()] = $domain;
                        }
                    }
                }
            }
        }

        return array_values($domains);
    }
}
