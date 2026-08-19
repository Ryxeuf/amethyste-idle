<?php

namespace App\Tests\Unit\GameEngine\Season;

use App\Entity\App\GameEvent;
use App\Enum\ConsequenceTide;
use App\GameEngine\Season\TideDefinitionException;
use App\GameEngine\Season\TideDefinitionLoader;
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
class TideDefinitionLoaderTest extends TestCase
{
    private TideDefinitionLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new TideDefinitionLoader(\dirname(__DIR__, 4));
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
            self::assertArrayHasKey($tide->value, $definition['consequences']);
            self::assertCount(4, $definition['consequences'][$tide->value]['beats']);
            self::assertNotSame('', $definition['consequences'][$tide->value]['theme']);
        }
    }

    /**
     * Les arcs des **deux** voix composables couvrent la maree entiere. Une
     * rotation trouee serait aussi muette qu'une consequence trouee.
     */
    public function testTheShippedArcsCoverTheWholeTide(): void
    {
        $definition = $this->loader->load();
        $arcs = array_merge(array_values($definition['consequences']), array_values($definition['rotation']));

        self::assertGreaterThanOrEqual(8, \count($arcs), 'Le fichier ne porte plus les deux voix composables.');

        foreach ($arcs as $tide) {
            $last = $tide['beats'][3];
            self::assertSame(0, $tide['beats'][0]['start_day']);
            self::assertSame(TideDefinitionLoader::TIDE_DAYS, $last['end_day']);
        }
    }

    public function testTheShippedArcsFollowTheCanonicalBeatOrder(): void
    {
        $expected = [GameEvent::BEAT_AMORCE, GameEvent::BEAT_MONTEE, GameEvent::BEAT_CLIMAX, GameEvent::BEAT_RESOLUTION];
        $definition = $this->loader->load();

        foreach (array_merge(array_values($definition['consequences']), array_values($definition['rotation'])) as $tide) {
            self::assertSame($expected, array_column($tide['beats'], 'beat'));
            self::assertSame([1, 2, 3, 4], array_column($tide['beats'], 'order'));
        }
    }

    // =====================================================================
    // La colonne vertebrale et la rotation (NAR-15)
    // =====================================================================

    /**
     * **La colonne reserve les quatre creneaux que le canon nomme.**.
     *
     * M1 est livree en fixtures ; M2, M4, M8 et M13 attendent leur jalon. Sans
     * ce bloc, une rotation prendrait le creneau M2 et « La Première Pierre »
     * n'arriverait jamais — le code n'en connaissait aucun.
     */
    public function testTheSpineReservesTheCanonSlots(): void
    {
        $canon = $this->loader->load()['canon'];

        self::assertSame([2, 4, 8, 13], array_keys($canon));

        foreach ($canon as $slot) {
            self::assertNotSame('', $slot['theme']);
            self::assertMatchesRegularExpression('/^NAR-\d\d$/', $slot['milestone'], 'Un créneau réservé doit nommer le jalon qui l\'écrira.');
        }
    }

    /**
     * **Il n'y a aucun endroit ou ecrire des beats sur la colonne.**.
     *
     * C'est la forme qui tient la regle : le bloc ne peut pas se transformer en
     * partition ecrite d'avance, parce qu'il n'a pas de champ pour ca. *Une
     * colonne vertebrale reserve, elle n'improvise pas.*
     */
    public function testACanonSlotCarriesNoArc(): void
    {
        foreach ($this->loader->load()['canon'] as $slot) {
            self::assertSame(['theme', 'milestone'], array_keys($slot));
        }
    }

    public function testACanonSlotWithoutItsMilestoneIsRefused(): void
    {
        $raw = $this->validRaw();
        unset($raw['canon'][2]['milestone']);

        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/milestone that writes it/');

        $this->loader->normalize($raw);
    }

    /**
     * Un gabarit qui ne nourrit rien ne peut pas etre prescrit : il ne serait
     * jamais tire, et l'erreur se lirait comme un choix d'equilibrage.
     */
    public function testARotationTemplateThatFeedsNothingIsRefused(): void
    {
        $raw = $this->validRaw();
        $raw['rotation']['amber_tide']['feeds'] = [];

        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/at least one sediment index/');

        $this->loader->normalize($raw);
    }

    public function testAnUnknownSedimentIndexIsRefused(): void
    {
        $raw = $this->validRaw();
        $raw['rotation']['amber_tide']['feeds'] = ['glory'];

        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/unknown sediment index/');

        $this->loader->normalize($raw);
    }

    public function testAnEmptyRotationIsRefused(): void
    {
        $raw = $this->validRaw();
        $raw['rotation'] = [];

        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/non-empty mapping/');

        $this->loader->normalize($raw);
    }

    /**
     * Les six gabarits livres couvrent les quatre indices : sans cela, un
     * serveur qui manquerait de rite verrait la rotation lui prescrire autre
     * chose, indefiniment.
     */
    public function testTheShippedRotationCoversEverySedimentIndex(): void
    {
        $covered = [];
        foreach ($this->loader->load()['rotation'] as $template) {
            foreach ($template['feeds'] as $index) {
                $covered[$index->value] = true;
            }
        }

        foreach (\App\Enum\SettlementIndex::cases() as $index) {
            self::assertArrayHasKey($index->value, $covered, sprintf(
                'Aucun gabarit ne nourrit « %s » : un serveur qui en manquerait n\'obtiendrait jamais ce qu\'il lui faut.',
                $index->value,
            ));
        }
    }

    /**
     * Et une clef composable rend son arc, quelle que soit sa voix — c'est ce
     * qui permet au composeur de n'avoir qu'un seul chemin.
     */
    public function testEveryComposableVoiceAnswersTheSameWay(): void
    {
        self::assertNotNull($this->loader->composable('paleness'));
        self::assertNotNull($this->loader->composable('the_choir'));
        self::assertNull($this->loader->composable('la-premiere-pierre'), 'Un créneau canon n\'a pas d\'arc à composer.');
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
        $raw['consequences']['paleness']['beats'][1]['start_day'] = 8;

        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/les beats sont contigus/');

        $this->loader->normalize($raw);
    }

    public function testAnArcThatStopsShortOfTheTideIsRefused(): void
    {
        $raw = $this->validRaw();
        $raw['consequences']['paleness']['beats'][3]['end_day'] = 25;

        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/couvrir la maree entiere/');

        $this->loader->normalize($raw);
    }

    public function testBeatsOutOfCanonicalOrderAreRefused(): void
    {
        $raw = $this->validRaw();
        $raw['consequences']['paleness']['beats'][1]['beat'] = GameEvent::BEAT_CLIMAX;

        $this->expectException(TideDefinitionException::class);
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
        unset($raw['consequences']['crue_call']);

        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/is missing from/');

        $this->loader->normalize($raw);
    }

    public function testAThresholdBelowOneIsRefused(): void
    {
        $raw = $this->validRaw();
        $raw['paleness_threshold'] = 0;

        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/positive integer/');

        $this->loader->normalize($raw);
    }

    public function testAnArcMissingABeatIsRefused(): void
    {
        $raw = $this->validRaw();
        array_pop($raw['consequences']['paleness']['beats']);

        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/exactly 4 beats/');

        $this->loader->normalize($raw);
    }

    public function testAMissingFileIsRefused(): void
    {
        $this->expectException(TideDefinitionException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->loader->load('/nowhere/tides.yaml');
    }

    /**
     * @return array<string, mixed>
     */
    private function validRaw(): array
    {
        return [
            'paleness_threshold' => 6,
            'canon' => [
                2 => ['theme' => 'La Première Pierre', 'milestone' => 'NAR-16'],
            ],
            'rotation' => [
                'amber_tide' => ['theme' => 'La Marée d\'Ambre', 'feeds' => ['lore'], 'beats' => $this->beats('La Marée d\'Ambre')],
            ],
            'consequences' => [
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
