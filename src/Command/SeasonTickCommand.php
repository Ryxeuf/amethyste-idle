<?php

namespace App\Command;

use App\Entity\App\InfluenceSeason;
use App\Enum\SeasonStatus;
use App\GameEngine\Guild\PrestigeTitleManager;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Season\RankingBaselineService;
use App\GameEngine\Season\SeasonRankingSnapshotService;
use App\GameEngine\Season\SeasonResolutionService;
use App\GameEngine\Season\SeasonRewardsManager;
use App\GameEngine\World\WorldLoadService;
use App\GameEngine\World\WorldScaleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:season:tick',
    description: 'Gère le cycle de vie des saisons d\'influence : démarrage, rush des 3 derniers jours, fin et attribution du contrôle',
)]
class SeasonTickCommand extends Command
{
    public const int RUSH_HOURS = 72;
    public const float RUSH_MULTIPLIER = 1.5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SeasonManager $seasonManager,
        private readonly TownControlManager $townControlManager,
        private readonly PrestigeTitleManager $prestigeTitleManager,
        private readonly SeasonRankingSnapshotService $rankingSnapshotService,
        private readonly SeasonRewardsManager $rewardsManager,
        private readonly SeasonResolutionService $resolutionService,
        private readonly RankingBaselineService $baselineService,
        private readonly WorldLoadService $worldLoadService,
        private readonly WorldScaleService $worldScaleService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();

        // 1. End active seasons that have passed their end date.
        //    Une maree qui s'acheve **est** la bascule : c'est le seul moment
        //    ou le monde a le droit de se contracter (FOY-17b).
        $tideBoundary = $this->handleExpiredSeasons($io, $now);

        // 2. Start scheduled seasons whose start date has arrived
        $this->handleScheduledSeasons($io, $now);

        // 3. Activate rush multiplier for last 72 hours of active season
        $this->handleRush($io, $now);

        // 4. Ensure a next season is always scheduled
        $this->ensureNextSeasonExists($io);

        // 5. Releve de la charge du monde (FOY-17). En fin de chaine, apres les
        //    versements de cloture : ce qu'on mesure est l'energie depensee par
        //    les joueurs, pas les ecritures du tick, mais un releve pris avant
        //    la cloture daterait d'avant le basculement de maree.
        $this->captureWorldLoad($io, $now, $tideBoundary);

