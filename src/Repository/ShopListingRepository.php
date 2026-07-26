<?php

namespace App\Repository;

use App\Entity\App\PlayerShop;
use App\Entity\App\ShopListing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopListing>
 */
class ShopListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopListing::class);
    }

    /**
     * @return ShopListing[]
     */
    public function findForShop(PlayerShop $shop): array
    {
        return $this->findBy(['shop' => $shop], ['listedAt' => 'ASC']);
    }

    public function countForShop(PlayerShop $shop): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.shop = :shop')
            ->setParameter('shop', $shop)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
