<?php

namespace App\Repository;

use App\Entity\App\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * Energie d'action depensee par tous les personnages depuis toujours (FOY-17).
     *
     * C'est la matiere premiere du dimensionnement du monde. La requete est une
     * simple agregation, jouee une fois par jour par le tick : elle n'a pas
     * besoin d'etre plus fine, et la porter ici plutot que dans le service rend
     * ce dernier testable sans monter un faux QueryBuilder.
     *
     * Les maitres du jeu ne sont **pas** exclus ici, et c'est voulu. Leur
     * energie ne s'incremente plus (cf. `ActionEnergyManager::spend()`), donc un
     * MJ n'ajoute rien ; mais un veteran promu MJ garde ce qu'il a reellement
     * depense avant. Le retrancher ferait retrograder des foyers a chaque
     * nomination, pour une pression qui a bel et bien eu lieu.
     */
    public function sumActionEnergySpent(): int
    {
        $sum = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.actionEnergySpentTotal), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return \is_numeric($sum) ? (int) $sum : 0;
    }

    /**
     * Identifiants des personnages maitres du jeu.
     *
     * Sert a les soustraire des mesures qui decrivent la population : les
     * classements, les podiums de saison. La liste est courte par nature — on la
     * charge entiere plutot que de jointer sur chaque agregat.
     *
     * @return list<int>
     */
    public function findGameMasterIds(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.id')
            ->andWhere('p.gameMaster = true')
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }
}
