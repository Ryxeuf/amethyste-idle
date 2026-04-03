<?php

namespace App\Repository;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inventory>
 */
class InventoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inventory::class);
    }

    /**
     * Charge un inventaire avec tous ses PlayerItem et leur Item (genericItem) en une seule requête.
     * Élimine le N+1 sur getItems() -> getGenericItem().
     */
    public function findByPlayerAndTypeWithItems(Player $player, int $type): ?Inventory
    {
        return $this->createQueryBuilder('inv')
            ->leftJoin('inv.items', 'pi')->addSelect('pi')
            ->leftJoin('pi.genericItem', 'gi')->addSelect('gi')
            ->leftJoin('pi.slots', 's')->addSelect('s')
            ->where('inv.player = :player')
            ->andWhere('inv.type = :type')
            ->setParameter('player', $player)
            ->setParameter('type', $type)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Charge tous les inventaires d'un joueur avec leurs items et genericItems.
     *
     * @return Inventory[]
     */
    public function findAllByPlayerWithItems(Player $player): array
    {
        return $this->createQueryBuilder('inv')
            ->leftJoin('inv.items', 'pi')->addSelect('pi')
            ->leftJoin('pi.genericItem', 'gi')->addSelect('gi')
            ->where('inv.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getResult();
    }
}
