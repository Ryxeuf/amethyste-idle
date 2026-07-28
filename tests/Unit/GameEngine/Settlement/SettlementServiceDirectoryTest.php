<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementGate;
use App\GameEngine\Settlement\SettlementServiceDirectory;
use App\Repository\SettlementRepository;
use PHPUnit\Framework\TestCase;

/**
 * Ou mene un service ouvert par le rang (FOY-06).
 *
 * Deux proprietes portent le jalon. La premiere : un service **declare mais pas
 * encore livre** ne produit aucune porte — annoncer l'Autel d'eveil avant que la
 * purete existe donnerait un bouton qui ne mene nulle part, ce qui est pire que
 * l'absence. La seconde : un service **ferme** reste affiche, avec son rang
 * manquant. Le masquer rendrait le palier suivant abstrait au moment precis ou
 * il compte le plus.
 */
class SettlementServiceDirectoryTest extends TestCase
{
    private ?Settlement $settlement = null;
    private Zone $zone;

    protected function setUp(): void
    {
        $this->zone = new Zone();
        $this->settlement = null;
    }

    public function testOnlyWiredServicesProduceADoor(): void
    {
        $this->settlementAt(SettlementRank::Metropolis);

        $services = array_column($this->directory()->forZone($this->zone), 'service');

        // `awakening_altar` et `rented_stall` sont declares et ouverts a ce rang,
        // mais rien ne les sert encore : ils restent une promesse de palier.
        self::assertSame(['regional_market', 'zone_bank'], $services);
    }

    public function testAClosedServiceStaysVisibleWithItsMissingRank(): void
    {
        $this->settlementAt(SettlementRank::Hamlet);

        $rows = $this->directory()->forZone($this->zone);

        self::assertCount(2, $rows);
        foreach ($rows as $row) {
            self::assertFalse($row['open']);
        }
        self::assertSame(SettlementRank::Town, $rows[0]['required']);
        self::assertSame('app_game_auction', $rows[0]['route']);
    }

    public function testTheMarketOpensAtTownAndTheBankStaysClosed(): void
    {
        $this->settlementAt(SettlementRank::Town);

        $rows = $this->directory()->forZone($this->zone);

        self::assertSame('regional_market', $rows[0]['service']);
        self::assertTrue($rows[0]['open']);
        self::assertSame('zone_bank', $rows[1]['service']);
        self::assertFalse($rows[1]['open']);
    }

    /**
     * Une zone sans foyer n'a pas de rang : rien de nouveau n'y ouvre. Ce n'est
     * pas une privation — le HV et la banque restent joignables par le menu,
     * quel que soit le rang (decision A).
     */
    public function testAZoneWithoutSettlementOpensNothingNew(): void
    {
        $this->settlement = null;

        foreach ($this->directory()->forZone($this->zone) as $row) {
            self::assertFalse($row['open']);
        }
    }

    public function testAPromotionNamesWhatItOpens(): void
    {
        $crossed = $this->directory()->crossedBetween(SettlementRank::Hamlet, SettlementRank::Town);

        self::assertSame(['regional_market'], $crossed);
    }

    /**
     * La descente rend exactement les memes services que la montee inverse. Les
     * enumerer separement aurait laisse deriver l'un des deux cotes — et c'est
     * le cote « ferme » qui aurait ete oublie.
     */
    public function testARegressionNamesTheSameServicesAsTheClimb(): void
    {
        $directory = $this->directory();

        self::assertSame(
            $directory->crossedBetween(SettlementRank::Town, SettlementRank::City),
            $directory->crossedBetween(SettlementRank::City, SettlementRank::Town),
        );
    }

    public function testSeveralRanksAtOnceNameEveryServiceCrossed(): void
    {
        $crossed = $this->directory()->crossedBetween(SettlementRank::Camp, SettlementRank::Metropolis);

        self::assertSame(['regional_market', 'zone_bank', 'rented_stall', 'awakening_altar'], $crossed);
    }

    public function testARankChangeThatCrossesNothingNamesNothing(): void
    {
        self::assertSame([], $this->directory()->crossedBetween(SettlementRank::Camp, SettlementRank::Hamlet));
    }

    private function settlementAt(SettlementRank $rank): void
    {
        $this->settlement = new Settlement($this->zone);
        $this->settlement->setRank($rank);
    }

    private function directory(): SettlementServiceDirectory
    {
        $repository = $this->createMock(SettlementRepository::class);
        $repository->method('findOneByZone')->willReturnCallback(
            fn (Zone $zone): ?Settlement => $zone === $this->zone ? $this->settlement : null,
        );

        $loader = $this->createMock(SettlementDefinitionLoader::class);
        $loader->method('load')->willReturn([
            'ranks' => ['camp' => 150, 'hamlet' => 1200, 'town' => 8000, 'city' => 25000, 'metropolis' => 60000],
            'decay_rate' => 0.02,
            'dominance_margin' => 0.25,
            'sustain_days' => 28,
            'minimum_type_rank' => SettlementRank::Hamlet,
            'sediment' => [],
            'daily_cap_per_player' => 60,
            'diminishing_threshold' => 40,
            'diminishing_factor' => 0.5,
            'grace_days' => 28,
            'rebuild_multiplier' => 2,
            'services' => [
                // Declare dans le desordre : le repertoire doit ranger par rang,
                // sinon l'ecran listerait la Metropole avant le Bourg.
                'awakening_altar' => SettlementRank::Metropolis,
                'zone_bank' => SettlementRank::City,
                'rented_stall' => SettlementRank::City,
                'regional_market' => SettlementRank::Town,
            ],
            'never_gated' => ['shop' => 'boutiques existantes'],
            'seed' => [],
            'without_settlement' => [],
        ]);

        return new SettlementServiceDirectory(new SettlementGate($repository, $loader));
    }
}
