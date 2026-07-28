<?php

namespace App\Repository;

use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * Energie d'action depensee par tous les personnages depuis toujours (FOY-17).
     *
     * C'est la matiere premiere du dimensionnement du monde. La requete est une
     * simple agregation, jouee une fois par jour par le tick : elle n'a pas
     * besoin d'etre plus fine, et la porter ici plutot que dans le service rend
     * ce dernier testable sans monter un faux QueryBuilder.
     */
    public function sumActionEnergySpent(): int
    {
        $sum = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.actionEnergySpentTotal), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return \is_numeric($sum) ? (int) $sum : 0;
    }
}
