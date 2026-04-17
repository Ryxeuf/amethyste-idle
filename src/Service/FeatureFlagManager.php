<?php

namespace App\Service;

use App\Entity\App\FeatureFlag;
use App\Entity\User;
use App\Repository\FeatureFlagRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Gere l'etat des feature flags : activation globale ou par utilisateur.
 *
 * Regles de resolution :
 *  - Un flag `enabled = true` est actif pour tout le monde.
 *  - Un flag `enabled = false` est actif uniquement pour les users explicitement assignes.
 *  - Un flag inexistant est toujours inactif.
 */
class FeatureFlagManager
{
    /**
     * Cache en memoire par requete : slug => bool (pour l'user courant).
     *
     * @var array<string, bool>
     */
    private array $cache = [];

    public function __construct(
        private readonly FeatureFlagRepository $repository,
        private readonly Security $security,
    ) {
    }

    /**
     * Verifie si le flag est actif pour un user donne (ou le user courant si null).
     */
    public function isEnabled(string $slug, ?User $user = null): bool
    {
        $user ??= $this->currentUser();

        $cacheKey = $slug . ':' . ($user?->getId() ?? 'anon');
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $flag = $this->repository->findOneBySlug($slug);
        if (null === $flag) {
            return $this->cache[$cacheKey] = false;
        }

        if ($flag->isEnabled()) {
            return $this->cache[$cacheKey] = true;
        }

        if (null === $user) {
            return $this->cache[$cacheKey] = false;
        }

        return $this->cache[$cacheKey] = $flag->hasUser($user);
    }

    /**
     * Liste tous les flags actifs globalement.
     *
     * @return FeatureFlag[]
     */
    public function getGloballyEnabled(): array
    {
        return $this->repository->findGloballyEnabled();
    }

    /**
     * Liste tous les flags actifs pour un user (actifs globalement + actifs specifiquement pour lui).
     *
     * @return FeatureFlag[]
     */
    public function getEnabledForUser(?User $user = null): array
    {
        $user ??= $this->currentUser();
        if (null === $user) {
            return $this->getGloballyEnabled();
        }

        return $this->repository->findEnabledForUser($user);
    }

    /**
     * Vide le cache memoire (utile en tests ou apres modification).
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    private function currentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }
}
