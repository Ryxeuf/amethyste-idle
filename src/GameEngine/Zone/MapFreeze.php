<?php

namespace App\GameEngine\Zone;

use App\Entity\User;
use App\Repository\FeatureFlagRepository;
use App\Service\FeatureFlagManager;

/**
 * Gel de la carte navigable (pivot PBBG, ZON-01), pilote par le feature flag
 * `map_frozen` — gel avant suppression (ZON-21), reversible sans deploiement.
 *
 * Deux lectures :
 *  - isFrozenFor(user) : gel effectif pour UN joueur (flag global OU active
 *    pour ce user) — pages et endpoints d'action. Permet de geler la carte
 *    pour les testeurs d'abord.
 *  - isGloballyFrozen() : gel pour tout le monde — suspension des publications
 *    Mercure map/move / map/respawn (flux globaux, pas de notion de user).
 */
class MapFreeze
{
    public const FLAG = 'map_frozen';

    public function __construct(
        private readonly FeatureFlagManager $featureFlagManager,
        private readonly FeatureFlagRepository $featureFlagRepository,
    ) {
    }

    public function isFrozenFor(?User $user = null): bool
    {
        return $this->featureFlagManager->isEnabled(self::FLAG, $user);
    }

    public function isGloballyFrozen(): bool
    {
        return $this->featureFlagRepository->findOneBySlug(self::FLAG)?->isEnabled() ?? false;
    }
}
