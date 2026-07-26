<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\App\GilsSupplySnapshot;
use App\GameEngine\Economy\GilsSupplyMeasure;
use App\GameEngine\Economy\GilsSupplyService;
use App\GameEngine\Economy\GilsSupplyTrend;
use PHPUnit\Framework\TestCase;

/**
 * Detection d'inflation par la masse monetaire (ECO-15).
 */
class GilsSupplyTrendTest extends TestCase
{
    private function snapshot(int $total, int $players, string $at): GilsSupplySnapshot
    {
        return new GilsSupplySnapshot(
            playerGils: $total,
            guildGils: 0,
            shopGils: 0,
            escrowGils: 0,
            playerCount: $players,
            capturedAt: new \DateTimeImmutable($at),
        );
    }

    /**
     * L'escrow compte dans la masse.
     *
     * Des Gils mis en enchere ont quitte une bourse sans etre detruits. Les
     * omettre ferait lire une deflation a chaque fois que le marche se remplit.
     */
    public function testEscrowCountsTowardTheSupply(): void
    {
        $measure = new GilsSupplyMeasure(
            playerGils: 1000,
            guildGils: 500,
            shopGils: 200,
            escrowGils: 300,
            playerCount: 2,
        );

        $this->assertSame(2000, $measure->total());
        $this->assertSame(1000.0, $measure->perCapita());
    }

    /**
     * Une population qui double n'est pas de l'inflation.
     *
     * C'est la raison d'etre du ratio par tete : le total brut a double, la
     * masse par personnage n'a pas bouge d'un centieme.
     */
    public function testDoublingThePopulationIsNotInflation(): void
    {
        $trend = new GilsSupplyTrend(
            $this->snapshot(10_000, 10, '2026-07-01'),
            $this->snapshot(20_000, 20, '2026-07-08'),
            7,
        );

        $this->assertSame(0.0, $trend->perCapitaChangePercent());
        $this->assertFalse($trend->isInflationary());
    }

    /**
     * Une masse par tete qui gonfle declenche l'alerte.
     */
    public function testRisingPerCapitaSupplyRaisesAnAlert(): void
    {
        $trend = new GilsSupplyTrend(
            $this->snapshot(10_000, 10, '2026-07-01'),
            $this->snapshot(13_000, 10, '2026-07-08'),
            7,
        );

        $this->assertEqualsWithDelta(30.0, $trend->perCapitaChangePercent(), 0.01);
        $this->assertTrue($trend->isInflationary());
        $this->assertFalse($trend->isDeflationary());
    }

    /**
     * Une masse qui fond aussi vite merite la meme attention.
     */
    public function testCollapsingPerCapitaSupplyRaisesAnAlert(): void
    {
        $trend = new GilsSupplyTrend(
            $this->snapshot(10_000, 10, '2026-07-01'),
            $this->snapshot(6_000, 10, '2026-07-08'),
            7,
        );

        $this->assertTrue($trend->isDeflationary());
        $this->assertFalse($trend->isInflationary());
    }

    /**
     * Un ecart de 3 jours n'est pas compare tel quel a un seuil hebdomadaire.
     *
     * La tache planifiee peut sauter un tour ; sans normalisation, une hausse
     * de 10 % sur 3 jours passerait sous un seuil de 15 % alors qu'elle vaut
     * plus de 23 % a la semaine.
     */
    public function testShortIntervalsAreNormalisedToAWeek(): void
    {
        $trend = new GilsSupplyTrend(
            $this->snapshot(10_000, 10, '2026-07-01'),
            $this->snapshot(11_000, 10, '2026-07-04'),
            7,
        );

        $this->assertSame(3, $trend->elapsedDays());
        $this->assertEqualsWithDelta(10.0, $trend->perCapitaChangePercent(), 0.01);
        $this->assertEqualsWithDelta(23.33, $trend->weeklyChangePercent(), 0.01);
        $this->assertTrue($trend->isInflationary());
    }

    /**
     * Une base a zero rend la variation indefinie, pas infinie.
     *
     * Un serveur neuf n'a pas a declencher une alerte d'inflation permanente.
     */
    public function testAZeroBaselineYieldsNoPercentage(): void
    {
        $trend = new GilsSupplyTrend(
            $this->snapshot(0, 10, '2026-07-01'),
            $this->snapshot(50_000, 10, '2026-07-08'),
            7,
        );

        $this->assertNull($trend->perCapitaChangePercent());
        $this->assertNull($trend->weeklyChangePercent());
        $this->assertFalse($trend->isInflationary());
        $this->assertFalse($trend->isDeflationary());
    }

    /**
     * Un serveur vide ne divise pas par zero.
     */
    public function testAnEmptyServerHasNoPerCapitaSupply(): void
    {
        $this->assertSame(0.0, $this->snapshot(5_000, 0, '2026-07-01')->getPerCapita());
    }

    /**
     * Le seuil par defaut est bien celui du service.
     */
    public function testThresholdDefaultsToTheServiceConstant(): void
    {
        $justUnder = new GilsSupplyTrend(
            $this->snapshot(10_000, 10, '2026-07-01'),
            $this->snapshot((int) (10_000 * (1 + (GilsSupplyService::WEEKLY_ALERT_PERCENT - 1) / 100)), 10, '2026-07-08'),
            7,
        );
        $justOver = new GilsSupplyTrend(
            $this->snapshot(10_000, 10, '2026-07-01'),
            $this->snapshot((int) (10_000 * (1 + (GilsSupplyService::WEEKLY_ALERT_PERCENT + 1) / 100)), 10, '2026-07-08'),
            7,
        );

        $this->assertFalse($justUnder->isInflationary());
        $this->assertTrue($justOver->isInflationary());
    }
}
