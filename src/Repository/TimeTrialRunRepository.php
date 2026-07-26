<?php

namespace App\Repository;

use App\Entity\App\Player;
use App\Entity\App\TimeTrial;
use App\Entity\App\TimeTrialRun;
use App\Enum\TimeTrialStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeTrialRun>
 */
class TimeTrialRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeTrialRun::class);
    }

    /**
     * Tentative en cours du joueur, tous parcours confondus.
     *
     * Une seule course a la fois : autoriser des tentatives paralleles
     * reviendrait a chronometrer un meme trajet sur plusieurs parcours.
     */
    public function findRunning(Player $player): ?TimeTrialRun
    {
        return $this->findOneBy(['player' => $player, 'status' => TimeTrialStatus::Running]);
    }

    /**
     * Meilleur temps de chaque joueur sur un parcours, du plus rapide au plus
     * lent.
     *
     * @return TimeTrialRun[]
     */
    public function findLeaderboard(TimeTrial $trial, int $limit = 20): array
    {
        /** @var TimeTrialRun[] $runs */
        $runs = $this->createQueryBuilder('r')
            ->andWhere('r.trial = :trial')
            ->andWhere('r.status = :finished')
            ->setParameter('trial', $trial)
            ->setParameter('finished', TimeTrialStatus::Finished->value)
            ->orderBy('r.elapsedSeconds', 'ASC')
            ->addOrderBy('r.finishedAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Un joueur n'apparait qu'avec son meilleur temps. Le tri etant deja
        // croissant, la premiere occurrence est la bonne.
        $board = [];
        foreach ($runs as $run) {
            $playerId = $run->getPlayer()->getId();
            if (isset($board[$playerId])) {
                continue;
            }
            $board[$playerId] = $run;
            if (\count($board) >= $limit) {
                break;
            }
        }

        return array_values($board);
    }

    public function findPersonalBest(Player $player, TimeTrial $trial): ?TimeTrialRun
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.player = :player')
            ->andWhere('r.trial = :trial')
            ->andWhere('r.status = :finished')
            ->setParameter('player', $player)
            ->setParameter('trial', $trial)
            ->setParameter('finished', TimeTrialStatus::Finished->value)
            ->orderBy('r.elapsedSeconds', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
