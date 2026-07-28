<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * Loi transverse, versant tanneur : un cuir exige par une recette doit etre
 * lache par au moins un monstre (ECO-24b-b).
 *
 * `OreSourceReferenceTest` tient la meme loi pour la ligne du metal, ou la
 * source est un **filon de zone**. Ici la source est une **table de butin** :
 * le tanneur ne mine pas, il depece. D'ou un test separe plutot qu'un
 * elargissement du premier — deux systemes, deux facons de repondre a la
 * question « d'ou vient cette matiere ? ».
 *
 * Le defaut corrige (BALANCE §21.7) : 12 recettes consommaient `leather-bone`,
 * `leather-fang`, `leather-dragon-scale` et `leather-werewolf-fur` sans
 * qu'**aucun** monstre ne les lache. Les series « durcie » et « dragon » et la
 * cape de maitre etaient irrealisables, sans le moindre message pour le dire.
 * Meme famille qu'ECO-02, ou les betes lachaient `leather_skin_1/2`, doublons
 * sans consommateur, pendant que les recettes attendaient `leather_raw`.
 */
class LeatherSourceReferenceTest extends TestCase
{
    /**
     * Cuirs de base : communs, largement repartis (6 et 8 Gils au catalogue).
     *
     * @var list<string>
     */
    private const COMMON_LEATHERS = ['leather-bone', 'leather-fang'];

    /**
     * Cuirs de haut palier : rares, concentres sur les especes qui les portent
     * en fiction (150 et 200 Gils).
     *
     * @var list<string>
     */
    private const RARE_LEATHERS = ['leather-werewolf-fur', 'leather-dragon-scale'];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * Cuirs cites comme ingredient d'au moins une recette.
     *
     * @return list<string>
     */
    private function leathersConsumedByRecipes(): array
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/RecipeFixtures.php');
        preg_match_all("/\['slug' => '(leather-[a-z0-9-]+)', 'quantity'/", $source, $matches);

        $slugs = array_values(array_unique($matches[1]));
        sort($slugs);

        return $slugs;
    }

    /**
     * Slug de cuir => especes qui le lachent.
     *
     * Les tables de butin designent l'objet par **reference** de fixture
     * (`leather_bone`), les recettes par **slug** (`leather-bone`) : la
     * correspondance se lit dans les fixtures d'items plutot que de se deviner.
     *
     * @return array<string, list<string>>
     */
    private function dropsByLeather(): array
    {
        $refToSlug = $this->itemRefToSlug();
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/MonsterItemFixtures.php');
        preg_match_all("/\['monster' => '([a-z0-9_]+)', 'item' => '([a-z0-9_]+)'/", $source, $matches, \PREG_SET_ORDER);

        $drops = [];
        foreach ($matches as $match) {
            $slug = $refToSlug[$match[2]] ?? null;
            if (\is_string($slug) && str_starts_with($slug, 'leather-')) {
                $drops[$slug][] = $match[1];
            }
        }

        foreach ($drops as $slug => $monsters) {
            $drops[$slug] = array_values(array_unique($monsters));
        }

        return $drops;
    }

    /**
     * @return array<string, string> reference de fixture => slug d'objet
     */
    private function itemRefToSlug(): array
    {
        $map = [];
        foreach ((array) glob($this->root() . '/fixtures/game/item/*.yaml') as $file) {
            $content = (string) file_get_contents((string) $file);
            preg_match_all("/^\s{2}([a-z0-9_]+) \(extends item\):.*?\n\s+slug: '([a-z0-9-]+)'/ms", $content, $matches, \PREG_SET_ORDER);
            foreach ($matches as $match) {
                $map[$match[1]] = $match[2];
            }
        }

        return $map;
    }

    public function testEveryLeatherConsumedByARecipeIsDroppedBySomeMonster(): void
    {
        $consumed = $this->leathersConsumedByRecipes();
        $this->assertNotEmpty($consumed, 'Le test ne verifie rien si l\'extraction des recettes echoue.');

        $drops = $this->dropsByLeather();

        $unobtainable = array_values(array_filter(
            $consumed,
            static fn (string $slug): bool => ($drops[$slug] ?? []) === [],
        ));

        $this->assertSame(
            [],
            $unobtainable,
            'Ces cuirs sont consommes par une recette et lache par aucun monstre : les recettes concernees '
            . 'sont irrealisables, et rien ne le dit au joueur.',
        );
    }

    /**
     * Raretes inversees : le commun est large, le rare est concentre.
     *
     * La comparaison oppose deux **groupes**, pas quatre matieres en chaine.
     * Ordonner les rares entre elles n'aurait aucun sens : la fourrure de
     * loup-garou et l'ecaille de dragon ne sont pas deux paliers d'une meme
     * ligne, ce sont deux fictions distinctes. Ce que la loi 2 de GAME_ZONES §0
     * exige, c'est que la frontiere entre base et haut palier soit nette.
     */
    public function testCommonLeathersAreMoreWidespreadThanRareOnes(): void
    {
        $drops = $this->dropsByLeather();

        $counts = [];
        foreach ([...self::COMMON_LEATHERS, ...self::RARE_LEATHERS] as $slug) {
            $counts[$slug] = \count($drops[$slug] ?? []);
            $this->assertGreaterThan(0, $counts[$slug], sprintf('Le cuir "%s" n\'a aucune source.', $slug));
        }

        $rarestCommon = min(array_intersect_key($counts, array_flip(self::COMMON_LEATHERS)));
        $widestRare = max(array_intersect_key($counts, array_flip(self::RARE_LEATHERS)));

        $this->assertGreaterThan(
            $widestRare,
            $rarestCommon,
            sprintf(
                'Le cuir de base le moins repandu (%d especes) ne depasse pas le cuir rare le plus repandu '
                . '(%d especes) : la frontiere entre matiere de base et matiere de haut palier s\'est effacee.',
                $rarestCommon,
                $widestRare,
            ),
        );
    }

    /**
     * Une matiere rare ne doit pas dependre d'une espece unique.
     *
     * Un cuir a source unique reproduit exactement le defaut de l'etain
     * (BALANCE §21.5b) : la recette est etranglee par sa moitie la plus rare, et
     * une seule espece mal placee suffit a bloquer une serie entiere.
     */
    public function testNoLeatherDependsOnASingleSpecies(): void
    {
        $drops = $this->dropsByLeather();

        $fragile = [];
        foreach ($this->leathersConsumedByRecipes() as $slug) {
            $count = \count($drops[$slug] ?? []);
            if ($count === 1) {
                $fragile[$slug] = $drops[$slug][0];
            }
        }

        $this->assertSame(
            [],
            $fragile,
            'Ces cuirs ne viennent que d\'une seule espece : la serie de recettes qui les consomme tient a un fil.',
        );
    }
}
