<?php

namespace App\Repository;

use App\Entity\App\GroupDungeonMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupDungeonMember>
 */
class GroupDungeonMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupDungeonMember::class);
    }
}
