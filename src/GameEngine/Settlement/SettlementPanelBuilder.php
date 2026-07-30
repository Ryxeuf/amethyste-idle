<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Player;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Retention\WeekKey;
use App\Repository\SettlementContributionRepository;
use App\Repository\SettlementRepository;
use App\Repository\SettlementWeeklyWorkContributionRepository;
use App\Repository\SettlementWeeklyWorkRepository;

/**
 * Ce que l'ecran de zone montre du foyer (FOY-04).
 *
 * « Un compteur qui monte n'est pas un jeu ; un chantier visible en est un »
 * (modele FFXIV/Ishgard, GAME_INSPIRATIONS § 3). Le panneau ne se contente donc
 * pas d'afficher un rang : il dit **ou en est la ville**, **ce que le prochain
 * palier ouvrira**, et **ce que le joueur y a mis** — seul et avec sa guilde.
 *
 * Rendre le prochain palier lisible est le point delicat. Un chiffre qui monte
 * sans promesse n'est qu'une barre ; c'est la phrase « au Bourg, le marche
 * ouvre » qui transforme une frequentation en projet.
 *
 * Le constructeur ne rend **que des donnees** : pas de phrase, pas de HTML.
 * L'ecran de zone est un ecran ne du pivot, ou la tolerance au texte code en dur
 * est nulle (`HardcodedTextTest`) — toute formulation passe par le catalogue.
 */
class SettlementPanelBuilder
{
    public function __construct(
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementContributionRepository $contributionRepository,
        private readonly SettlementDefinitionLoader $loader,
        private readonly SettlementGate $gate,
        private readonly GuildManager $guildManager,
        private readonly SettlementServiceDirectory $serviceDirectory,
        private readonly SettlementWeeklyWorkRepository $workRepository,
        private readonly SettlementWeeklyWorkContributionRepository $workContributionRepository,
        private readonly CrueQuotaService $crueQuota,
        private readonly VassalageService $vassalage,
    ) {
    }

    /**
     * @return array{
     *     rank: SettlementRank,
     *     type: ?\App\Enum\SettlementType,
     *     total: int,
     *     indices: list<array{index: SettlementIndex, value: int, share: int}>,
     *     next: ?array{rank: SettlementRank, threshold: int, missing: int, progress: int, opens: list<string>},
     *     services: list<array{service: string, required: SettlementRank, open: bool, route: string}>,
     *     work: ?array{needs: list<array{activity: string, target: int, progress: int}>, percent: int, complete: bool, contributors: list<array{name: string, units: int}>},
     *     crue: ?array{rank: SettlementRank, quota: int, occupants: list<string>},
     *     overlord: ?array{zone: string, rank: SettlementRank, cap: SettlementRank},
     *     contribution: int,
     *     guildContribution: int,
     *     ebbing: bool,
     *     highestRank: SettlementRank
     * }|null `null` quand la zone n'a pas de foyer — ce n'est pas une anomalie
     */
    public function build(Zone $zone, ?Player $player = null): ?array
    {
        $settlement = $this->settlementRepository->findOneByZone($zone);
        if ($settlement === null) {
            // le Fanal et les Jardins sont batis sur la Voute : il n'y a rien a
            // montrer, et surtout pas une jauge a zero qui laisserait croire a
            // un chantier abandonne.
            return null;
        }

        $definition = $this->loader->load();
        $total = $settlement->getTotalSediment();
        $rank = $settlement->getRank();

        $contribution = 0;
        $guildContribution = 0;
        if ($player !== null) {
            $own = $this->contributionRepository->findOneFor($settlement, $player);
            $contribution = $own === null ? 0 : $own->getGrains();

            $guild = $this->guildManager->getPlayerGuild($player);
            if ($guild !== null) {
                $guildContribution = $this->contributionRepository->sumForGuild($settlement, $guild);
            }
        }

        return [
            'rank' => $rank,
            'type' => $settlement->getType(),
            'total' => $total,
            'indices' => $this->indices($settlement->getAllSediment(), $total),
            'next' => $this->nextStep($rank, $total, $definition['ranks']),
            // FOY-06 : ce que le rang a **deja** ouvert, et ou cela mene. La
            // ligne `next.opens` promet ; celle-ci donne la porte. Les services
            // fermes restent affiches avec leur rang manquant — les masquer
            // rendrait le palier suivant abstrait au moment ou il compte le plus.
            'services' => $this->serviceDirectory->forZone($zone),
            // RET-05 : ce que la ville attend **cette semaine**. La maree dit
            // ou va la ville ; le chantier dit ce qu'elle demande maintenant.
            'work' => $this->weeklyWork($zone),
            // FOY-08 : le foyer merite un rang que la Crue lui refuse. La
            // competition doit se **voir**, sinon elle est vecue comme un bug.
            'crue' => $this->crueWait($settlement, $rank, $definition['ranks']),
            // FOY-09 : une grande voisine boit la croissance. Le dire est ce
            // qui transforme un plafond subi en decision de lieu.
            'overlord' => $this->overlord($settlement),
            'contribution' => $contribution,
            'guildContribution' => $guildContribution,
            // Le foyer a decroche : il a deja ete plus haut. Le signaler prepare
            // l'annonce d'etiage de FOY-10 — une retrogradation ne doit jamais
            // etre une surprise.
            'ebbing' => $settlement->getHighestRank()->level() > $rank->level(),
            'highestRank' => $settlement->getHighestRank(),
        ];
    }

