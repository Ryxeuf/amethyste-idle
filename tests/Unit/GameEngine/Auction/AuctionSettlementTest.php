<?php

namespace App\Tests\Unit\GameEngine\Auction;

use App\GameEngine\Auction\AuctionSettlement;
use PHPUnit\Framework\TestCase;

/**
 * ECO-04 — repartition des Gils d'une vente a l'hotel des ventes.
 */
class AuctionSettlementTest extends TestCase
{
    private const REBATE = 0.10;

    public function testTaxGoesToTheTreasuryWhenAGuildRulesTheRegion(): void
    {
        $settlement = AuctionSettlement::compute(1000, 0.05, true, false, self::REBATE);

        self::assertSame(50, $settlement->taxAmount);
        self::assertSame(50, $settlement->treasuryAmount);
        self::assertSame(0, $settlement->burnedAmount);
        self::assertSame(950, $settlement->sellerRevenue);
        self::assertSame(1000, $settlement->buyerCharge);
    }

    /**
     * Sans guilde controlante, les Gils prelevees ne vont a personne : ils sont
     * **detruits**. C'est le gold sink du canal, et c'est deliberé — les rendre
     * au vendeur ferait de la taxe une simple illusion d'affichage.
     */
    public function testTaxIsBurnedWhenNoGuildRulesTheRegion(): void
    {
        $settlement = AuctionSettlement::compute(1000, 0.05, false, false, self::REBATE);

        self::assertSame(50, $settlement->taxAmount);
        self::assertSame(0, $settlement->treasuryAmount);
        self::assertSame(50, $settlement->burnedAmount);
        // Le vendeur touche la meme chose que si une guilde avait percu la taxe :
        // son revenu ne depend pas de la politique de la region.
        self::assertSame(950, $settlement->sellerRevenue);
        self::assertSame(1000, $settlement->buyerCharge);
    }

    public function testMemberRebateReducesWhatTheBuyerPaysAndTheTreasuryReceives(): void
    {
        $settlement = AuctionSettlement::compute(1000, 0.20, true, true, self::REBATE);

        self::assertSame(200, $settlement->taxAmount);
        self::assertSame(100, $settlement->memberRebate);
        self::assertSame(900, $settlement->buyerCharge);
        self::assertSame(100, $settlement->treasuryAmount);
    }

    /**
     * Invariant n°1 : le vendeur ne depend jamais de l'identite de l'acheteur.
     * Sinon son revenu varierait selon l'appartenance de guilde de l'acheteur,
     * impossible a anticiper au moment de fixer un prix.
     */
    public function testSellerRevenueIsIdenticalWithAndWithoutAMemberBuyer(): void
    {
        $withMember = AuctionSettlement::compute(1000, 0.20, true, true, self::REBATE);
        $withStranger = AuctionSettlement::compute(1000, 0.20, true, false, self::REBATE);

        self::assertSame($withStranger->sellerRevenue, $withMember->sellerRevenue);
    }

    /**
     * Invariant n°2 : la guilde ne peut reverser que ce qu'elle preleve. Une
     * ristourne superieure a la taxe se financerait sur le tresor a chaque
     * transaction — une fuite, pas un avantage.
     */
    public function testRebateIsCappedByTheTaxCollected(): void
    {
        // Taxe 2 % contre ristourne 10 % : la remise voudrait 100, la taxe n'a
        // percu que 20.
        $settlement = AuctionSettlement::compute(1000, 0.02, true, true, self::REBATE);

        self::assertSame(20, $settlement->taxAmount);
        self::assertSame(20, $settlement->memberRebate);
        self::assertSame(0, $settlement->treasuryAmount);
        self::assertSame(980, $settlement->buyerCharge);
        self::assertGreaterThanOrEqual(0, $settlement->treasuryAmount);
    }

    public function testNoRebateWithoutARulingGuildEvenForAGuildedBuyer(): void
    {
        $settlement = AuctionSettlement::compute(1000, 0.05, false, true, self::REBATE);

        self::assertSame(0, $settlement->memberRebate);
        self::assertSame(1000, $settlement->buyerCharge);
        self::assertSame(50, $settlement->burnedAmount);
    }

    public function testZeroTaxRateProducesNoTaxNoRebate(): void
    {
        $settlement = AuctionSettlement::compute(1000, 0.0, true, true, self::REBATE);

        self::assertSame(0, $settlement->taxAmount);
        self::assertSame(0, $settlement->memberRebate);
        self::assertSame(1000, $settlement->sellerRevenue);
        self::assertSame(1000, $settlement->buyerCharge);
    }

    /**
     * L'arrondi va toujours vers le bas, des deux cotes : ni la guilde ni
     * l'acheteur ne gagnent un Gil au change.
     */
    public function testAmountsAreFlooredNeverRoundedUp(): void
    {
        $settlement = AuctionSettlement::compute(99, 0.05, true, true, self::REBATE);

        self::assertSame(4, $settlement->taxAmount);   // floor(4.95)
        self::assertSame(4, $settlement->memberRebate); // floor(9.9) plafonne a 4
        self::assertSame(95, $settlement->sellerRevenue);
        self::assertSame(95, $settlement->buyerCharge);
        self::assertSame(0, $settlement->treasuryAmount);
    }
}
