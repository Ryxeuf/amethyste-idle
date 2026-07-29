<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\Game\Item;
use App\Enum\Element;
use App\GameEngine\Economy\ResourceAffinityCatalog;
use App\GameEngine\Economy\ResourceAffinityDefinitionException;
use App\GameEngine\Economy\ResourceAffinityDefinitionLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * L'affinite elementaire d'une ressource — la loi 10 (ZON-36).
 *
 * Ce qui se verrouille ici n'est pas une liste de valeurs mais **la
 * derivation** : la ligne donne le defaut, la correction dit ou le lieu prime.
 * Un test qui epinglerait cinquante couples slug/element aurait fige la table
 * sans jamais verifier la regle — et la loi 10 est une regle, precisement pour
 * qu'on n'ait pas a defendre cinquante decisions arbitraires.
 */
class ResourceAffinityCatalogTest extends TestCase
{
    // =====================================================================
    // La derivation
    // =====================================================================

    /**
     * @return array<string, array{string, Element}>
     */
    public static function lineProvider(): array
    {
        return [
            'le metal est Metal' => ['ore-copper', Element::Metal],
            'les plantes sont Bete' => ['plant-sage', Element::Beast],
            'les poissons sont Eau' => ['fish-trout', Element::Water],
            'le bois est Bete' => ['wood-beech', Element::Beast],
            'les depouilles sont Bete' => ['leather-raw', Element::Beast],
            'le gibier hors prefixe aussi' => ['meat-game', Element::Beast],
        ];
    }

    #[DataProvider('lineProvider')]
    public function testTheLineGivesTheDefault(string $slug, Element $expected): void
    {
        self::assertSame($expected, $this->shipped()->affinityOf($slug));
    }

    /**
     * La signature du lieu prime la ligne — c'est la seconde moitie de la regle,
     * et la seule qui demande une decision.
     *
     * @return array<string, array{string, Element, Element}>
     */
    public static function correctionProvider(): array
    {
        return [
            'le sombracier vient du fond des Mines' => ['ore-darksteel', Element::Metal, Element::Dark],
            'le mithril est le metal que le vent a mis a nu' => ['ore-mithril', Element::Metal, Element::Air],
            'le platine porte le theme Ambre' => ['ore-platinum', Element::Metal, Element::Fire],
            'la foudre dans l\'eau noire' => ['fish-electric-eel', Element::Water, Element::Air],
            'le bois tourbe du Marais' => ['wood-peat', Element::Beast, Element::Dark],
            'le bois petrifie des Dunes' => ['wood-petrified', Element::Beast, Element::Earth],
            'la bete prime la ligne' => ['leather-dragon-scale', Element::Beast, Element::Fire],
        ];
    }

    #[DataProvider('correctionProvider')]
    public function testTheSignatureOfThePlaceOverridesTheLine(string $slug, Element $line, Element $corrected): void
    {
        $catalog = $this->shipped();

        self::assertSame($line, $catalog->lineOf($slug), 'La ligne de reference a change : la correction ne corrige plus rien.');
        self::assertSame($corrected, $catalog->affinityOf($slug));
    }

    /**
     * Une correction qui rendrait la meme valeur que sa ligne n'est pas une
     * decision : c'est du bruit dans une table dont l'interet est de se
     * compter. Le jour ou une ligne bouge, ce test nomme les corrections
     * devenues inutiles au lieu de les laisser dormir.
     */
    public function testNoCorrectionRepeatsItsOwnLine(): void
    {
        $catalog = $this->shipped();

        $redundant = [];
        foreach ($catalog->corrections() as $slug => $element) {
            if ($catalog->lineOf($slug) === $element) {
                $redundant[] = $slug;
            }
        }

        self::assertSame([], $redundant, 'Ces corrections repetent leur ligne : elles ne decident de rien.');
    }

    // =====================================================================
    // Le substrat, et ce qui n'est pas une ressource
    // =====================================================================

    /**
     * « L'amethyste est le substrat, pas un flux » (GAME_WORLD § 2.2). Lui
     * donner une affinite reviendrait a dire que le temps est fait d'une des
     * choses qu'il porte.
     */
    public function testAmethystIsCoveredAndCarriesNoAffinity(): void
    {
        $catalog = $this->shipped();

        self::assertTrue($catalog->covers('ore-amethyst-crystal'), 'L\'amethyste est bien une ressource.');
        self::assertNull($catalog->affinityOf('ore-amethyst-crystal'));
    }

