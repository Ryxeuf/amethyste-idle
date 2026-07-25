<?php

namespace App\Repository;

use App\Entity\Game\CodexEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CodexEntry>
 */
class CodexEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodexEntry::class);
    }

    public function findBySlug(string $slug): ?CodexEntry
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Entrees debloquables par un declencheur donne (type + cle).
     *
     * @return CodexEntry[]
     */
    public function findByUnlock(string $unlockType, string $unlockKey): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.unlockType = :type')
            ->andWhere('c.unlockKey = :key')
            ->setParameter('type', $unlockType)
            ->setParameter('key', $unlockKey)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CodexEntry[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.category = :category')
            ->setParameter('category', $category)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Toutes les entrees, triees par categorie puis titre (pour l'ecran Codex).
     *
     * @return CodexEntry[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.category', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Faits de monde (`world_fact`) publics, du plus recent au plus ancien —
     * fil de l'histoire du serveur (NAR-07).
     *
     * @return CodexEntry[]
     */
    public function findWorldFactsChronological(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.category = :category')
            ->setParameter('category', CodexEntry::CATEGORY_WORLD_FACT)
            ->orderBy('c.createdAt', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
