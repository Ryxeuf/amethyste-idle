<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\SettlementRankCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Les trois calculs qui font monter, descendre et nommer un foyer (FOY-03).
 *
 * Tout est pur : le chiffrage de BALANCE § 23.2 a § 23.4 se verifie ici
 * directement, sans monter un monde autour. Une regle d'equilibrage qu'on ne
 * peut pas eprouver seule finit par n'etre eprouvee par personne.
 */
class SettlementRankCalculatorTest extends TestCase
{
    /** @var array<string, int> */
    private const THRESHOLDS = [
        'camp' => 150,
        'hamlet' => 1200,
        'town' => 8000,
        'city' => 25000,
        'metropolis' => 60000,
    ];

    public function testRankIsTheHighestThresholdReached(): void
    {
        self::assertSame(SettlementRank::Ruin, SettlementRankCalculator::rankFor(0, self::THRESHOLDS));
        self::assertSame(SettlementRank::Ruin, SettlementRankCalculator::rankFor(149, self::THRESHOLDS));
        self::assertSame(SettlementRank::Camp, SettlementRankCalculator::rankFor(150, self::THRESHOLDS));
        self::assertSame(SettlementRank::Camp, SettlementRankCalculator::rankFor(1199, self::THRESHOLDS));
        self::assertSame(SettlementRank::Hamlet, SettlementRankCalculator::rankFor(1200, self::THRESHOLDS));
        self::assertSame(SettlementRank::Town, SettlementRankCalculator::rankFor(8000, self::THRESHOLDS));
        self::assertSame(SettlementRank::City, SettlementRankCalculator::rankFor(25000, self::THRESHOLDS));
        self::assertSame(SettlementRank::Metropolis, SettlementRankCalculator::rankFor(60000, self::THRESHOLDS));
        self::assertSame(SettlementRank::Metropolis, SettlementRankCalculator::rankFor(999999, self::THRESHOLDS));
    }

    /**
     * Demi-vie d'environ 35 jours (BALANCE § 23.2) : un foyer delaisse une maree
     * entiere descend visiblement, un foyer delaisse une semaine ne s'effondre
     * pas. C'est ce reglage qui fait du rang une photographie de la
     * frequentation reelle plutot qu'un cumul.
     */
    public function testDecayHalvesTheStockInRoughlyOneTide(): void
    {
        self::assertSame(493, SettlementRankCalculator::decay(1000, 0.02, 35));
        self::assertSame(868, SettlementRankCalculator::decay(1000, 0.02, 7));
    }

    /**
     * Un tick manque n'offre pas une journee de repit : la decroissance compose
     * sur les jours ecoules. Un serveur eteint une semaine reprend ou il en
     * etait, pas sept jours plus haut.
     */
    public function testMissingATickBuysNoRespite(): void
    {
        $oneDay = SettlementRankCalculator::decay(10000, 0.02, 1);
        $threeDays = SettlementRankCalculator::decay(10000, 0.02, 3);

        self::assertLessThan($oneDay, $threeDays);
        self::assertSame(9411, $threeDays);
    }

    /**
     * L'arrondi vers le bas est ce qui permet a un foyer d'atteindre reellement
     * zero. Au plus proche, un stock de 1 ne descendrait jamais et chaque zone
     * jamais frequentee garderait une trace indelebile.
     */
    public function testATinyStockEventuallyReachesZero(): void
    {
        self::assertSame(0, SettlementRankCalculator::decay(1, 0.02, 1));
        self::assertSame(0, SettlementRankCalculator::decay(0, 0.02, 100));
    }

    public function testDecayDoesNothingWithoutElapsedDays(): void
    {
        self::assertSame(500, SettlementRankCalculator::decay(500, 0.02, 0));
        self::assertSame(500, SettlementRankCalculator::decay(500, 0.02, -3));
    }

    public function testAnEmptySettlementHasNoChallenger(): void
    {
        self::assertNull(SettlementRankCalculator::challenger(
            ['trade' => 0, 'war' => 0, 'lore' => 0, 'rite' => 0],
            0.25,
        ));
    }

    /**
     * Egalite parfaite : personne devant. Rendre le premier venu ferait dependre
     * l'identite d'une ville de l'ordre de declaration d'un enum.
     */
    public function testAPerfectTieHasNoChallenger(): void
    {
        self::assertNull(SettlementRankCalculator::challenger(
            ['trade' => 500, 'war' => 500, 'lore' => 0, 'rite' => 0],
            0.25,
        ));
    }

    public function testTheMarginIsWhatSeparatesALeaderFromANeighbour(): void
    {
        // 125 = exactement 100 x 1,25 : la marge est atteinte.
        self::assertSame(SettlementIndex::Trade, SettlementRankCalculator::challenger(
            ['trade' => 125, 'war' => 100, 'lore' => 0, 'rite' => 0],
            0.25,
        ));

        self::assertNull(SettlementRankCalculator::challenger(
            ['trade' => 124, 'war' => 100, 'lore' => 0, 'rite' => 0],
            0.25,
        ));
    }

    /**
     * Un foyer nourri par un seul indice a un pretendant des le premier grain :
     * il n'y a personne a depasser.
     */
    public function testASingleFedIndexClaimsImmediately(): void
    {
        self::assertSame(SettlementIndex::Rite, SettlementRankCalculator::challenger(
            ['trade' => 0, 'war' => 0, 'lore' => 0, 'rite' => 1],
            0.25,
        ));
    }

    /**
     * La comparaison porte sur le **second**, pas sur la somme des autres : une
     * ville peut avoir trois indices vivants et une identite nette.
     */
    public function testOnlyTheRunnerUpMatters(): void
    {
        self::assertSame(SettlementIndex::War, SettlementRankCalculator::challenger(
            ['trade' => 300, 'war' => 1000, 'lore' => 300, 'rite' => 300],
            0.25,
        ));
    }
}
