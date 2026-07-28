<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\GameEngine\Guild\GuildManager;
use App\Repository\SettlementContributionRepository;
use App\Repository\SettlementRepository;

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
    ) {
    }

    /**
     * @return array{
     *     rank: SettlementRank,
     *     type: ?\App\Enum\SettlementType,
     *     total: int,
     *     indices: list<array{index: SettlementIndex, value: int, share: int}>,
     *     next: ?array{rank: SettlementRank, threshold: int, missing: int, progress: int, opens: list<string>},
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
            // Lumiere et les Jardins sont batis sur la Voute : il n'y a rien a
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
