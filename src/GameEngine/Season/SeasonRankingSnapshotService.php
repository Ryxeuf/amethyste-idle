<?php

namespace App\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\PlayerSeasonRankingSnapshot;
use App\Enum\RankingTab;
use App\Repository\PlayerSeasonRankingSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Fige les top-N des classements (kills / quests / xp) dans une table
 * d'archive quand une saison se termine. Idempotent : un second appel
 * sur la meme saison ne double pas les lignes (no-op si snapshot existe).
 *
 * Depuis la tache 132, les valeurs archivees sont celles **de la saison**
 * (cumul moins reference) et non le palmares depuis l'ouverture du serveur.
 */
class SeasonRankingSnapshotService
{
    public const int DEFAULT_LIMIT = 50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RankingBaselineService $baselineService,
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

        $counts = [];
        foreach (RankingTab::cases() as $tab) {
            $counts[$tab->value] = $this->snapshotTab($season, $tab, $limit);
        }

        $this->entityManager->flush();

        return $counts;
    }

    private function snapshotTab(InfluenceSeason $season, RankingTab $tab, int $limit): int
    {
        $rank = 0;

        foreach ($this->baselineService->topOfSeason($tab, $limit) as $row) {
            ++$rank;
            $this->entityManager->persist(new PlayerSeasonRankingSnapshot(
                $season,
                $tab,
                $rank,
                $row['player'],
                $row['total'],
            ));
        }

        return $rank;
    }
}
