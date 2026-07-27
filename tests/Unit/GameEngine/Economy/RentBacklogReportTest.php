<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\GameEngine\Economy\RentBacklogReport;
use PHPUnit\Framework\TestCase;

/**
 * Lecture de l'arriere de loyers (tache 134, jalon F.0).
 */
class RentBacklogReportTest extends TestCase
{
    public function testAnEmptyBacklogNeedsNoAction(): void
    {
        $report = new RentBacklogReport(houseCount: 0, shopCount: 0, worstHousePeriods: 0, worstShopPeriods: 0);

        $this->assertTrue($report->isEmpty());
        $this->assertSame(0, $report->dailyChargesAvoided());
    }

    /**
     * Un seul retard suffit a rendre l'arriere non vide.
     */
    public function testASingleLateShopIsNotAnEmptyBacklog(): void
    {
        $report = new RentBacklogReport(houseCount: 0, shopCount: 1, worstHousePeriods: 0, worstShopPeriods: 3);

        $this->assertFalse($report->isEmpty());
    }

    /**
     * C'est le pire des deux retards qui donne le nombre de jours de rafale.
     *
     * Chaque execution ne rattrape qu'une periode : les prelevements quotidiens
     * durent tant que le retard le plus lourd n'est pas resorbe, quel que soit
     * le type de bien concerne.
     */
    public function testTheWorstBacklogDrivesTheBurstLength(): void
    {
        $houseIsWorse = new RentBacklogReport(houseCount: 4, shopCount: 2, worstHousePeriods: 26, worstShopPeriods: 3);
        $shopIsWorse = new RentBacklogReport(houseCount: 4, shopCount: 2, worstHousePeriods: 3, worstShopPeriods: 26);

        $this->assertSame(26, $houseIsWorse->dailyChargesAvoided());
        $this->assertSame(26, $shopIsWorse->dailyChargesAvoided());
    }
}
