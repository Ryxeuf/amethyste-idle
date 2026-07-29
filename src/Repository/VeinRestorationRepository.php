<?php

namespace App\Repository;

use App\Entity\App\VeinRestoration;
use App\Entity\App\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VeinRestoration>
 */
class VeinRestorationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VeinRestoration::class);
    }

    /**
     * Le chantier en cours sur ce filon, s'il y en a un.
     *
     * C'est la garde d'idempotence de FOY-12 : un filon ne porte jamais deux
     * chantiers a la fois, sinon deux guildes paieraient pour le meme effet et
     * la seconde n'achererait rien.
     */
    public function findActive(Zone $zone, string $veinSlug, \DateTimeImmutable $now): ?VeinRestoration
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.zone = :zone')
            ->andWhere('r.veinSlug = :slug')
            ->andWhere('r.endsAt > :now')
            ->setParameter('zone', $zone)
            ->setParameter('slug', $veinSlug)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Les chantiers en cours sur toute la carte, indexes par `zoneId:slug`.
     *
     * Le tick quotidien passe sur tous les filons ; il lui faut donc une seule
     * requete plutot qu'une par filon.
     *
     * @return array<string, true>
     */
    public function activeKeys(\DateTimeImmutable $now): array
    {
        /** @var list<array{zoneId: int, veinSlug: string}> $rows */
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.zone) AS zoneId', 'r.veinSlug AS veinSlug')
            ->andWhere('r.endsAt > :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getArrayResult();

        $keys = [];
        foreach ($rows as $row) {
            $keys[self::key((int) $row['zoneId'], (string) $row['veinSlug'])] = true;
        }

        return $keys;
    }

    public static function key(int $zoneId, string $veinSlug): string
    {
        return $zoneId . ':' . $veinSlug;
    }

    /**
     * Les chantiers en cours dans une zone, indexes par slug de filon.
     *
     * @return array<string, VeinRestoration>
     */
    public function activeInZone(Zone $zone, \DateTimeImmutable $now): array
    {
        /** @var list<VeinRestoration> $rows */
        $rows = $this->createQueryBuilder('r')
            ->andWhere('r.zone = :zone')
            ->andWhere('r.endsAt > :now')
            ->setParameter('zone', $zone)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($rows as $restoration) {
            $indexed[$restoration->getVeinSlug()] = $restoration;
        }

        return $indexed;
    }
}
