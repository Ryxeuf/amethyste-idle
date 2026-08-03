<?php

namespace App\Repository;

use App\Entity\App\CraftOrder;
use App\Entity\App\Guild;
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
        // ECO-28 : jointure externe — une commande de service n'a pas de
        // recette, et l'atelier doit la montrer pour qu'elle soit livree.
        return $this->createQueryBuilder('o')
            ->leftJoin('o.recipe', 'r')->addSelect('r')
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
            // RET-03 : une commande de guilde ne parait jamais au tableau
            // regional. La retirer ici plutot que de la filtrer a l'affichage
            // est ce qui garantit qu'elle ne fuit par aucun autre appelant.
            ->andWhere('o.guild IS NULL')
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
     * Commandes de **service** ouvertes au tableau regional (ECO-28).
     *
     * Une section a part : la jointure sur la recette du tableau classique les
     * exclurait, et un service se lit autrement (la piece du client, pas un
     * objet a produire).
     *
     * @return CraftOrder[]
     */
    public function findOpenServiceInRegion(?Region $region, int $limit = 25): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.requester', 'p')->addSelect('p')
            ->where('o.status = :open')
            ->andWhere('o.serviceKind IS NOT NULL')
            ->andWhere('o.targetCrafter IS NULL')
            ->andWhere('o.guild IS NULL')
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

        return $qb->getQuery()->getResult();
    }

    /**
     * Commandes ouvertes d'une guilde — le canal interne de RET-03.
     *
     * @return CraftOrder[]
     */
    public function findOpenForGuild(Guild $guild, int $limit = 20): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.recipe', 'r')->addSelect('r')
            ->join('o.requester', 'p')->addSelect('p')
            ->where('o.guild = :guild')
            ->andWhere('o.status = :open')
            ->andWhere('o.expiresAt > :now')
            ->setParameter('guild', $guild)
            ->setParameter('open', CraftOrderStatus::Open)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * Commandes de guilde encore **vivantes** depuis une date : ouvertes ou
     * prises, mais pas encore reglees.
     *
     * Le plafond hebdomadaire compte celles-ci, pas celles qui sont livrees ou
     * expirees : une commande servie mardi doit liberer la place pour la
     * suivante, sinon le rendez-vous devient une punition pour les guildes
     * efficaces.
     */
    public function countActiveForGuildSince(Guild $guild, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.guild = :guild')
            ->andWhere('o.status IN (:alive)')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('guild', $guild)
            ->setParameter('alive', [CraftOrderStatus::Open, CraftOrderStatus::Claimed])
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Les commandes directes qui m'ont ete adressees et que je n'ai pas encore
     * prises (ECO-07b).
     *
     * @return CraftOrder[]
     */
    public function findOpenDirectFor(Player $crafter): array
    {
        // ECO-28 : jointure externe — une commande de service n'a pas de
        // recette, et une commande directe de sertissage doit se voir.
        return $this->createQueryBuilder('o')
            ->leftJoin('o.recipe', 'r')->addSelect('r')
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
