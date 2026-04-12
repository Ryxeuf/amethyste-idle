<?php

namespace App\Repository;

use App\Entity\App\AuctionListing;
use App\Entity\App\Player;
use App\Enum\AuctionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuctionListing>
 */
class AuctionListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuctionListing::class);
    }

    /**
     * @return array{items: AuctionListing[], total: int, pages: int, page: int}
     */
    public function findActiveListings(?string $search, ?string $type, ?string $rarity, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('l')
            ->join('l.playerItem', 'pi')->addSelect('pi')
            ->join('pi.genericItem', 'gi')->addSelect('gi')
            ->join('l.seller', 's')->addSelect('s')
            ->where('l.status = :status')
            ->andWhere('l.expiresAt > :now')
            ->setParameter('status', AuctionStatus::Active)
            ->setParameter('now', new \DateTimeImmutable());

        if ($search !== null && $search !== '') {
            $qb->andWhere('LOWER(gi.name) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($type !== null && $type !== '') {
            $qb->andWhere('gi.type = :type')
                ->setParameter('type', $type);
        }

        if ($rarity !== null && $rarity !== '') {
            $qb->andWhere('gi.rarity = :rarity')
                ->setParameter('rarity', $rarity);
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();

        $pages = max(1, (int) ceil($total / $limit));
        $page = max(1, min($page, $pages));

        $items = $qb->orderBy('l.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    /**
     * @return AuctionListing[]
     */
    public function findBySeller(Player $seller, ?AuctionStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->join('l.playerItem', 'pi')->addSelect('pi')
            ->join('pi.genericItem', 'gi')->addSelect('gi')
            ->where('l.seller = :seller')
            ->setParameter('seller', $seller)
            ->orderBy('l.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('l.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
