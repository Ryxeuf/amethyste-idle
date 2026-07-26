<?php

namespace App\Repository;

use App\Entity\App\PlayerShop;
use App\Entity\App\ShopListing;
use App\Enum\ShopStatus;
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

    /**
     * Lots en vente dont l'objet correspond a la recherche (ECO-12b).
     *
     * Seules les echoppes **ouvertes** repondent : un rideau baisse ne doit pas
     * faire esperer un achat impossible.
     *
     * @return ShopListing[]
     */
    public function searchOnSale(string $needle, int $limit): array
    {
        return $this->createQueryBuilder('l')
            ->addSelect('s', 'i')
            ->join('l.shop', 's')
            ->join('l.playerItem', 'pi')
            ->join('pi.genericItem', 'i')
            ->andWhere('s.status = :open')
            ->andWhere('LOWER(i.name) LIKE :needle')
            ->setParameter('open', ShopStatus::Open->value)
            ->setParameter('needle', '%' . mb_strtolower($needle) . '%')
            ->orderBy('l.unitPrice', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
