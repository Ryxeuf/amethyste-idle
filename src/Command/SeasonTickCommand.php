<?php

namespace App\Command;

use App\Entity\App\InfluenceSeason;
use App\Enum\SeasonStatus;
use App\GameEngine\Guild\PrestigeTitleManager;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Season\SeasonRankingSnapshotService;
use App\GameEngine\Season\SeasonRewardsManager;
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
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();

        // 1. End active seasons that have passed their end date
        $this->handleExpiredSeasons($io, $now);

        // 2. Start scheduled seasons whose start date has arrived
        $this->handleScheduledSeasons($io, $now);

        // 3. Activate rush multiplier for last 72 hours of active season
        $this->handleRush($io, $now);

        // 4. Ensure a next season is always scheduled
        $this->ensureNextSeasonExists($io);

        return Command::SUCCESS;
    }

    private function handleExpiredSeasons(SymfonyStyle $io, \DateTimeImmutable $now): void
    {
        $activeSeason = $this->seasonManager->getCurrentSeason();

        if ($activeSeason === null) {
            return;
        }

        if ($activeSeason->getEndsAt() > $now) {
            return;
        }

        // Attribute region control before ending the season
        $results = $this->townControlManager->attributeControl($activeSeason);

        // Update prestige titles for controlling guild members
        $this->prestigeTitleManager->updateTitles($activeSeason);

        // Archive top-N individual rankings (task 132 sous-phase 3)
        $snapshotCounts = $this->rankingSnapshotService->snapshot($activeSeason);

        // Award podium titles (task 132 sous-phase 4)
        $rewardCounts = $this->rewardsManager->awardPodium($activeSeason);

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

        $io->info(sprintf(
            'Titres du podium attribués : %d kills, %d quêtes, %d XP.',
            $rewardCounts['kills'] ?? 0,
            $rewardCounts['quests'] ?? 0,
            $rewardCounts['xp'] ?? 0,
        ));
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
