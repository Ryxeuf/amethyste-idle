<?php

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\GameEvent;
use App\Enum\ConsequenceTide;
use App\GameEngine\Season\ConsequenceTideDefinitionException;
use App\GameEngine\Season\ConsequenceTideDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * La composition d'une maree consequence est de la **donnee** (FOY-15).
 *
 * Le chargeur existe pour qu'un arc mal ecrit echoue a la lecture plutot que de
 * se decouvrir six semaines plus tard sur un ecran de saison. Deux defauts sont
 * silencieux par nature, et c'est contre eux qu'il est ecrit : **un trou entre
 * deux beats** (des jours sans rien, dont personne ne se plaindrait) et **un arc
 * qui ne couvre pas la maree** (une fin de saison muette).
 */
class ConsequenceTideDefinitionLoaderTest extends TestCase
{
    private ConsequenceTideDefinitionLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new ConsequenceTideDefinitionLoader(\dirname(__DIR__, 4));
    }

    /**
     * Le fichier livre est valide, et declare une maree pour chaque cas de
     * l'enum. Un cas sans composition donnerait un theme affiche derriere lequel
     * il ne se passerait rien.
     */
    public function testTheShippedFileDeclaresEveryTide(): void
    {
        $definition = $this->loader->load();

        self::assertGreaterThan(0, $definition['paleness_threshold']);
        foreach (ConsequenceTide::cases() as $tide) {
            self::assertArrayHasKey($tide->value, $definition['tides']);
            self::assertCount(4, $definition['tides'][$tide->value]['beats']);
            self::assertNotSame('', $definition['tides'][$tide->value]['theme']);
        }
    }

    public function testTheShippedArcsCoverTheWholeTide(): void
    {
        foreach ($this->loader->load()['tides'] as $tide) {
            $last = $tide['beats'][3];
            self::assertSame(0, $tide['beats'][0]['start_day']);
            self::assertSame(ConsequenceTideDefinitionLoader::TIDE_DAYS, $last['end_day']);
        }
    }

    public function testTheShippedArcsFollowTheCanonicalBeatOrder(): void
    {
        $expected = [GameEvent::BEAT_AMORCE, GameEvent::BEAT_MONTEE, GameEvent::BEAT_CLIMAX, GameEvent::BEAT_RESOLUTION];

        foreach ($this->loader->load()['tides'] as $tide) {
            self::assertSame($expected, array_column($tide['beats'], 'beat'));
            self::assertSame([1, 2, 3, 4], array_column($tide['beats'], 'order'));
        }
    }

    // =====================================================================
    // Ce que le chargeur refuse
    // =====================================================================

    /**
     * Un trou entre deux beats laisserait des jours sans beat actif, et rien ne
     * le signalerait : l'ecran afficherait simplement moins de choses.
     */
    public function testAGapBetweenTwoBeatsIsRefused(): void
    {
        $raw = $this->validRaw();
        $raw['tides']['paleness']['beats'][1]['start_day'] = 8;

        $this->expectException(ConsequenceTideDefinitionException::class);
        $this->expectExceptionMessageMatches('/les beats sont contigus/');

        $this->loader->normalize($raw);
    }

    public function testAnArcThatStopsShortOfTheTideIsRefused(): void
    {
        $raw = $this->validRaw();
        $raw['tides']['paleness']['beats'][3]['end_day'] = 25;

        $this->expectException(ConsequenceTideDefinitionException::class);
        $this->expectExceptionMessageMatches('/couvrir la maree entiere/');

        $this->loader->normalize($raw);
    }

    public function testBeatsOutOfCanonicalOrderAreRefused(): void
    {
        $raw = $this->validRaw();
        $raw['tides']['paleness']['beats'][1]['beat'] = GameEvent::BEAT_CLIMAX;

        $this->expectException(ConsequenceTideDefinitionException::class);
        $this->expectExceptionMessageMatches('/amorce -> montee -> climax -> resolution/');

        $this->loader->normalize($raw);
    }

    /**
     * Une maree declaree par l'enum et absente du fichier donnerait un theme
     * selectionnable dont l'arc serait vide.
     */
    public function testAMissingTideIsRefused(): void
    {
        $raw = $this->validRaw();
        unset($raw['tides']['crue_call']);

        $this->expectException(ConsequenceTideDefinitionException::class);
        $this->expectExceptionMessageMatches('/is missing from/');

        $this->loader->normalize($raw);
    }

    public function testAThresholdBelowOneIsRefused(): void
    {
        $raw = $this->validRaw();
        $raw['paleness_threshold'] = 0;

        $this->expectException(ConsequenceTideDefinitionException::class);
        $this->expectExceptionMessageMatches('/positive integer/');

        $this->loader->normalize($raw);
    }

    public function testAnArcMissingABeatIsRefused(): void
    {
        $raw = $this->validRaw();
        array_pop($raw['tides']['paleness']['beats']);

        $this->expectException(ConsequenceTideDefinitionException::class);
        $this->expectExceptionMessageMatches('/exactly 4 beats/');

        $this->loader->normalize($raw);
    }

    public function testAMissingFileIsRefused(): void
    {
        $this->expectException(ConsequenceTideDefinitionException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->loader->load('/nowhere/consequence_tides.yaml');
    }

    /**
     * @return array<string, mixed>
     */
    private function validRaw(): array
    {
        return [
            'paleness_threshold' => 6,
            'tides' => [
                'paleness' => ['theme' => 'La Pâleur', 'beats' => $this->beats('La Pâleur')],
                'crue_call' => ['theme' => 'L\'Appel de la Crue', 'beats' => $this->beats('L\'Appel de la Crue')],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function beats(string $theme): array
    {
        $names = [GameEvent::BEAT_AMORCE, GameEvent::BEAT_MONTEE, GameEvent::BEAT_CLIMAX, GameEvent::BEAT_RESOLUTION];

        $beats = [];
        foreach ($names as $index => $name) {
            $beats[] = [
                'beat' => $name,
                'name' => sprintf('%s — %s', $theme, $name),
                'description' => 'Ce qui se passe pendant ce beat.',
                'start_day' => $index * 7,
                'end_day' => ($index + 1) * 7,
            ];
        }

        return $beats;
    }
}
