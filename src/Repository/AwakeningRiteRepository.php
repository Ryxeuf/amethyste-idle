<?php

namespace App\Repository;

use App\Entity\App\AwakeningRite;
use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AwakeningRite>
 */
class AwakeningRiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AwakeningRite::class);
    }

    /**
     * Le rite en cours de ce joueur, s'il en a un.
     *
     * **Un seul a la fois** : le rite est exceptionnel, et pouvoir en empiler
     * dix en ferait une file de production. La regle est tenue par le service,
     * qui interroge cette methode avant d'en ouvrir un nouveau.
     */
    public function findPending(Player $player): ?AwakeningRite
    {
        return $this->findOneBy(['player' => $player, 'claimedAt' => null], ['endsAt' => 'ASC']);
    }

    /**
     * @return list<AwakeningRite>
     */
    public function findClaimed(Player $player): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.player = :player')
            ->andWhere('r.claimedAt IS NOT NULL')
            ->setParameter('player', $player)
            ->orderBy('r.claimedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
