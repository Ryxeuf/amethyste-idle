<?php

namespace App\Repository;

use App\Entity\App\PlayerShop;
use App\Entity\App\ShopSaleLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopSaleLog>
 */
class ShopSaleLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopSaleLog::class);
    }

    /**
     * @return ShopSaleLog[]
     */
    public function findRecentForShop(PlayerShop $shop, int $limit = 30): array
    {
        return $this->findBy(['shop' => $shop], ['soldAt' => 'DESC'], $limit);
    }
}
