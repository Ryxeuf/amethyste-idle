<?php

namespace App\Repository;

use App\Entity\App\Map;
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
     * Zone rattachee a une carte TMX d'origine (transition pivot PBBG, ZON-03).
     *
     * **L'ordre est la correction.** Plusieurs zones peuvent partager une carte
     * — le Fanal et son Quartier des Jardins —, et ce `findOneBy` n'en nommait
     * aucune : PostgreSQL rendait la premiere ligne venue, c'est-a-dire l'ordre
     * physique, c'est-a-dire un ordre qui bouge des qu'une zone est mise a jour
     * (donc a chaque `app:zone:import`). Les habitants du Fanal poses par les
     * fixtures sont ainsi partis dans le lotissement voisin, et l'ecran de zone
     * listant strictement par zone, ils ont disparu du jeu sans qu'une seule
     * erreur ne soit levee.
     *
     * `sourceMapPrimary` tranche ; `id` reste en second critere pour que le
     * resultat soit stable meme sur une base qui n'a pas encore ete reimportee.
     */
    public function findEnabledBySourceMap(Map $map): ?Zone
    {
        return $this->findOneBy(
            ['sourceMap' => $map, 'enabled' => true],
            ['sourceMapPrimary' => 'DESC', 'id' => 'ASC'],
        );
    }

    /**
     * Zone de depart plausible, en dernier recours.
     *
     * Sert quand ni la zone courante, ni la carte de rattachement, ni le hub
     * declare ne donnent de position : mieux vaut poser le joueur dans une
     * ville sure que de le laisser sans zone, ou plus aucune action n'existe.
     * Un donjon n'est jamais choisi — on n'y nait pas.
     */
    public function findDefaultStartingZone(): ?Zone
    {
        foreach ([['type' => Zone::TYPE_CITY, 'isSafe' => true], ['type' => Zone::TYPE_CITY]] as $criteria) {
            $zone = $this->findOneBy($criteria + ['enabled' => true], ['name' => 'ASC']);
            if (null !== $zone) {
                return $zone;
            }
        }

        return $this->createQueryBuilder('z')
            ->andWhere('z.enabled = true')
            ->andWhere('z.type != :dungeon')
            ->setParameter('dungeon', Zone::TYPE_DUNGEON)
            ->orderBy('z.name', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
