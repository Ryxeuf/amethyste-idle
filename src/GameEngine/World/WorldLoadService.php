<?php

namespace App\GameEngine\World;

use App\Entity\App\Player;
use App\Entity\App\WorldLoadSnapshot;
use App\Repository\WorldLoadSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Mesure de la charge du monde (FOY-17).
 *
 * > **La population effective se deduit de l'energie depensee, pas des
 * > connexions.** ([BALANCE.md § 22.5](../../../docs/BALANCE.md))
 *
 * L'energie est la ressource rare fondamentale : toute action qui pese sur le
 * monde en consomme, et **se connecter n'en consomme pas**. La population
 * effective est donc un nombre de joueurs reguliers **equivalents**, pas un
 * nombre de comptes — ce qui la rend insensible au multi-compte, puisqu'un
 * joueur qui fait tourner trois comptes exerce reellement la pression de trois.
 *
 * Ce service ne decide de rien : il **mesure**. La mise a l'echelle du monde
 * (facteur `W`) est le travail de `WorldScaleService`, qui consomme cette
 * mesure.
 */
class WorldLoadService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorldLoadSnapshotRepository $snapshotRepository,
        /** Energie qu'un joueur regulier depense par jour (BALANCE § 22.5 : ~60 % de sa regen). */
        private readonly int $regularPlayerDailyEnergy,
        /** Duree d'une maree, en jours (`SeasonManager::SEASON_DURATION_DAYS`). */
        private readonly int $tideDays,
    ) {
    }

    /**
     * Capture la charge du jour. Idempotent : rejouer le meme jour reecrit la
     * meme ligne plutot que d'en creer une seconde.
     */
    public function capture(?\DateTimeImmutable $now = null): WorldLoadSnapshot
    {
        $now ??= new \DateTimeImmutable();
        $day = $now->setTime(0, 0, 0);

        $cumulative = $this->totalEnergySpent();
        $previous = $this->snapshotRepository->findLatestBefore($day);

        // Difference avec le dernier instantane connu — pas avec « hier ». Un
        // serveur arrete trois jours reprend sur ce qu'il sait, plutot que de
        // compter zero et de faire croire a une desertion.
        $daily = $previous === null
            ? $cumulative
            : max(0, $cumulative - $previous->getCumulativeEnergy());

        $snapshot = $this->snapshotRepository->findOneByDay($day);
        if ($snapshot === null) {
            $snapshot = new WorldLoadSnapshot();
            $snapshot->setDay($day);
            $this->entityManager->persist($snapshot);
        }

        $snapshot->setCumulativeEnergy($cumulative);
        $snapshot->setDailyEnergy($daily);
        $snapshot->setCapturedAt($now);

        $this->entityManager->flush();

        return $snapshot;
    }

    /**
     * Population effective sur la maree ecoulee.
     *
     * `C / (energie d'un joueur regulier sur une maree)`, ou `C` est l'energie
     * totale depensee sur la fenetre.
     */
    public function effectivePopulation(): float
    {
        $energyPerRegularPlayer = $this->regularPlayerDailyEnergy * $this->tideDays;
        if ($energyPerRegularPlayer <= 0) {
            return 0.0;
        }

        return $this->energySpentOverTide() / $energyPerRegularPlayer;
    }

    /**
     * Energie totale depensee sur la maree ecoulee.
     */
    public function energySpentOverTide(): int
    {
        $total = 0;
        foreach ($this->snapshotRepository->findRecent($this->tideDays) as $snapshot) {
            $total += $snapshot->getDailyEnergy();
        }

        return $total;
    }

    /**
     * Nombre de jours mesures. En dessous d'une maree pleine, la mesure est
     * partielle — c'est ce qui justifie la periode de grace au lancement
     * (BALANCE § 22.4) : un monde ne doit pas se contracter sur une fenetre
     * qu'il n'a pas encore eu le temps de remplir.
     */
    public function measuredDays(): int
    {
        return \count($this->snapshotRepository->findRecent($this->tideDays));
    }

    private function totalEnergySpent(): int
    {
        $sum = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(p.actionEnergySpentTotal), 0)')
            ->from(Player::class, 'p')
            ->getQuery()
            ->getSingleScalarResult();

        return \is_numeric($sum) ? (int) $sum : 0;
    }
}
