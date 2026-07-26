<?php

namespace App\Repository;

use App\Entity\App\AuctionListing;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Enum\AuctionStatus;
use App\Enum\AuctionType;
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
     * Annonces actives du **marche local** (ECO-03).
     *
     * `$region` porte la segmentation : seules les annonces deposees dans cette
     * region sont visibles, les ventes flash exceptees — canal systeme, global
     * par nature. Un `$region` nul cible le marche des joueurs hors region, et
     * non « toutes les regions » : sans cela, un personnage hors graphe verrait
     * l'integralite des marches.
     *
     * @return array{items: AuctionListing[], total: int, pages: int, page: int}
     */
    public function findActiveListings(?string $search, ?string $type, ?string $rarity, int $page = 1, int $limit = 20, ?Region $region = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->join('l.playerItem', 'pi')->addSelect('pi')
            ->join('pi.genericItem', 'gi')->addSelect('gi')
            ->join('l.seller', 's')->addSelect('s')
            ->where('l.status = :status')
            ->andWhere('l.expiresAt > :now')
            ->setParameter('status', AuctionStatus::Active)
            ->setParameter('now', new \DateTimeImmutable());

        if ($region !== null) {
            $qb->andWhere('(l.region = :region OR l.type = :flashType)')
                ->setParameter('region', $region)
                ->setParameter('flashType', AuctionType::Flash);
        } else {
            $qb->andWhere('(l.region IS NULL OR l.type = :flashType)')
                ->setParameter('flashType', AuctionType::Flash);
        }

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
     * Annonces actives **toutes regions confondues** (ECO-16b).
     *
     * Deliberement hors du filtre regional : la moderation regarde le marche
     * entier, sinon une annonce frauduleuse serait invisible depuis toute autre
     * region que celle du moderateur.
     *
     * @return AuctionListing[]
     */
    public function findActiveForModeration(int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.playerItem', 'pi')->addSelect('pi')
            ->join('pi.genericItem', 'gi')->addSelect('gi')
            ->join('l.seller', 's')->addSelect('s')
            ->where('l.status = :status')
            ->setParameter('status', AuctionStatus::Active)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
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

    /**
     * @return AuctionListing[]
     */
    public function findActiveFlashSales(): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.playerItem', 'pi')->addSelect('pi')
            ->join('pi.genericItem', 'gi')->addSelect('gi')
            ->join('l.seller', 's')->addSelect('s')
            ->where('l.type = :type')
            ->andWhere('l.status = :status')
            ->andWhere('l.expiresAt > :now')
            ->setParameter('type', AuctionType::Flash)
            ->setParameter('status', AuctionStatus::Active)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('l.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Annonces actives dont l'echeance est passee (ECO-16).
     *
     * La requete vivait dans `AuctionManager::expireListings()`, seul endroit
     * du service a construire une requete lui-meme — ce qui rendait le chemin
     * d'expiration intestable autrement qu'en simulant le constructeur de
     * requetes Doctrine. C'etait un defaut de place, pas de testabilite : les
     * sept autres lectures d'annonce etaient deja ici.
     *
     * @return AuctionListing[]
     */
    public function findExpirable(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->andWhere('l.expiresAt <= :now')
            ->setParameter('status', AuctionStatus::Active)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function countActiveBySeller(Player $seller): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.seller = :seller')
            ->andWhere('l.status = :status')
            ->andWhere('l.expiresAt > :now')
            ->setParameter('seller', $seller)
            ->setParameter('status', AuctionStatus::Active)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLastCancelledAt(Player $seller): ?\DateTimeInterface
    {
        $result = $this->createQueryBuilder('l')
            ->select('l.cancelledAt')
            ->where('l.seller = :seller')
            ->andWhere('l.status = :status')
            ->andWhere('l.cancelledAt IS NOT NULL')
            ->setParameter('seller', $seller)
            ->setParameter('status', AuctionStatus::Cancelled)
            ->orderBy('l.cancelledAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['cancelledAt'] ?? null;
    }

    /**
     * @return array{total_listings: int, total_sold: int, total_volume: int, avg_price: float, active_count: int, top_items: array<array{name: string, sold_count: int, avg_price: float}>}
     */
    public function getAuctionStats(): array
    {
        $totalListings = (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $soldStats = $this->createQueryBuilder('l')
            ->select('COUNT(l.id) AS sold_count, COALESCE(SUM(l.pricePerUnit * l.quantity), 0) AS total_volume, COALESCE(AVG(l.pricePerUnit), 0) AS avg_price')
            ->where('l.status = :status')
            ->setParameter('status', AuctionStatus::Sold)
            ->getQuery()
            ->getOneOrNullResult();

        $activeCount = (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :active')
            ->andWhere('l.expiresAt > :now')
            ->setParameter('active', AuctionStatus::Active)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();

        $topItems = $this->getEntityManager()->createQueryBuilder()
            ->select('gi.name, COUNT(l.id) AS sold_count, AVG(l.pricePerUnit) AS avg_price')
            ->from(AuctionListing::class, 'l')
            ->join('l.playerItem', 'pi')
            ->join('pi.genericItem', 'gi')
            ->where('l.status = :status')
            ->setParameter('status', AuctionStatus::Sold)
            ->groupBy('gi.name')
            ->orderBy('sold_count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return [
            'total_listings' => $totalListings,
            'total_sold' => (int) ($soldStats['sold_count'] ?? 0),
            'total_volume' => (int) ($soldStats['total_volume'] ?? 0),
            'avg_price' => round((float) ($soldStats['avg_price'] ?? 0), 0),
            'active_count' => $activeCount,
            'top_items' => array_map(fn (array $row) => [
                'name' => $row['name'],
                'sold_count' => (int) $row['sold_count'],
                'avg_price' => round((float) $row['avg_price'], 0),
            ], $topItems),
        ];
    }
}
