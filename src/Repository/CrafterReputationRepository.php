<?php

namespace App\Repository;

use App\Entity\App\CrafterReputation;
use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CrafterReputation>
 */
class CrafterReputationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrafterReputation::class);
    }

    public function findOneForPlayerAndCraft(Player $player, string $craft): ?CrafterReputation
    {
        return $this->findOneBy(['player' => $player, 'craft' => $craft]);
    }

    /**
     * Les reputations d'un artisan, tous metiers confondus.
     *
     * @return CrafterReputation[]
     */
    public function findForPlayer(Player $player): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.player = :player')
            ->setParameter('player', $player)
            ->orderBy('c.points', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Classement d'un metier — ce que consulte un client avant d'adresser une
     * commande directe (ECO-07b).
     *
     * @return CrafterReputation[]
     */
    public function findTopByCraft(?string $craft = null, int $limit = 25): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.player', 'p')->addSelect('p')
            ->orderBy('c.points', 'DESC')
            ->addOrderBy('c.deliveries', 'DESC')
            ->setMaxResults(max(1, $limit));

        if (null !== $craft && '' !== $craft) {
            $qb->andWhere('c.craft = :craft')->setParameter('craft', $craft);
        }

        return $qb->getQuery()->getResult();
    }
}
