<?php

namespace App\Tests\Unit\Entity;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\Enum\SettlementType;
use PHPUnit\Framework\TestCase;

/**
 * Le foyer d'une zone (FOY-01).
 *
 * Les deux regles que ces tests tiennent sont celles qui, mal ecrites, seraient
 * invisibles : un rang le plus haut qui redescendrait avec le rang courant
 * (FOY-10 perdrait sa memoire), et une egalite parfaite entre indices qui
 * donnerait quand meme une identite a la ville — laquelle dependrait alors de
 * l'ordre de declaration d'un enum.
 */
class SettlementTest extends TestCase
{
    public function testANewSettlementIsARuinWithNothingDeposited(): void
    {
        $settlement = new Settlement(new Zone());

        self::assertSame(SettlementRank::Ruin, $settlement->getRank());
        self::assertSame(SettlementRank::Ruin, $settlement->getHighestRank());
        self::assertNull($settlement->getType());
        self::assertSame(0, $settlement->getTotalSediment());
        self::assertNull($settlement->getDominantIndex());
    }

    public function testRankIsReadOnTheSumOfTheFourIndices(): void
    {
        $settlement = new Settlement(new Zone());
        $settlement->addSediment(SettlementIndex::Trade, 400);
        $settlement->addSediment(SettlementIndex::War, 300);
        $settlement->addSediment(SettlementIndex::Lore, 200);
        $settlement->addSediment(SettlementIndex::Rite, 100);

        self::assertSame(1000, $settlement->getTotalSediment());
        self::assertSame(
            ['trade' => 400, 'war' => 300, 'lore' => 200, 'rite' => 100],
            $settlement->getAllSediment(),
        );
    }

    /**
     * Ce qu'on a bati une fois se rebatit moins cher (FOY-10) : le plus haut
     * rang atteint est une memoire, il ne suit pas la chute.
     */
    public function testHighestRankRemembersTheSummitAfterADecay(): void
    {
        $settlement = new Settlement(new Zone());
        $settlement->setRank(SettlementRank::Town);
        $settlement->setRank(SettlementRank::Camp);

        self::assertSame(SettlementRank::Camp, $settlement->getRank());
        self::assertSame(SettlementRank::Town, $settlement->getHighestRank());
    }

    public function testDominantIndexDrivesTheType(): void
    {
        $settlement = new Settlement(new Zone());
        $settlement->addSediment(SettlementIndex::Trade, 100);
        $settlement->addSediment(SettlementIndex::War, 900);

        self::assertSame(SettlementIndex::War, $settlement->getDominantIndex());
        self::assertSame(SettlementType::Bastion, SettlementType::fromIndex(SettlementIndex::War));
    }

    public function testAPerfectTieLeavesTheSettlementWithoutIdentity(): void
    {
        $settlement = new Settlement(new Zone());
        $settlement->addSediment(SettlementIndex::Trade, 500);
        $settlement->addSediment(SettlementIndex::Lore, 500);

        self::assertNull($settlement->getDominantIndex());
    }

    public function testSedimentNeverGoesNegative(): void
    {
        $settlement = new Settlement(new Zone());
        $settlement->addSediment(SettlementIndex::Rite, 10);
        $settlement->addSediment(SettlementIndex::Rite, -50);

        self::assertSame(0, $settlement->getSediment(SettlementIndex::Rite));
    }

    public function testRankScaleIsOrderedAndComparable(): void
    {
        self::assertSame(
            ['ruin', 'camp', 'hamlet', 'town', 'city', 'metropolis'],
            array_map(static fn (SettlementRank $r): string => $r->value, SettlementRank::ordered()),
        );

        self::assertTrue(SettlementRank::City->isAtLeast(SettlementRank::Town));
        self::assertFalse(SettlementRank::Camp->isAtLeast(SettlementRank::Town));
        self::assertSame(SettlementRank::Hamlet, SettlementRank::Camp->next());
        self::assertNull(SettlementRank::Metropolis->next());
    }

    public function testEveryIndexHasATypeAndBack(): void
    {
        foreach (SettlementIndex::cases() as $index) {
            self::assertSame($index, SettlementType::fromIndex($index)->index());
        }
    }
}
