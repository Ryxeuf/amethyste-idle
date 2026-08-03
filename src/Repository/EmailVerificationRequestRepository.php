<?php

namespace App\Repository;

use App\Entity\EmailVerificationRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailVerificationRequest>
 */
class EmailVerificationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerificationRequest::class);
    }

    public function findOneBySelector(string $selector): ?EmailVerificationRequest
    {
        return $this->findOneBy(['selector' => $selector]);
    }

    /**
     * Retire le jeton actif du compte, s'il en a un — le renvoi remplace,
     * il ne s'ajoute pas (meme loi que le mot de passe oublie).
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
