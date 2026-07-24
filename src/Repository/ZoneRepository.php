<?php

namespace App\Repository;

use App\Entity\App\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Zone>
 */
class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    public function findEnabledBySlug(string $slug): ?Zone
    {
        return $this->findOneBy(['slug' => $slug, 'enabled' => true]);
    }

    /**
     * @return list<Zone>
     */
    public function findAllEnabled(): array
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.enabled = true')
            ->orderBy('z.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
