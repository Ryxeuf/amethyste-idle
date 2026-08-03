<?php

namespace App\Repository;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetRequest>
 */
class PasswordResetRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetRequest::class);
    }

    public function findOneBySelector(string $selector): ?PasswordResetRequest
    {
        return $this->findOneBy(['selector' => $selector]);
    }

    /**
     * Retire la demande active du compte, s'il en a une. Une nouvelle demande
     * remplace l'ancienne — c'est la loi « un seul jeton actif par compte »,
     * que l'index unique sur `user_id` tient aussi cote schema.
     */
    public function removeActiveRequestFor(User $user): void
    {
        $existing = $this->findOneBy(['user' => $user]);
        if (null !== $existing) {
            $this->getEntityManager()->remove($existing);
            $this->getEntityManager()->flush();
        }
    }
}
