<?php

namespace App\Repository;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mob>
 */
class MobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mob::class);
    }

    /**
     * Mobs rencontrables d'une zone (vivants, hors combat), Monster hydrate.
     * Pivot PBBG (ZON-08) : vivier des rencontres de l'action Explorer.
     *
     * @return list<Mob>
     */
    public function findAvailableInZone(Zone $zone, int $limit = 25): array
    {
        return $this->createQueryBuilder('mob')
            ->join('mob.monster', 'monster')->addSelect('monster')
            ->andWhere('mob.zone = :zone')
            ->andWhere('mob.fight IS NULL')
            ->andWhere('mob.life > 0')
            ->setParameter('zone', $zone)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Charge les mobs d'une carte avec leur Monster (ManyToOne) hydrate.
     *
     * Volontairement sans `leftJoin` sur les OneToMany de Monster (`spells`,
     * `monsterItems` et leur `item`) : le seul appelant (`/api/map/entities`)
     * n'utilise que `getName()`, `getSlug()`, `isWorldBoss()`, `isNocturnal()`
     * et `getSpawnWeather()`. Les joins OneToMany generaient un produit
     * cartesien (3 spells x 5 items = 15 lignes par mob sur la wire) sans
     * benefice fonctionnel.
     *
     * @return Mob[]
     */
    public function findByMapWithMonster(Map $map): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.monster', 'mon')->addSelect('mon')
            ->where('m.map = :map')
            ->setParameter('map', $map)
            ->getQuery()
            ->getResult();
    }
}
