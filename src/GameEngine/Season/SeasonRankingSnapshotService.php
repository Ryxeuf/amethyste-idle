<?php

namespace App\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\PlayerSeasonRankingSnapshot;
use App\Enum\RankingTab;
use App\Repository\DomainExperienceRepository;
use App\Repository\PlayerBestiaryRepository;
use App\Repository\PlayerQuestCompletedRepository;
use App\Repository\PlayerSeasonRankingSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Fige les top-N des classements (kills / quests / xp) dans une table
 * d'archive quand une saison se termine. Idempotent : un second appel
 * sur la meme saison ne double pas les lignes (no-op si snapshot existe).
 */
class SeasonRankingSnapshotService
{
    public const int DEFAULT_LIMIT = 50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerBestiaryRepository $bestiaryRepository,
        private readonly PlayerQuestCompletedRepository $questCompletedRepository,
        private readonly DomainExperienceRepository $domainExperienceRepository,
        private readonly PlayerSeasonRankingSnapshotRepository $snapshotRepository,
    ) {
    }

    /**
     * Snapshote les 3 onglets du classement pour la saison donnee.
     *
     * @return array<string, int> nombre de lignes creees par onglet
     */
    public function snapshot(InfluenceSeason $season, int $limit = self::DEFAULT_LIMIT): array
    {
        if ($this->snapshotRepository->countForSeason($season) > 0) {
            return [RankingTab::Kills->value => 0, RankingTab::Quests->value => 0, RankingTab::Xp->value => 0];
        }

        $counts = [
            RankingTab::Kills->value => $this->snapshotKills($season, $limit),
            RankingTab::Quests->value => $this->snapshotQuests($season, $limit),
            RankingTab::Xp->value => $this->snapshotXp($season, $limit),
        ];

        $this->entityManager->flush();

        return $counts;
    }

    private function snapshotKills(InfluenceSeason $season, int $limit): int
    {
        $rows = $this->bestiaryRepository->findTopKillers($limit);

        return $this->persistRows($season, RankingTab::Kills, $rows, 'totalKills');
    }

    private function snapshotQuests(InfluenceSeason $season, int $limit): int
    {
        $rows = $this->questCompletedRepository->findTopQuestCompleters($limit);

        return $this->persistRows($season, RankingTab::Quests, $rows, 'totalQuests');
    }

    private function snapshotXp(InfluenceSeason $season, int $limit): int
    {
        $rows = $this->domainExperienceRepository->findTopXpEarners($limit);

        return $this->persistRows($season, RankingTab::Xp, $rows, 'totalXp');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function persistRows(InfluenceSeason $season, RankingTab $tab, array $rows, string $valueKey): int
    {
        $rank = 0;
        foreach ($rows as $row) {
            ++$rank;
            if (!isset($row['player']) || !\is_object($row['player'])) {
                continue;
            }
            $total = (int) ($row[$valueKey] ?? 0);
            $snapshot = new PlayerSeasonRankingSnapshot(
                $season,
                $tab,
                $rank,
                $row['player'],
                $total,
            );
            $this->entityManager->persist($snapshot);
        }

        return $rank;
    }
}
