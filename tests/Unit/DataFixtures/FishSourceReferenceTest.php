<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * Aucun poisson n'est orphelin (ECO-29).
 *
 * Le trou le plus large de l'audit d'economie : **les sept poissons du monde
 * n'etaient consommes par rien**. Six filons de peche, un arbre entier de
 * competences, et pas une recette au bout — un joueur pouvait monter pecheur
 * jusqu'au kraken et n'avoir strictement rien a faire de sa peche.
 *
 * Ce fichier est la version « peche » de `OreSourceReferenceTest` et de
 * `LeatherSourceReferenceTest`, mais dans l'autre sens : ceux-la verifient
 * qu'une matiere consommee a une **source**, celui-ci qu'une matiere sourcee a
 * un **debouche**. Les deux defauts sont muets, et symetriques.
 */
class FishSourceReferenceTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * Tous les poissons livres, lus sur leur fixture.
     *
     * @return list<string>
     */
    private function fishSlugs(): array
    {
        preg_match_all(
            "/slug: '(fish-[a-z0-9-]+)'/",
            (string) file_get_contents($this->root() . '/fixtures/game/item/fish.yaml'),
            $matches,
        );

        sort($matches[1]);

        return $matches[1];
    }

    private function recipes(): string
    {
        return (string) file_get_contents($this->root() . '/src/DataFixtures/RecipeFixtures.php');
    }

    /**
     * Chaque poisson est consomme par au moins une recette.
     */
    public function testEveryFishHasAConsumer(): void
    {
        $recipes = $this->recipes();

        $orphans = [];
        foreach ($this->fishSlugs() as $slug) {
            if (!str_contains($recipes, sprintf("['slug' => '%s'", $slug))) {
                $orphans[] = $slug;
            }
        }

        self::assertSame(
            [],
            $orphans,
            'Ces poissons se pechent et ne servent a rien. Un filon dont la matiere n\'a pas de debouche est une '
            . 'promesse vide : le joueur peche, et decouvre qu\'il n\'y a rien a en faire.',
        );
    }

    /**
     * Les sept poissons sont bien les sept attendus.
     *
     * Le compte est epingle pour qu'un poisson ajoute sans debouche fasse
     * rougir la CI le jour ou il est ecrit, pas six semaines plus tard.
     */
    public function testTheWorldStillHasExactlySevenFish(): void
    {
        self::assertSame([
            'fish-baby-kraken',
            'fish-carp',
            'fish-electric-eel',
            'fish-moonfish',
            'fish-perch',
            'fish-salmon',
            'fish-trout',
        ], $this->fishSlugs());
    }

    /**
     * Le gibier et le ble des Vallons ont eux aussi trouve leur metier.
     *
     * ZON-30 les avait poses **sans consommateur**, en disant que le cuisinier
     * viendrait. Il est venu : ce test est la ou la promesse se verifie.
     */
    public function testTheValesHarvestsFinallyHaveATrade(): void
    {
        $recipes = $this->recipes();

        foreach (['meat-game', 'plant-wheat'] as $slug) {
            self::assertStringContainsString(
                sprintf("['slug' => '%s'", $slug),
                $recipes,
                sprintf('"%s" attend toujours son metier.', $slug),
            );
        }
    }

    /**
     * Les plats du cuisinier sont atteignables par un arbre de talent.
     *
     * Une recette qu'aucun skill ne debloque est du contenu mort (ECO-18) — et
     * sept recettes mortes n'auraient rien repare du tout.
     */
    public function testEveryCookRecipeIsUnlockedBySomeSkill(): void
    {
        preg_match_all("/'slug' => '(recipe-[a-z-]+)',\s*\n\s*'craft' => 'cuisinier'/", $this->recipes(), $matches);

        self::assertNotEmpty($matches[1], 'Aucune recette de cuisine trouvee : le test ne verifie rien.');

        $skills = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');

        foreach ($matches[1] as $slug) {
            self::assertStringContainsString(sprintf("'%s'", $slug), $skills, sprintf('"%s" n\'est ouverte par aucun noeud.', $slug));
        }
    }

    /**
     * Le metier existe, et l'etabli le montre.
     *
     * Un domaine sans onglet a l'etabli serait un arbre qu'on monte pour rien :
     * les recettes seraient apprises et jamais realisables.
     */
    public function testTheCookIsADomainAndAWorkbenchTab(): void
    {
        self::assertStringContainsString(
            "'cook' => ['title' => 'Cuisinier'",
            (string) file_get_contents($this->root() . '/src/DataFixtures/DomainFixtures.php'),
        );

        self::assertStringContainsString(
            "'cuisinier'",
            (string) file_get_contents($this->root() . '/src/Controller/Game/CraftingController.php'),
        );
    }
}