        return Command::SUCCESS;
    }

    private function captureWorldLoad(SymfonyStyle $io, \DateTimeImmutable $now, bool $tideBoundary): void
    {
        $snapshot = $this->worldLoadService->capture($now);

        $io->info(sprintf(
            'Charge du monde au %s : %d point(s) d\'energie depensee, population effective %.1f (%d jour(s) mesures).',
            $snapshot->getDay()->format('d/m/Y'),
            $snapshot->getDailyEnergy(),
            $this->worldLoadService->effectivePopulation(),
            $this->worldLoadService->measuredDays(),
        ));

        // FOY-17b — la mise a l'echelle suit immediatement la mesure. Le drapeau
        // dit si l'on est a une bascule de maree : hors bascule, seule
        // l'expansion est permise (asymetrie de BALANCE § 22.4).
        $newScale = $this->worldScaleService->evaluate($tideBoundary);
        if ($newScale !== null) {
            $io->success(sprintf('Facteur de monde porte a %.2f.', $newScale));
        }
    }

    /**
     * @return bool vrai si une maree s'est achevee sur ce tick
     */
    private function handleExpiredSeasons(SymfonyStyle $io, \DateTimeImmutable $now): bool
    {
        $activeSeason = $this->seasonManager->getCurrentSeason();

        if ($activeSeason === null) {
            return false;
        }

        if ($activeSeason->getEndsAt() > $now) {
            return false;
        }

        // Attribute region control before ending the season
        $results = $this->townControlManager->attributeControl($activeSeason);

        // Update prestige titles for controlling guild members
        $this->prestigeTitleManager->updateTitles($activeSeason);

        // Archive top-N individual rankings (task 132 sous-phase 3)
        $snapshotCounts = $this->rankingSnapshotService->snapshot($activeSeason);

        // Award podium titles (task 132 sous-phase 4)
        $rewardCounts = $this->rewardsManager->awardPodium($activeSeason);

        // Credits narratifs : la guilde controlante s'inscrit au journal de monde (NAR-11)
        $factsRecorded = $this->resolutionService->resolve($activeSeason, $results);

        // Reference du classement (tache 132) : **apres** l'archivage et les
        // titres. La saison qui s'acheve se juge sur la reference de la
        // precedente ; figer avant remettrait tout le monde a zero.
        $baselineCounts = $this->baselineService->capture($activeSeason);

        $this->seasonManager->endSeason($activeSeason);

        $controlSummary = [];
        foreach ($results as $regionSlug => $guildName) {
            $controlSummary[] = sprintf('  %s → %s', $regionSlug, $guildName ?? 'libre');
        }

        $io->success(sprintf(
            'Saison "%s" terminée. Contrôle attribué :%s',
            $activeSeason->getName(),
            $controlSummary !== [] ? "\n" . implode("\n", $controlSummary) : ' aucune région contestable',
        ));

        $io->info(sprintf(
            'Classement archivé : %d kills, %d quêtes, %d XP.',
            $snapshotCounts['kills'] ?? 0,
            $snapshotCounts['quests'] ?? 0,
            $snapshotCounts['xp'] ?? 0,
        ));

        $io->info(sprintf('Journal de monde : %d fait(s) de résolution enregistré(s).', $factsRecorded));

        $io->info(sprintf(
            'Titres du podium attribués : %d kills, %d quêtes, %d XP.',
            $rewardCounts['kills'] ?? 0,
            $rewardCounts['quests'] ?? 0,
            $rewardCounts['xp'] ?? 0,
        ));

        $io->info(sprintf(
            'Références de classement figées : %d kills, %d quêtes, %d XP.',
            $baselineCounts['kills'] ?? 0,
            $baselineCounts['quests'] ?? 0,
            $baselineCounts['xp'] ?? 0,
        ));

        return true;
    }

    private function handleScheduledSeasons(SymfonyStyle $io, \DateTimeImmutable $now): void
    {
        // Don't start if there's already an active season
        if ($this->seasonManager->getCurrentSeason() !== null) {
            return;
        }

        $scheduledSeasons = $this->entityManager->getRepository(InfluenceSeason::class)->findBy(
            ['status' => SeasonStatus::Scheduled],
            ['startsAt' => 'ASC'],
        );

        foreach ($scheduledSeasons as $season) {
            if ($season->getStartsAt() <= $now) {
                $this->seasonManager->startSeason($season);
                $io->success(sprintf('Saison "%s" démarrée.', $season->getName()));

                return;
            }
        }
    }

    private function handleRush(SymfonyStyle $io, \DateTimeImmutable $now): void
    {
        $activeSeason = $this->seasonManager->getCurrentSeason();

        if ($activeSeason === null) {
            return;
        }

        // Already in rush mode
        $parameters = $activeSeason->getParameters() ?? [];
        if (!empty($parameters['rush_active'])) {
            return;
        }

        $endsAt = $activeSeason->getEndsAt();
        $rushStart = \DateTimeImmutable::createFromInterface($endsAt)->modify(sprintf('-%d hours', self::RUSH_HOURS));

        if ($now < $rushStart) {
            return;
        }

        $parameters['rush_active'] = true;
        $parameters['rush_multiplier'] = self::RUSH_MULTIPLIER;
        $activeSeason->setParameters($parameters);
        $activeSeason->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        $io->info(sprintf(
            'Ruée des 3 derniers jours activée pour "%s" (×%.1f).',
            $activeSeason->getName(),
            self::RUSH_MULTIPLIER,
        ));
    }

    private function ensureNextSeasonExists(SymfonyStyle $io): void
    {
        $scheduled = $this->entityManager->getRepository(InfluenceSeason::class)->findOneBy(
            ['status' => SeasonStatus::Scheduled],
        );

        if ($scheduled !== null) {
            return;
        }

        $nextSeason = $this->seasonManager->getOrCreateNextSeason();

        $io->info(sprintf(
            'Prochaine saison "%s" créée (début : %s).',
            $nextSeason->getName(),
            $nextSeason->getStartsAt()->format('d/m/Y'),
        ));
    }
}
