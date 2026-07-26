<?php

namespace App\Repository;

use App\Entity\App\CraftOrder;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Enum\CraftOrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CraftOrder>
 */
class CraftOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CraftOrder::class);
    }

    /**
     * Commandes vivantes d'un commanditaire — celles qui immobilisent encore son
     * escrow.
     *
     * @return CraftOrder[]
     */
    public function findActiveByRequester(Player $requester): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.requester = :requester')
            ->andWhere('o.status IN (:active)')
            ->setParameter('requester', $requester)
            ->setParameter('active', [CraftOrderStatus::Open, CraftOrderStatus::Claimed])
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * L'atelier d'un artisan : les commandes qu'il a prises et pas encore
     * livrees (ECO-07).
     *
     * @return CraftOrder[]
     */
    public function findClaimedByCrafter(Player $crafter): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.recipe', 'r')->addSelect('r')
            ->join('o.requester', 'p')->addSelect('p')
            ->where('o.crafter = :crafter')
            ->andWhere('o.status = :claimed')
            ->setParameter('crafter', $crafter)
            ->setParameter('claimed', CraftOrderStatus::Claimed)
            ->orderBy('o.readyAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveByRequester(Player $requester): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.requester = :requester')
            ->andWhere('o.status IN (:active)')
            ->setParameter('requester', $requester)
            ->setParameter('active', [CraftOrderStatus::Open, CraftOrderStatus::Claimed])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Tableau de commandes d'une region (ECO-06).
     *
     * `$region` nul cible les commandes sans region, et non « toutes » : meme
     * symetrie qu'a l'hotel des ventes, pour qu'un personnage hors graphe ne
     * voie pas l'integralite des marches.
     *
     * Les commandes **directes** en sont exclues (ECO-07b) : elles sont
     * adressees, pas publiees. Les laisser paraitre ici en ferait les deux a la
     * fois, et l'artisan vise n'aurait plus aucun privilege.
     *
     * @return CraftOrder[]
     */
    public function findOpenInRegion(?Region $region, ?string $craft = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.recipe', 'r')->addSelect('r')
            ->join('o.requester', 'p')->addSelect('p')
            ->where('o.status = :open')
            ->andWhere('o.targetCrafter IS NULL')
            ->andWhere('o.expiresAt > :now')
            ->setParameter('open', CraftOrderStatus::Open)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit));

        if (null !== $region) {
            $qb->andWhere('o.region = :region')->setParameter('region', $region);
        } else {
            $qb->andWhere('o.region IS NULL');
        }

        if (null !== $craft && '' !== $craft) {
            $qb->andWhere('r.craft = :craft')->setParameter('craft', $craft);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Les commandes directes qui m'ont ete adressees et que je n'ai pas encore
     * prises (ECO-07b).
     *
     * @return CraftOrder[]
     */
    public function findOpenDirectFor(Player $crafter): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.recipe', 'r')->addSelect('r')
            ->join('o.requester', 'p')->addSelect('p')
            ->where('o.targetCrafter = :crafter')
            ->andWhere('o.status = :open')
            ->andWhere('o.expiresAt > :now')
            ->setParameter('crafter', $crafter)
            ->setParameter('open', CraftOrderStatus::Open)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Commandes actives dont l'echeance est passee — a restituer (ECO-09).
     *
     * @return CraftOrder[]
     */
    public function findExpirable(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status IN (:active)')
            ->andWhere('o.expiresAt <= :now')
            ->setParameter('active', [CraftOrderStatus::Open, CraftOrderStatus::Claimed])
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
