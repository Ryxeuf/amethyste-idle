<?php

namespace App\Repository;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DomainExperience>
 */
class DomainExperienceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainExperience::class);
    }

    /**
     * Charge toutes les DomainExperience d'un joueur avec Domain et Skills en une seule requête.
     * Élimine le N+1 sur getDomain() -> getSkills().
     *
     * @return DomainExperience[]
     */
    public function findByPlayerWithDomainsAndSkills(Player $player): array
    {
        return $this->createQueryBuilder('de')
            ->join('de.domain', 'd')->addSelect('d')
            ->leftJoin('d.skills', 's')->addSelect('s')
            ->where('de.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getResult();
    }
}
