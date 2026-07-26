<?php

namespace App\Repository;

use App\Entity\Game\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * Recettes dont le nom, ou celui de leur resultat, correspond (ECO-12b).
     *
     * Les deux champs comptent : un joueur cherche « epee » sans savoir si la
     * recette porte le nom de l'objet ou celui du procede.
     *
     * @return Recipe[]
     */
    public function searchByName(string $needle, int $limit): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('i')
            ->join('r.result', 'i')
            ->andWhere('LOWER(i.name) LIKE :needle OR LOWER(r.name) LIKE :needle')
            ->setParameter('needle', '%' . mb_strtolower($needle) . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
