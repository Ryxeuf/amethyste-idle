<?php

namespace App\GameEngine\Season;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\PlayerRankingBaseline;
use App\Enum\RankingTab;
use App\Repository\DomainExperienceRepository;
use App\Repository\PlayerBestiaryRepository;
use App\Repository\PlayerQuestCompletedRepository;
use App\Repository\PlayerRankingBaselineRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Reference de classement figee a la cloture d'une saison (tache 132).
 *
 * Les trois classements agregent des compteurs cumulatifs que rien n'horodate.
 * Sans reference, « le classement de la saison » est en realite le palmares de
 * toute l'histoire du serveur : les memes veterans sur le podium a perpetuite,
 * et un nouveau venu qui ne peut structurellement jamais y figurer.
 *
 * La reference est prise **apres** l'archivage et l'attribution des titres :
 * la saison qui s'acheve doit etre jugee sur la reference de la precedente.
 */
class RankingBaselineService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerRankingBaselineRepository $baselineRepository,
        private readonly PlayerBestiaryRepository $bestiaryRepository,
        private readonly PlayerQuestCompletedRepository $questCompletedRepository,
        private readonly DomainExperienceRepository $domainExperienceRepository,
    ) {
    }

    /**
     * Progression realisee pendant la saison en cours, par joueur.
     *
     * Un joueur sans reference n'en a jamais eu : il a donc tout accompli
     * pendant la saison courante, et son cumul est son score. Un delta negatif
     * est ramene a zero — les compteurs sources ne decroissent pas, et laisser
     * passer un negatif ferait remonter un joueur inactif au classement.
     *
     * @return array<int, int> total de la saison, indexe par identifiant de joueur
     */
    public function currentSeasonTotals(RankingTab $tab): array
    {
        $baselines = $this->baselineRepository->mapByPlayerId($tab);
        $totals = [];

        foreach ($this->cumulativeTotals($tab) as $playerId => $cumulative) {
            $delta = $cumulative - ($baselines[$playerId] ?? 0);
            if ($delta > 0) {
                $totals[$playerId] = $delta;
            }
        }

        return $totals;
    }

    /**
     * Tete du classement de la saison en cours, joueurs hydrates.
     *
     * Le tri se fait **apres** soustraction : trier sur le cumul puis tronquer
     * ramenerait le palmares historique, defaut meme que la tache corrige.
     *
     * @return list<array{player: Player, total: int}>
     */
    public function topOfSeason(RankingTab $tab, int $limit): array
    {
        $totals = $this->currentSeasonTotals($tab);
        arsort($totals);
        $totals = \array_slice($totals, 0, $limit, true);

        if ([] === $totals) {
            return [];
        }

        $byId = [];
        foreach ($this->entityManager->getRepository(Player::class)->findBy(['id' => array_keys($totals)]) as $player) {
            $byId[$player->getId()] = $player;
        }

        $top = [];
        foreach ($totals as $playerId => $total) {
            if (!isset($byId[$playerId])) {
                continue;
            }
            $top[] = ['player' => $byId[$playerId], 'total' => $total];
        }

        return $top;
    }

    /**
     * Rang du joueur dans le classement de la saison, 1-based.
     *
     * Retourne null s'il n'a rien accompli cette saison : un rang de fin de
     * liste pour un score nul est une information trompeuse.
     */
    public function currentSeasonRankFor(Player $player, RankingTab $tab): ?int
    {
        $total = $this->currentSeasonTotalFor($player, $tab);
        if ($total <= 0) {
            return null;
        }

        $ahead = 0;
        foreach ($this->currentSeasonTotals($tab) as $playerId => $other) {
            if ($other > $total && $playerId !== $player->getId()) {
                ++$ahead;
            }
        }

        return $ahead + 1;
    }

    public function currentSeasonTotalFor(Player $player, RankingTab $tab): int
    {
        return max(0, $this->cumulativeTotalFor($player, $tab) - $this->baselineRepository->valueForPlayer($player, $tab));
    }

    /**
     * Fige le cumul de chaque joueur a la cloture de la saison.
     *
     * @return array<string, int> nombre de references ecrites par onglet
     */
    public function capture(InfluenceSeason $season): array
    {
        $counts = [];

        foreach (RankingTab::cases() as $tab) {
            $counts[$tab->value] = $this->captureTab($season, $tab);
        }

        $this->entityManager->flush();

        return $counts;
    }

    private function captureTab(InfluenceSeason $season, RankingTab $tab): int
    {
        $existing = $this->baselineRepository->findIndexedByPlayerId($tab);
        $players = $this->entityManager->getRepository(Player::class);
        $written = 0;

        foreach ($this->cumulativeTotals($tab) as $playerId => $cumulative) {
            $baseline = $existing[$playerId] ?? null;

            if (null !== $baseline) {
                $baseline->setValue($cumulative, $season->getSeasonNumber());
                ++$written;
                continue;
            }

            $player = $players->find($playerId);
            if (!$player instanceof Player) {
                continue;
            }

            $this->entityManager->persist(new PlayerRankingBaseline($player, $tab, $cumulative, $season->getSeasonNumber()));
            ++$written;
        }

        return $written;
    }

    /**
     * @return array<int, int>
     */
    private function cumulativeTotals(RankingTab $tab): array
    {
        return match ($tab) {
            RankingTab::Kills => $this->bestiaryRepository->sumKillsByPlayerId(),
            RankingTab::Quests => $this->questCompletedRepository->countQuestsByPlayerId(),
            RankingTab::Xp => $this->domainExperienceRepository->sumXpByPlayerId(),
        };
    }

    private function cumulativeTotalFor(Player $player, RankingTab $tab): int
    {
        return match ($tab) {
            RankingTab::Kills => $this->bestiaryRepository->getTotalKills($player),
            RankingTab::Quests => $this->questCompletedRepository->countQuestsCompleted($player),
            RankingTab::Xp => $this->domainExperienceRepository->getTotalXpEarned($player),
        };
    }
}