    /**
     * La voisine qui plafonne ce foyer, si elle existe (FOY-09).
     *
     * **Derive, jamais stocke** : le jour ou la capitale tombe, la mention
     * disparait d'elle-meme, sans qu'aucun champ n'ait a etre remis a zero.
     *
     * @return ?array{zone: string, rank: SettlementRank, cap: SettlementRank}
     */
    private function overlord(Settlement $settlement): ?array
    {
        $overlord = $this->vassalage->overlordOf($settlement);
        $cap = $this->vassalage->capFor($settlement);
        if ($overlord === null || $cap === null) {
            return null;
        }

        return [
            'zone' => $overlord->getZone()->getName(),
            'rank' => $overlord->getRank(),
            'cap' => $cap,
        ];
    }

    /**
     * Le rang que la Crue refuse, et qui occupe la place (FOY-08).
     *
     * **Derive, jamais stocke.** L'attente se lit en comparant le rang naturel
     * — celui que le sediment merite — au rang tenu : une colonne de plus aurait
     * fallu tenir d'accord avec un calcul qui, lui, ne peut pas se tromper.
     *
     * Rend `null` quand rien n'attend, ce qui est le cas ordinaire.
     *
     * @param array<string, int> $thresholds
     *
     * @return ?array{rank: SettlementRank, quota: int, occupants: list<string>}
     */
    private function crueWait(Settlement $settlement, SettlementRank $rank, array $thresholds): ?array
    {
        $natural = SettlementRankCalculator::rankFor($settlement->getTotalSediment(), $thresholds);
        if ($natural->level() <= $rank->level()) {
            return null;
        }

        $wanted = $rank->next();
        if ($wanted === null || $this->crueQuota->allows($settlement, $wanted)) {
            return null;
        }

        $quota = $this->crueQuota->quotaFor($wanted);

        return [
            'rank' => $wanted,
            'quota' => $quota ?? 0,
            'occupants' => array_map(
                static fn (Settlement $occupant): string => $occupant->getZone()->getName(),
                $this->crueQuota->occupants($wanted, $settlement),
            ),
        ];
    }

    /**
     * Le chantier de la semaine, et ceux qui l'ont pousse (RET-05).
     *
     * Les contributeurs sont **nommes**, et bornes : nommer tout le monde ne
     * nomme personne, et une liste de quarante lignes noierait exactement la
     * reconnaissance qu'elle porte.
     *
     * @return ?array{needs: list<array{activity: string, target: int, progress: int}>, percent: int, complete: bool, contributors: list<array{name: string, units: int}>}
     */
    private function weeklyWork(Zone $zone): ?array
    {
        $work = $this->workRepository->findCurrentForZone($zone, WeekKey::of(new \DateTimeImmutable()));
        if ($work === null) {
            return null;
        }

        $contributors = [];
        foreach ($this->workContributionRepository->findTopFor($work) as $contribution) {
            $contributors[] = [
                'name' => $contribution->getPlayer()->getName(),
                'units' => $contribution->getUnits(),
            ];
        }

        return [
            'needs' => $work->getNeeds(),
            'percent' => $work->getProgressPercent(),
            'complete' => $work->getCompletedAt() !== null,
            'contributors' => $contributors,
        ];
    }

    /**
     * @param array<string, int> $sediment
     *
     * @return list<array{index: SettlementIndex, value: int, share: int}>
     */
    private function indices(array $sediment, int $total): array
    {
        $rows = [];
        foreach (SettlementIndex::cases() as $index) {
            $value = $sediment[$index->value] ?? 0;
            $rows[] = [
                'index' => $index,
                'value' => $value,
                'share' => $total > 0 ? (int) round($value * 100 / $total) : 0,
            ];
        }

        return $rows;
    }

    /**
     * Le palier suivant, et ce qu'il ouvrira.
     *
     * La progression se lit **entre deux seuils**, pas depuis zero : au Bourg,
     * une barre partant de zero serait pleine a 97 % et ne bougerait plus
     * jamais, alors que le vrai chemin vers la Cite reste presque entier.
     *
     * @param array<string, int> $thresholds
     *
     * @return ?array{rank: SettlementRank, threshold: int, missing: int, progress: int, opens: list<string>}
     */
    private function nextStep(SettlementRank $rank, int $total, array $thresholds): ?array
    {
        $next = $rank->next();
        if ($next === null) {
            return null;
        }

        $target = $thresholds[$next->value] ?? null;
        if ($target === null) {
            return null;
        }

        $floor = $rank === SettlementRank::Ruin ? 0 : ($thresholds[$rank->value] ?? 0);
        $span = max(1, $target - $floor);
        $done = max(0, min($span, $total - $floor));

        return [
            'rank' => $next,
            'threshold' => $target,
            'missing' => max(0, $target - $total),
            'progress' => (int) round($done * 100 / $span),
            'opens' => $this->opensAt($next),
        ];
    }

    /**
     * Services que ce rang precis ouvre — ceux qui l'exigent, pas ceux qui sont
     * deja acquis. Reciter l'acquis noierait la promesse.
     *
     * @return list<string>
     */
    private function opensAt(SettlementRank $rank): array
    {
        $opens = [];
        foreach ($this->gate->services() as $service => $required) {
            if ($required === $rank) {
                $opens[] = $service;
            }
        }

        return $opens;
    }
}
