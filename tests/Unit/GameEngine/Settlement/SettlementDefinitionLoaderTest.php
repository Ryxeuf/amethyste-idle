<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\SettlementDefinitionException;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Le chargeur de parametres de foyer (FOY-01).
 *
 * Ce que ces tests protegent n'est pas la lecture d'un YAML : c'est qu'un
 * parametre de foyer **faux echoue bruyamment**. Un seuil desordonne, un indice
 * mal orthographie ou un plafond anti-exploit inoperant ne se voient sur aucun
 * ecran — ils se voient six semaines plus tard, quand une zone a cesse de monter
 * sans que personne ne sache pourquoi.
 */
class SettlementDefinitionLoaderTest extends TestCase
{
    private SettlementDefinitionLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new SettlementDefinitionLoader('/project');
    }

    public function testDefaultFileTargetsSettlementsConfig(): void
    {
        self::assertSame('/project/config/game/settlements.yaml', $this->loader->defaultFile());
    }

    public function testNormalizeReadsAValidDefinition(): void
    {
        $result = $this->loader->normalize($this->validRaw());

        self::assertSame(['camp' => 150, 'hamlet' => 1200, 'town' => 8000, 'city' => 25000, 'metropolis' => 60000], $result['ranks']);
        self::assertSame(0.02, $result['decay_rate']);
        self::assertSame(0.25, $result['dominance_margin']);
        self::assertSame(28, $result['sustain_days']);
        self::assertSame(SettlementRank::Hamlet, $result['minimum_type_rank']);
        self::assertSame(60, $result['daily_cap_per_player']);
        self::assertSame(40, $result['diminishing_threshold']);
        self::assertSame(0.5, $result['diminishing_factor']);
        self::assertSame(28, $result['grace_days']);
        self::assertSame(2, $result['rebuild_multiplier']);
        self::assertSame(SettlementRank::Camp, $result['seed']['marais']['rank']);
        self::assertSame(400, $result['seed']['marais']['stock']);
        self::assertSame('batie sur la Voute', $result['without_settlement']['lumiere']);
        self::assertSame(SettlementRank::Town, $result['services']['regional_market']);
        self::assertSame(['shop' => 'boutiques existantes'], $result['never_gated']);
    }

    public function testSpreadIsNotAFifthIndex(): void
    {
        $result = $this->loader->normalize($this->validRaw());

        $kill = $result['sediment']['mob_kill'];
        self::assertSame(SettlementIndex::War, $kill->index);
        self::assertFalse($kill->isSpread());
        self::assertSame(1.0, $kill->grains);

        // Traverser une zone n'est ni du negoce ni de la guerre : ca nourrit les
        // quatre, donc ca ne donne jamais d'identite a la ville.
        $travel = $result['sediment']['travel'];
        self::assertNull($travel->index);
        self::assertTrue($travel->isSpread());
        self::assertSame(0.2, $travel->grains);
    }

    public function testRankThresholdsMustIncrease(): void
    {
        $raw = $this->validRaw();
        $raw['ranks']['town'] = 900; // sous le Hameau

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/must increase/');
        $this->loader->normalize($raw);
    }

    public function testEveryRankNeedsAThreshold(): void
    {
        $raw = $this->validRaw();
        unset($raw['ranks']['city']);

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/"city"/');
        $this->loader->normalize($raw);
    }

    /**
     * Un taux de decroissance nul fige le monde ; un taux a 1 le vide chaque
     * nuit. Les deux extremes doivent etre refuses a la lecture.
     */
    public function testDecayRateMustStayStrictlyBetweenZeroAndOne(): void
    {
        foreach ([0, 1, 1.5, -0.1, 'beaucoup'] as $bad) {
            $raw = $this->validRaw();
            $raw['decay']['daily_rate'] = $bad;

            try {
                $this->loader->normalize($raw);
                self::fail(sprintf('Decay rate "%s" should have been rejected.', var_export($bad, true)));
            } catch (SettlementDefinitionException $e) {
                self::assertStringContainsString('decay.daily_rate', $e->getMessage());
            }
        }
    }

    public function testUnknownSedimentIndexIsRejected(): void
    {
        $raw = $this->validRaw();
        $raw['sediment']['mob_kill']['index'] = 'warr';

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/unknown index/');
        $this->loader->normalize($raw);
    }

    public function testSedimentGrainsMustBePositive(): void
    {
        $raw = $this->validRaw();
        $raw['sediment']['travel']['grains'] = 0;

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/positive "grains"/');
        $this->loader->normalize($raw);
    }

    /**
     * Le seuil de rendements decroissants doit mordre **avant** le plafond,
     * sinon il est ecrit et sans effet.
     */
    public function testDiminishingThresholdMustStayBelowTheCap(): void
    {
        $raw = $this->validRaw();
        $raw['anti_exploit']['diminishing_threshold'] = 60;

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/must stay below/');
        $this->loader->normalize($raw);
    }

    /**
     * Un facteur de 1 ne ralentit rien et un facteur de 0 coupe net : dans les
     * deux cas la regle serait ecrite et ne dirait pas ce qu'elle fait.
     */
    public function testDiminishingFactorMustBeARate(): void
    {
        foreach ([0, 1, 1.5, -0.5, 'moitie'] as $bad) {
            $raw = $this->validRaw();
            $raw['anti_exploit']['diminishing_factor'] = $bad;

            try {
                $this->loader->normalize($raw);
                self::fail(sprintf('Diminishing factor "%s" should have been rejected.', var_export($bad, true)));
            } catch (SettlementDefinitionException $e) {
                self::assertStringContainsString('anti_exploit.diminishing_factor', $e->getMessage());
            }
        }
    }

    public function testSeedRankMustBeKnown(): void
    {
        $raw = $this->validRaw();
        $raw['seed']['marais']['rank'] = 'capitale';

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/seed\.marais\.rank/');
        $this->loader->normalize($raw);
    }

    /**
     * Le point de `without_settlement` : sans raison ecrite, on ne distingue pas
     * une zone volontairement sans foyer d'un oubli de configuration.
     */
    public function testZoneWithoutSettlementMustStateWhy(): void
    {
        $raw = $this->validRaw();
        $raw['without_settlement']['lumiere'] = '   ';

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/must state its reason in writing/');
        $this->loader->normalize($raw);
    }

    public function testMissingFileIsReportedAsADefinitionError(): void
    {
        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/not found/');
        $this->loader->load('/project/nowhere/settlements.yaml');
    }

    /**
     * Le bloc d'atelier est **facultatif** : un monde sans bonus reste jouable,
     * et le refuser aurait fait echouer le chargement de toute configuration
     * anterieure a FOY-07.
     */
    public function testTheWorkshopBlockIsOptional(): void
    {
        $workshop = $this->loader->normalize($this->validRaw())['workshop'];

        self::assertSame(0, $workshop['cap']);
        self::assertSame([], $workshop['zone_line']);
    }

    /**
     * Le defaut que ce chargeur existe pour attraper : une **ligne muette**. Une
     * zone qui nomme une ligne inconnue continuerait de fonctionner, n'accorderait
     * jamais son bonus, et personne ne s'en apercevrait avant de se demander
     * pourquoi une Metropole ne donne rien.
     */
    public function testAZoneCannotNameAProductionLineThatDoesNotExist(): void
    {
        $raw = $this->validRaw();
        $raw['workshop'] = [
            'line_bonus' => ['metal' => ['forgeron' => 3]],
            'zone_line' => ['mines' => 'obsidienne'],
        ];

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/absent from "workshop.line_bonus"/');

        $this->loader->normalize($raw);
    }

    public function testAnUnknownSettlementTypeIsRefusedInTheWorkshopTable(): void
    {
        $raw = $this->validRaw();
        $raw['workshop'] = ['type_bonus' => ['comptoir' => ['forgeron' => 3]]];

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/unknown settlement type/');

        $this->loader->normalize($raw);
    }

    /**
     * Le foyer ajoute, il ne retranche jamais : une ville qui rendrait un etabli
     * moins bon qu'ailleurs serait une punition pour l'avoir frequentee.
     */
    public function testAWorkshopBonusCannotBeNegative(): void
    {
        $raw = $this->validRaw();
        $raw['workshop'] = ['rank_bonus' => ['town' => -1]];

        $this->expectException(SettlementDefinitionException::class);
        $this->expectExceptionMessageMatches('/must be a non-negative integer/');

        $this->loader->normalize($raw);
    }

    /**
     * @return array<string, mixed>
     */
    private function validRaw(): array
    {
        return [
            'ranks' => ['camp' => 150, 'hamlet' => 1200, 'town' => 8000, 'city' => 25000, 'metropolis' => 60000],
            'decay' => ['daily_rate' => 0.02],
            'type' => ['dominance_margin' => 0.25, 'sustain_days' => 28, 'minimum_rank' => 'hamlet'],
            'sediment' => [
                'mob_kill' => ['index' => 'war', 'grains' => 1],
                'quest' => ['index' => 'lore', 'grains' => 5],
                'travel' => ['index' => 'spread', 'grains' => 0.2],
            ],
            'anti_exploit' => ['daily_cap_per_player' => 60, 'diminishing_threshold' => 40, 'diminishing_factor' => 0.5],
            'regression' => ['grace_days' => 28, 'max_ranks_per_tide' => 1, 'rebuild_multiplier' => 2],
            'services' => ['regional_market' => ['minimum_rank' => 'town']],
            'never_gated' => ['shop' => 'boutiques existantes'],
            'seed' => ['marais' => ['rank' => 'camp', 'stock' => 400]],
            'without_settlement' => ['lumiere' => 'batie sur la Voute'],
        ];
    }
}
