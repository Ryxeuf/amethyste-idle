<?php

namespace App\Repository;

use App\Entity\App\Enchantment;
use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enchantment>
 */
class EnchantmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enchantment::class);
    }

    /**
     * Combien d'enchantements ont expire sur l'equipement porte.
     *
     * **Une seule lecture indexee**, et c'est la raison d'etre de cette
     * methode : `EnchantmentManager` sait deja repondre, mais en parcourant les
     * inventaires et en interrogeant la base une fois par piece portee. C'est
     * tenable sur l'ecran d'artisanat, ou l'on vient rarement ; le hub est le
     * premier ecran de chaque connexion, et son cout est borne (huit lectures).
     *
     * `expires_at` porte deja son index (IDX_enchantment_expires).
     */
    public function countExpiredOnWornGear(Player $player, \DateTimeInterface $now): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.playerItem', 'pi')
            ->join('pi.inventory', 'inv')
            ->where('inv.player = :player')
            ->andWhere('pi.gear != 0')
            ->andWhere('e.expiresAt <= :now')
            ->setParameter('player', $player)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
