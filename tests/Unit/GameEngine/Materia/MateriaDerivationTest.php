<?php

namespace App\Tests\Unit\GameEngine\Materia;

use App\Entity\Game\Spell;
use App\Enum\Element;
use App\GameEngine\Materia\MateriaBlueprint;
use App\GameEngine\Materia\MateriaDerivation;
use PHPUnit\Framework\TestCase;

/**
 * MAT-02 — une materia ne s'ecrit pas, elle se derive (GAME_MATERIA §2.1).
 *
 * Le contrat tient trois choses : la derivation est complete (chaque champ
 * vient du sort ou d'une grille figee), les slugs sont uniques par
 * construction (la convention `m<niveau>-<slug du sort>` herite de l'unicite
 * des slugs de sort), et la rarete n'est declaree nulle part — elle se lit du
 * prefixe de slug, jamais d'un champ.
 */
class MateriaDerivationTest extends TestCase
{
    private MateriaDerivation $derivation;

    protected function setUp(): void
    {
        $this->derivation = new MateriaDerivation();
    }

    private function spell(string $slug, string $name, int $level, Element $element): Spell
    {
        $spell = new Spell();
        $spell->setSlug($slug);
        $spell->setName($name);
        $spell->setLevel($level);
        $spell->setElement($element);

        return $spell;
    }

    public function testDerivationIsComplete(): void
    {
        $spell = $this->spell('fire-ball', 'Boule de feu', 1, Element::Fire);
        $spell->setNameTranslations(['en' => 'Fireball']);

        $blueprint = $this->derivation->derive($spell);

        $this->assertSame('m1-fire-ball', $blueprint->slug);
        $this->assertSame('Matéria : Boule de feu', $blueprint->name);
        $this->assertSame(['en' => 'Materia: Fireball'], $blueprint->nameTranslations);
        $this->assertSame('Matéria contenant le sort « Boule de feu ».', $blueprint->description);
        $this->assertSame(['en' => 'Materia containing the "Fireball" spell.'], $blueprint->descriptionTranslations);
        $this->assertSame(Element::Fire, $blueprint->element, 'L\'element n\'est jamais redeclare : il vient du sort.');
        $this->assertSame(1, $blueprint->tier);
        $this->assertSame(130, $blueprint->price);
        $this->assertSame(10, $blueprint->energyCost);
        $this->assertSame('fire-ball', $blueprint->spellSlug);
    }

    /**
     * La grille par palier est figee (GAME_MATERIA §2.3) : prix
     * 130/180/280/320/380, energie 10/15/20/25/30.
     */
    public function testTierGridsAreTheActedOnes(): void
    {
        $expected = [
            1 => [130, 10],
            2 => [180, 15],
            3 => [280, 20],
            4 => [320, 25],
            5 => [380, 30],
        ];

        foreach ($expected as $tier => [$price, $energyCost]) {
            $blueprint = $this->derivation->derive($this->spell('test-spell', 'Test', $tier, Element::Water));

            $this->assertSame($price, $blueprint->price, sprintf('Prix du palier %d', $tier));
            $this->assertSame($energyCost, $blueprint->energyCost, sprintf('Energie du palier %d', $tier));
        }
    }

    /**
     * Deux sorts distincts donnent deux slugs distincts : la convention
     * `m<niveau>-<slug du sort>` herite de l'unicite des slugs de sort, et un
     * meme sort a deux niveaux differents resterait discernable.
     */
    public function testSlugsAreUniqueByConstruction(): void
    {
        $slugs = [
            $this->derivation->slugFor($this->spell('fire-ball', 'Boule de feu', 1, Element::Fire)),
            $this->derivation->slugFor($this->spell('flame', 'Flammèche', 1, Element::Fire)),
            $this->derivation->slugFor($this->spell('fire-wall', 'Mur de feu', 2, Element::Fire)),
        ];

        $this->assertSame($slugs, array_values(array_unique($slugs)));
        $this->assertSame(['m1-fire-ball', 'm1-flame', 'm2-fire-wall'], $slugs);
    }

    /**
     * La rarete ne se declare jamais : le gabarit n'a aucun champ pour elle.
     * Elle est une fonction du slug (`ItemFixtures::inferRarity()`), et le
     * slug une fonction du sort.
     */
    public function testBlueprintCarriesNoRarity(): void
    {
        $this->assertFalse(
            property_exists(MateriaBlueprint::class, 'rarity'),
            'Une materia dont la rarete est ecrite en dur est un bug (GAME_MATERIA §2.1).',
        );
        $this->assertFalse(property_exists(MateriaBlueprint::class, 'nbUsages'), 'Une materia n\'est jamais consommable.');
    }

    /**
     * Un sort hors des paliers 1 a 5 est une erreur de contenu : la
     * derivation refuse plutot que d'inventer un prix.
     */
    public function testUnknownTierIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->derivation->derive($this->spell('overworld-spell', 'Hors grille', 6, Element::Dark));
    }

    /**
     * Les traductions suivent celles du sort : une locale non traduite chez
     * le sort n'est pas inventee chez la materia.
     */
    public function testTranslationsFollowTheSpell(): void
    {
        $blueprint = $this->derivation->derive($this->spell('stone-throw', 'Jet de cailloux', 1, Element::Earth));

        $this->assertSame([], $blueprint->nameTranslations);
        $this->assertSame([], $blueprint->descriptionTranslations);
    }
}
