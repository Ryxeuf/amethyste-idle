<?php

namespace App\Repository;

use App\Entity\App\Zone;
use App\Entity\App\ZoneVein;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ZoneVein>
 */
class ZoneVeinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZoneVein::class);
    }

    /**
     * Etat du filon partage d'une ressource dans une zone (null si jamais
     * recolte — le stock est alors considere plein par le service).
     */
    public function findOneByZoneAndSlug(Zone $zone, string $slug): ?ZoneVein
    {
        return $this->findOneBy(['zone' => $zone, 'slug' => $slug]);
    }
}
