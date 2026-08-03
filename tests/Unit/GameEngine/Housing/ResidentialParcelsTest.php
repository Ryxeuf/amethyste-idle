<?php

namespace App\Tests\Unit\GameEngine\Housing;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementRank;
use App\GameEngine\Housing\ResidentialParcels;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\World\WorldScaleService;
use App\Repository\PlayerHouseRepository;
use App\Repository\SettlementRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Les parcelles residentielles par rang (FOY-18).
 *
 * La liste explicite devient une regle : tout foyer Hameau+ loge, a capacite
 * par rang mise a l'echelle du monde — et la capacite ne gate QUE l'ouverture
 * de nouvelles parcelles : une regression, ou une contraction de W, laisse
 * toutes les demeures en place. Jamais d'expulsion (decision A).
 */
class ResidentialParcelsTest extends TestCase
{
    private SettlementRepository&MockObject $settlementRepository;
    private PlayerHouseRepository&MockObject $houseRepository;
    private float $worldScale = 1.0;
    private ?Settlement $settlement = null;
    private int $housesInZone = 0;

    protected function setUp(): void
    {
        $this->settlementRepository = $this->createMock(SettlementRepository::class);
        $this->settlementRepository->method('findOneByZone')->willReturnCallback(fn (): ?Settlement => $this->settlement);
        $this->houseRepository = $this->createMock(PlayerHouseRepository::class);
        $this->houseRepository->method('countInZone')->willReturnCallback(fn (): int => $this->housesInZone);
    }

    private function parcels(): ResidentialParcels
    {
        // Le vrai fichier livre : les capacites testees sont celles du jeu.
        $loader = new SettlementDefinitionLoader(\dirname(__DIR__, 4));

        $worldScale = $this->createMock(WorldScaleService::class);
        $worldScale->method('current')->willReturnCallback(fn (): float => $this->worldScale);

        return new ResidentialParcels($this->settlementRepository, $loader, $worldScale, $this->houseRepository);
    }

    private function settle(SettlementRank $rank): void
    {
        $settlement = $this->createMock(Settlement::class);
        $settlement->method('getRank')->willReturn($rank);
        $this->settlement = $settlement;
    }

    private function zone(): Zone
    {
        return (new Zone())->setSlug('foret-des-murmures')->setName('Foret des Murmures');
    }

    public function testCapacityGrowsWithRank(): void
    {
        $parcels = $this->parcels();

        self::assertSame(8, $parcels->scaledCapacity(SettlementRank::Hamlet));
        self::assertSame(20, $parcels->scaledCapacity(SettlementRank::Town));
        self::assertSame(40, $parcels->scaledCapacity(SettlementRank::City));
        self::assertSame(80, $parcels->scaledCapacity(SettlementRank::Metropolis));
    }

    /**
     * On ne s'installe pas dans ce qui peut disparaitre : Ruine et Campement
     * ne logent pas.
     */
    public function testACampDoesNotHouse(): void
    {
        $parcels = $this->parcels();

        self::assertNull($parcels->scaledCapacity(SettlementRank::Camp));
        self::assertNull($parcels->scaledCapacity(SettlementRank::Ruin));

        $this->settle(SettlementRank::Camp);
        self::assertFalse($parcels->isRankResidential($this->zone()));
        self::assertNull($parcels->panel($this->zone()));
    }

    /**
     * BALANCE § 24.3 : W met les seuils a l'echelle, jamais les taux — la
     * capacite suit le monde, comme les seuils de rang.
     */
    public function testTheWorldFactorScalesCapacity(): void
    {
        $this->worldScale = 2.0;

        self::assertSame(16, $this->parcels()->scaledCapacity(SettlementRank::Hamlet));

        $this->worldScale = 0.5;
        self::assertSame(4, $this->parcels()->scaledCapacity(SettlementRank::Hamlet));
    }

    public function testAZoneWithoutASettlementIsNotRankResidential(): void
    {
        $this->settlement = null;

        self::assertFalse($this->parcels()->isRankResidential($this->zone()));
        self::assertNull($this->parcels()->panel($this->zone()));
    }

    public function testAFullSettlementClosesNewParcels(): void
    {
        $this->settle(SettlementRank::Hamlet);
        $this->housesInZone = 8;

        self::assertFalse($this->parcels()->canOpenParcel($this->zone()));
        self::assertSame(0, $this->parcels()->panel($this->zone())['free']);
    }

    public function testAFreeParcelOpens(): void
    {
        $this->settle(SettlementRank::Hamlet);
        $this->housesInZone = 7;

        self::assertTrue($this->parcels()->canOpenParcel($this->zone()));
        self::assertSame(['capacity' => 8, 'taken' => 7, 'free' => 1], $this->parcels()->panel($this->zone()));
    }

    /**
     * Jamais d'expulsion : une regression (ou une contraction de W) peut
     * laisser plus de demeures que de parcelles. La seule consequence est que
     * rien ne s'ouvre — `free` s'ecrase a zero, personne ne part.
     */
    public function testMoreHousesThanParcelsEvictsNoOne(): void
    {
        $this->settle(SettlementRank::Hamlet);
        $this->housesInZone = 20;

        $panel = $this->parcels()->panel($this->zone());

        self::assertSame(['capacity' => 8, 'taken' => 20, 'free' => 0], $panel, 'Le surnombre se lit, il ne se corrige jamais.');
        self::assertFalse($this->parcels()->canOpenParcel($this->zone()));
        self::assertTrue($this->parcels()->isRankResidential($this->zone()), 'La zone reste residentielle : les demeures existantes vivent leur vie.');
    }
}
