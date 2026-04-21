<?php

namespace App\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\PlayerSeasonReward;
use App\Enum\RankingTab;
use App\Repository\PlayerSeasonRankingSnapshotRepository;
use App\Repository\PlayerSeasonRewardRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Attribue des recompenses (titres) aux joueurs du podium (top 3) de chaque
 * onglet de classement (kills / quests / xp) a la fin d'une saison.
 *
 * Lit les snapshots deja figes par {@see SeasonRankingSnapshotService} et
 * persiste une {@see PlayerSeasonReward} par (saison, onglet, rang).
 * Idempotent : un second appel sur la meme saison est un no-op.
 */
class SeasonRewardsManager
{
    public const int PODIUM_SIZE = 3;

    private const array TAB_LABELS = [
        'kills' => 'des chasseurs',
        'quests' => 'des aventuriers',
        'xp' => 'du savoir',
    ];

    private const array RANK_PREFIXES = [
        1 => 'Champion',
        2 => 'Vice-champion',
        3 => 'Troisieme',
    ];

    /**
     * Mapping (onglet, rang) -> identifiant d'icone cosmetique. Rendu visuel
     * cote templates (`game/ranking/index.html.twig`, `game/profile/show.html.twig`).
     * Convention : `<tab_theme>_<rank_metal>` (ex: `hunter_gold`).
     */
    private const array COSMETIC_ICONS = [
        'kills' => [
            1 => 'hunter_gold',
            2 => 'hunter_silver',
            3 => 'hunter_bronze',
        ],
        'quests' => [
            1 => 'adventurer_gold',
            2 => 'adventurer_silver',
            3 => 'adventurer_bronze',
        ],
        'xp' => [
            1 => 'scholar_gold',
            2 => 'scholar_silver',
            3 => 'scholar_bronze',
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerSeasonRankingSnapshotRepository $snapshotRepository,
        private readonly PlayerSeasonRewardRepository $rewardRepository,
    ) {
    }

    /**
     * Attribue les titres du podium pour chaque onglet. Retourne le nombre
     * de recompenses creees par onglet.
     *
     * @return array<string, int>
     */
    public function awardPodium(InfluenceSeason $season): array
    {
        if ($this->rewardRepository->countForSeason($season) > 0) {
            return [RankingTab::Kills->value => 0, RankingTab::Quests->value => 0, RankingTab::Xp->value => 0];
        }

        $counts = [];
        foreach (RankingTab::cases() as $tab) {
            $counts[$tab->value] = $this->awardTab($season, $tab);
        }

        $this->entityManager->flush();

        return $counts;
    }

    private function awardTab(InfluenceSeason $season, RankingTab $tab): int
    {
        $snapshots = $this->snapshotRepository->findBySeasonAndTab($season, $tab);
        $created = 0;

        foreach ($snapshots as $snapshot) {
            $rank = $snapshot->getRank();
            if ($rank > self::PODIUM_SIZE) {
                continue;
            }

            $reward = new PlayerSeasonReward(
                $season,
                $snapshot->getPlayer(),
                $tab,
                $rank,
                $this->buildTitleLabel($tab, $rank, $season),
                $this->resolveCosmeticIcon($tab, $rank),
            );
            $this->entityManager->persist($reward);
            ++$created;
        }

        return $created;
    }

    private function buildTitleLabel(RankingTab $tab, int $rank, InfluenceSeason $season): string
    {
        return sprintf(
            '%s %s — Saison %d',
            self::RANK_PREFIXES[$rank] ?? self::RANK_PREFIXES[3],
            self::TAB_LABELS[$tab->value],
            $season->getSeasonNumber(),
        );
    }

    private function resolveCosmeticIcon(RankingTab $tab, int $rank): ?string
    {
        return self::COSMETIC_ICONS[$tab->value][$rank] ?? null;
    }
}
