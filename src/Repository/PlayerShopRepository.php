<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\PlayerShop;
use App\Entity\App\Zone;
use App\Enum\ShopStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerShop>
 */
class PlayerShopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerShop::class);
    }

    public function findForOwner(Player $owner): ?PlayerShop
    {
        return $this->findOneBy(['owner' => $owner]);
    }

    /**
     * Echoppes dont le loyer est echu (ECO-11).
     *
     * Les echoppes deja en impaye sont exclues : le rideau est deja tombe, et
     * les repasser a chaque cycle ferait grimper la dette sans qu'aucun
     * mecanisme ne permette de la solder.
     *
     * @return PlayerShop[]
     */
    public function findWithRentDue(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.rentDueAt IS NOT NULL')
            ->andWhere('s.rentDueAt <= :now')
            ->andWhere('s.status != :arrears')
            ->setParameter('now', $now)
            ->setParameter('arrears', ShopStatus::Arrears->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * Echoppes visibles depuis une zone : seules celles qui vendent.
     *
     * Une echoppe fermee ou en impaye reste en base — rien n'est confisque —
     * mais elle disparait de la rue.
     *
     * @return PlayerShop[]
     */
    public function findOpenInZone(Zone $zone): array
    {
        return $this->findBy(['zone' => $zone, 'status' => ShopStatus::Open], ['name' => 'ASC']);
    }
}
