<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\SettlementDefinitionException;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementGate;
use App\Repository\SettlementRepository;
use PHPUnit\Framework\TestCase;

/**
 * Ce que le rang ouvre, et ce qu'il n'a pas le droit de fermer (FOY-05).
 *
 * La propriete la plus importante n'est pas qu'un service se ferme : c'est que
 * **presque rien ne se ferme**. Un joueur ne doit jamais decouvrir qu'un service
 * dont il se servait hier lui est interdit aujourd'hui parce que d'autres ont
 * cesse de frequenter sa zone.
 */
class SettlementGateTest extends TestCase
{
    private ?Settlement $settlement = null;
    private Zone $zone;

    protected function setUp(): void
    {
        $this->zone = new Zone();
        $this->settlement = null;
    }

    /**
     * Le defaut est **ouvert**. Gater se declare ; s'ouvrir ne se declare pas.
     * C'est l'inverse d'un systeme de permissions, et c'est voulu : un oubli de
     * configuration doit laisser jouer, pas bloquer.
     */
    public function testAServiceThatIsNotDeclaredIsOpenEverywhere(): void
    {
        $gate = $this->gate();

        self::assertTrue($gate->allows($this->zone, 'shop'));
        self::assertTrue($gate->allows($this->zone, 'pnj'));
        self::assertTrue($gate->allows($this->zone, 'quelque_chose_de_jamais_declare'));
    }

    /**
     * Et cela vaut aussi dans une zone **sans foyer** : Lumiere garde ses
     * boutiques, quoi qu'il arrive au reste du monde.
     */
    public function testExistingServicesStayOpenEvenWhereNoSettlementExists(): void
    {
        $this->settlement = null;

        self::assertTrue($this->gate()->allows($this->zone, 'shop'));
    }

    public function testAGatedServiceOpensAtItsRank(): void
    {
        $this->settlementAt(SettlementRank::Town);

        $gate = $this->gate();
        self::assertTrue($gate->allows($this->zone, 'regional_market'));
        self::assertFalse($gate->allows($this->zone, 'zone_bank'));
    }

    public function testAGatedServiceStaysClosedBelowItsRank(): void
    {
        $this->settlementAt(SettlementRank::Hamlet);

        self::assertFalse($this->gate()->allows($this->zone, 'regional_market'));
    }

    public function testAHigherRankOpensEverythingBelowIt(): void
    {
        $this->settlementAt(SettlementRank::Metropolis);

        $gate = $this->gate();
        foreach (array_keys($gate->services()) as $service) {
            self::assertTrue($gate->allows($this->zone, $service), sprintf('"%s" devrait etre ouvert a la Metropole.', $service));
        }
    }

    /**
     * Une zone sans foyer n'a pas de rang, donc pas de service nouveau. Ce n'est
     * pas une privation : rien ne s'accumule sur la Voute.
     */
    public function testAZoneWithoutSettlementHasNoNewServices(): void
    {
        $this->settlement = null;

        $verdict = $this->gate()->verdict($this->zone, 'zone_bank');

        self::assertFalse($verdict->allowed);
        self::assertSame(SettlementRank::Ruin, $verdict->current);
    }

    /**
     * Un refus doit dire ce qui manque. Un bouton grise sans explication est la
     * facon la plus sure de faire croire a un bug.
     */
    public function testARefusalSaysWhatIsMissing(): void
    {
        $this->settlementAt(SettlementRank::Camp);

        $verdict = $this->gate()->verdict($this->zone, 'zone_bank');

        self::assertFalse($verdict->allowed);
        self::assertSame(SettlementRank::City, $verdict->required);
        self::assertSame(SettlementRank::Camp, $verdict->current);
        self::assertSame(3, $verdict->missingRanks());
        self::assertSame('game.settlement.gate.closed', $verdict->messageKey());
        self::assertSame(
            ['%service%' => 'zone_bank', '%required%' => 'city', '%current%' => 'camp'],
            $verdict->messageParams(),
        );
    }

    public function testAnOpenVerdictCarriesNoMessage(): void
    {
        $this->settlementAt(SettlementRank::City);

        $verdict = $this->gate()->verdict($this->zone, 'zone_bank');

        self::assertTrue($verdict->allowed);
        self::assertNull($verdict->messageKey());
        self::assertSame([], $verdict->messageParams());
        self::assertSame(0, $verdict->missingRanks());
    }

    /**
     * Le garde-fou de la decision A, rendu **executable**. Sans lui, une ligne
     * de YAML ecrite de bonne foi suffirait a fermer une boutique que des
     * joueurs utilisaient la veille.
     */
    public function testTheLoaderRefusesToGateAServiceDeclaredNeverGated(): void
    {
        $loader = new SettlementDefinitionLoader('/project');

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/cannot be gated/');

        $loader->normalize([
            'ranks' => ['camp' => 150, 'hamlet' => 1200, 'town' => 8000, 'city' => 25000, 'metropolis' => 60000],
            'decay' => ['daily_rate' => 0.02],
            'type' => ['dominance_margin' => 0.25, 'sustain_days' => 28, 'minimum_rank' => 'hamlet'],
            'sediment' => ['mob_kill' => ['index' => 'war', 'grains' => 1]],
            'anti_exploit' => ['daily_cap_per_player' => 60, 'diminishing_threshold' => 40, 'diminishing_factor' => 0.5],
            'regression' => ['grace_days' => 28, 'max_ranks_per_tide' => 1, 'rebuild_multiplier' => 2],
            'services' => ['shop' => ['minimum_rank' => 'town']],
            'never_gated' => ['shop' => 'boutiques existantes'],
        ]);
    }

    /**
     * La table livree doit rester coherente avec la decision A : aucun service
     * gate ne doit porter le nom d'un service que le monde livre possede deja.
     */
    public function testTheShippedTableGatesOnlyNewServices(): void
    {
        $definition = (new SettlementDefinitionLoader(\dirname(__DIR__, 4)))->load();

        $overlap = array_values(array_intersect(
            array_keys($definition['services']),
            array_keys($definition['never_gated']),
        ));

        self::assertSame([], $overlap);
        self::assertNotEmpty($definition['never_gated'], 'La liste des services jamais gates ne doit pas etre vide.');
    }

    private function settlementAt(SettlementRank $rank): void
    {
        $this->settlement = new Settlement($this->zone);
        $this->settlement->setRank($rank);
    }

    private function gate(): SettlementGate
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
            'services' => [
                'regional_market' => SettlementRank::Town,
                'zone_bank' => SettlementRank::City,
                'awakening_altar' => SettlementRank::Metropolis,
            ],
            'never_gated' => ['shop' => 'boutiques existantes'],
            'seed' => [],
            'without_settlement' => [],
        ]);

        return new SettlementGate($repository, $loader);
    }
}