    /**
     * Le `null` du substrat et celui du hors-perimetre se ressemblent ; les
     * confondre perdrait l'information qui compte. Une epee n'a pas d'affinite
     * parce que la question ne se pose pas ; l'amethyste en a une, et c'est
     * « aucune ».
     */
    public function testTheTwoKindsOfNullAreDistinguishable(): void
    {
        $catalog = $this->shipped();

        self::assertFalse($catalog->covers('iron-sword'));
        self::assertNull($catalog->affinityOf('iron-sword'));

        self::assertTrue($catalog->covers('ore-amethyst-crystal'));
        self::assertNull($catalog->affinityOf('ore-amethyst-crystal'));
    }

    /**
     * Les pieces de cuir portent le nom de leur matiere. Sans exclusion
     * explicite, un prefixe seul en ferait des matieres premieres.
     */
    public function testLeatherGearIsNotAResource(): void
    {
        $catalog = $this->shipped();

        self::assertFalse($catalog->covers('leather-boots'));
        self::assertNull($catalog->affinityOf('leather-boots'));
        self::assertTrue($catalog->covers('leather-raw'));
    }

    public function testAnItemIsAskedThroughItsSlug(): void
    {
        $ore = new Item();
        $ore->setSlug('ore-mithril');

        self::assertSame(Element::Air, $this->shipped()->affinityOfItem($ore));
        self::assertNull($this->shipped()->affinityOfItem(null));
    }

    // =====================================================================
    // Les refus du loader
    // =====================================================================

    /**
     * Une table sans ligne n'est pas une table etroite : c'est une loi qui ne
     * s'appliquerait nulle part, et rien ne le dirait.
     */
    public function testATableWithoutALineIsRefused(): void
    {
        $this->expectException(ResourceAffinityDefinitionException::class);
        $this->expectExceptionMessageMatches('/declares no line/');

        $this->loader()->normalize(['lines' => []]);
    }

    /**
     * « Aucune » n'est pas une correction : c'est le substrat. Les deux
     * ecritures rendraient la meme valeur et diraient deux choses differentes.
     */
    public function testACorrectionCannotBeNone(): void
    {
        $this->expectException(ResourceAffinityDefinitionException::class);
        $this->expectExceptionMessageMatches('/without_affinity/');

        $this->loader()->normalize([
            'lines' => ['metal' => ['ore-']],
            'corrections' => ['ore-amethyst-crystal' => 'none'],
        ]);
    }

    public function testAnUnknownElementIsRefused(): void
    {
        $this->expectException(ResourceAffinityDefinitionException::class);
        $this->expectExceptionMessageMatches('/unknown element/');

        $this->loader()->normalize([
            'lines' => ['metal' => ['ore-']],
            'corrections' => ['ore-copper' => 'amethyst'],
        ]);
    }

    public function testALineKeyedBySomethingElseThanAnElementIsRefused(): void
    {
        $this->expectException(ResourceAffinityDefinitionException::class);
        $this->expectExceptionMessageMatches('/keyed by element/');

        $this->loader()->normalize(['lines' => ['mining' => ['ore-']]]);
    }

    /**
     * Ecrire une matiere des deux cotes ferait dependre son affinite de l'ordre
     * du code plutot que de la table.
     */
    public function testASlugCannotBeBothCorrectedAndExcluded(): void
    {
        $this->expectException(ResourceAffinityDefinitionException::class);
        $this->expectExceptionMessageMatches('/sit in both/');

        $this->loader()->normalize([
            'lines' => ['metal' => ['ore-']],
            'corrections' => ['ore-copper' => 'fire'],
            'excluded' => ['ore-copper'],
        ]);
    }

    public function testAMissingTableIsRefusedAtLoad(): void
    {
        $this->expectException(ResourceAffinityDefinitionException::class);
        $this->expectExceptionMessageMatches('/not found/');

        (new ResourceAffinityDefinitionLoader('/nowhere'))->load();
    }

    private function loader(): ResourceAffinityDefinitionLoader
    {
        return new ResourceAffinityDefinitionLoader(\dirname(__DIR__, 4));
    }

    private function shipped(): ResourceAffinityCatalog
    {
        return new ResourceAffinityCatalog($this->loader());
    }
}
