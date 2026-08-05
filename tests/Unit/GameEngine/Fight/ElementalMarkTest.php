<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\Element;
use App\GameEngine\Fight\ElementalMark;
use PHPUnit\Framework\TestCase;

/**
 * Les huit marques elementaires, et la loi qui les tient (ARC-13a).
 *
 * GAME_ARCHETYPES § 1.1. Trois pieces deja ecrites du systeme en dependent — le
 * capstone d'assaut, le levier `grip` et la palette de controle : sans marques,
 * ce sont trois mecaniques qui pointent vers un vide.
 */
class ElementalMarkTest extends TestCase
{
    /**
     * Les marques declarees dans la fixture, par slug.
     *
     * @return array<string, array{element: string, duration: int, type: string}>
     */
    private function declaredMarks(): array
    {
        $source = (string) file_get_contents(
            \dirname(__DIR__, 4) . '/src/DataFixtures/Game/StatusEffectFixtures.php',
        );

        preg_match_all("/'status_\w+' => \[(.*?)\n            \],/s", $source, $blocks, \PREG_SET_ORDER);

        $found = [];
        foreach ($blocks as [$body]) {
            if (preg_match("/'slug' => '([a-z0-9-]+)'/", $body, $slug) !== 1) {
                continue;
            }
            if (!ElementalMark::isMark($slug[1])) {
                continue;
            }

            preg_match("/'element' => Element::(\w+)/", $body, $element);
            preg_match("/'duration' => (\d+)/", $body, $duration);
            preg_match("/'type' => StatusEffect::(\w+)/", $body, $type);

            $found[$slug[1]] = [
                'element' => $element[1] ?? 'None',
                'duration' => (int) ($duration[1] ?? 0),
                'type' => $type[1] ?? '?',
            ];
        }

        return $found;
    }

    /**
     * Une marque, et une seule, par element.
     *
     * Huit elements, huit marques, aucune en double — c'est ce qui permet a un
     * passif d'arbre de dire « ma marque » sans ambiguite.
     */
    public function testExactlyOneMarkPerElement(): void
    {
        $elements = ElementalMark::markedElements();

        self::assertCount(8, $elements);
        self::assertCount(8, ElementalMark::SLUGS);
        self::assertCount(8, array_unique(ElementalMark::SLUGS));

        foreach ($elements as $element) {
            $slug = ElementalMark::forElement($element);
            self::assertNotNull($slug);
            self::assertSame($element, ElementalMark::elementOf($slug));
        }
    }

    /**
     * Aucun element reel n'est sans marque, et `None` n'en a pas.
     *
     * `None` n'est pas un element mais son absence : une action sans element ne
     * qualifie aucun passif d'arbre (§ 9 quater — le defaut qui avait eteint
     * l'archer). Lui donner une marque creerait une neuvieme case qui ne
     * correspond a aucun domaine.
     */
    public function testEveryRealElementIsMarkedAndNoneIsNot(): void
    {
        self::assertNull(ElementalMark::forElement(Element::None));

        foreach (Element::cases() as $element) {
            if (Element::None === $element) {
                continue;
            }

            self::assertNotNull(
                ElementalMark::forElement($element),
                sprintf('L\'element %s n\'a pas de marque.', $element->value),
            );
        }
    }

    /**
     * Les huit marques existent en donnee, sur leur element.
     *
     * Le catalogue et la fixture ne peuvent pas diverger : une marque declaree
     * ici et absente de la base serait un passif qui ne s'allume jamais.
     */
    public function testTheEightMarksExistInTheFixtureOnTheirElement(): void
    {
        $declared = $this->declaredMarks();

        self::assertCount(8, $declared, 'Les huit marques doivent exister en donnee.');

        foreach (ElementalMark::SLUGS as $elementValue => $slug) {
            self::assertArrayHasKey($slug, $declared);
            self::assertSame(
                ucfirst($elementValue),
                $declared[$slug]['element'],
                sprintf('La marque "%s" n\'est pas declaree sur son element.', $slug),
            );
        }
    }

    /**
     * Aucune marque ne dure un seul tour.
     *
     * **C'est la correction du § 9 quinquies, et elle est arithmetique.** En
     * duel, echanger un de ses tours contre un tour adverse laisse les degats
     * subis **rigoureusement identiques** — 101 dans les quatre cas mesures :
     * le combat s'allonge exactement de ce qu'on a vole. Une entrave d'un tour
     * est donc un nœud mort, quel que soit le chiffre qu'on y mette.
     */
    public function testNoMarkLastsASingleTurn(): void
    {
        foreach ($this->declaredMarks() as $slug => $mark) {
            self::assertGreaterThanOrEqual(
                ElementalMark::MIN_DURATION,
                $mark['duration'],
                sprintf('La marque "%s" dure %d tour(s) : une entrave d\'un tour est nulle en duel.', $slug, $mark['duration']),
            );
        }
    }

    /**
     * La loi de duree laisse passer ce qu'un geste de degat porte.
     *
     * Un tour qui a servi deux fois n'a pas ete echange : la marque posee par
     * un geste de degat echappe au minimum, parce que le defaut qu'il corrige
     * n'existe pas.
     */
    public function testAMarkCarriedByDamageEscapesTheMinimum(): void
    {
        self::assertTrue(ElementalMark::durationIsLegal(1, true));
        self::assertFalse(ElementalMark::durationIsLegal(1, false));
        self::assertTrue(ElementalMark::durationIsLegal(ElementalMark::MIN_DURATION, false));
    }

    /**
     * Chaque marque porte le nom que le canon lui donne.
     */
    public function testEveryMarkIsNamed(): void
    {
        foreach (ElementalMark::SLUGS as $slug) {
            self::assertArrayHasKey($slug, ElementalMark::LABELS);
            self::assertNotSame('', ElementalMark::LABELS[$slug]);
        }

        self::assertCount(8, ElementalMark::LABELS);
    }

    /**
     * Un statut ordinaire n'est pas une marque.
     *
     * Le poison, la regeneration et le bouclier restent des effets ordinaires :
     * ce qui fait une marque n'est pas sa mecanique mais le fait qu'elle soit
     * **celle de son element**.
     */
    public function testAnOrdinaryStatusIsNotAMark(): void
    {
        foreach (['poison', 'regeneration', 'shield', 'berserk', 'freeze', 'silence'] as $ordinary) {
            self::assertFalse(ElementalMark::isMark($ordinary), $ordinary);
            self::assertNull(ElementalMark::elementOf($ordinary), $ordinary);
        }

        // La Brulure, elle, est un DOT **et** la marque du feu.
        self::assertTrue(ElementalMark::isMark('burn'));
        self::assertSame(Element::Fire, ElementalMark::elementOf('burn'));
    }
}
