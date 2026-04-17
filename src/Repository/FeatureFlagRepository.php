<?php

namespace App\Repository;

use App\Entity\App\FeatureFlag;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeatureFlag>
 */
class FeatureFlagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureFlag::class);
    }

    public function findOneBySlug(string $slug): ?FeatureFlag
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @return FeatureFlag[]
     */
    public function findGloballyEnabled(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.enabled = true')
            ->orderBy('f.slug', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Flags accessibles pour un user : soit actives globalement, soit assignees au user.
     *
     * @return FeatureFlag[]
     */
    public function findEnabledForUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.users', 'u')
            ->where('f.enabled = true OR u.id = :uid')
            ->setParameter('uid', $user->getId())
            ->orderBy('f.slug', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
