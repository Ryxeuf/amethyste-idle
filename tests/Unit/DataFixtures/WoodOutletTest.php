<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * Le bois a un debouche (ECO-30).
 *
 * ZON-34 a livre quatre essences, cinq filons et un arbre entier de
 * competences — **et pas une recette au bout**. Un joueur pouvait monter
 * bucheron jusqu'au bois petrifie et n'avoir strictement rien a faire de sa
 * coupe. C'est la version « bois » du defaut qu'`FishSourceReferenceTest`
 * verrouille pour la peche, et il se lit dans le meme sens : une matiere
 * sourcee doit avoir un **debouche**.
 *
 * Trois proprietes se tiennent ici :
 *
 * 1. **Chaque essence est consommee**, y compris le petrifie — un palier de
 *    fin de ligne sans recette serait une exclusivite decorative.
 * 2. **Aucune arme de bois n'est sans recette.** Les arcs et les batons
 *    existaient depuis toujours et ne se fabriquaient nulle part.
 * 3. **La fleche existe et se depense.** Un metier qui ne produit que du
 *    durable voit sa demande s'eteindre le jour ou chacun a son arc.
 */
class WoodOutletTest extends TestCase
{
    /**
     * Les quatre essences de ZON-34.
     *
     * @var list<string>
     */
    private const ESSENCES = ['wood-beech', 'wood-whisperoak', 'wood-peat', 'wood-petrified'];

    /**
     * Les armes de bois livrees avant ce jalon, toutes sans recette.
     *
     * @var list<string>
     */
    private const WOODEN_WEAPONS = ['t1_bow', 't1_staff', 't2_bow', 't2_staff', 't3_bow', 't3_staff'];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function recipes(): string
    {
        return (string) file_get_contents($this->root() . '/src/DataFixtures/RecipeFixtures.php');
    }

    /**
     * Chaque essence est consommee par au moins une recette.
     */
    public function testEveryEssenceHasAConsumer(): void
    {
        $recipes = $this->recipes();

        $orphans = [];
        foreach (self::ESSENCES as $slug) {
            if (!str_contains($recipes, sprintf("['slug' => '%s'", $slug))) {
                $orphans[] = $slug;
            }
        }

        self::assertSame(
            [],
            $orphans,
            'Ces essences se coupent et ne servent a rien. Un filon dont la matiere n\'a pas de debouche est une '
            . 'promesse vide : le joueur abat, et decouvre qu\'il n\'y a rien a en faire.',
        );
    }

    /**
     * Chaque arme de bois se fabrique.
     *
     * Six armes, six paliers d'archer et de mage, et aucune n'avait de recette :
     * elles ne tombaient qu'au butin. Le charpentier existe precisement pour que
     * l'equipement des classes a distance vienne d'un joueur.
     */
    public function testNoWoodenWeaponIsWithoutARecipe(): void
    {
        $recipes = $this->recipes();

        $missing = [];
        foreach (self::WOODEN_WEAPONS as $reference) {
            if (!str_contains($recipes, sprintf("'result_ref' => '%s'", $reference))) {
                $missing[] = $reference;
            }
        }

        self::assertSame([], $missing, 'Ces armes de bois ne se fabriquent nulle part.');
    }

    /**
     * Le consommable perpetuel existe, et il consomme les plumes des Vallons.
     *
     * ZON-30 avait pose `feather-raw` **sans consommateur**, en disant que le
     * charpentier viendrait. Il est venu : ce test est la ou la promesse se
     * verifie.
     */
    public function testTheArrowsExistAndSpendTheCrowFeathers(): void
    {
        $recipes = $this->recipes();

        self::assertStringContainsString("'result_ref' => 'crafted_arrows'", $recipes);
        self::assertStringContainsString("['slug' => 'feather-raw'", $recipes, 'Les plumes attendent toujours leur metier.');
    }

    /**
     * La planche traverse le metier.
     *
     * C'est l'equivalent bois de la laniere de cuir : un intermediaire bon
     * marche par lequel passe tout le reste. Sans lui, le hetre serait une
     * matiere de premier jour dont la demande s'eteindrait des le second
     * palier — le creux du milieu, en petit.
     */
    public function testThePlankIsTheSpineOfTheTrade(): void
    {
        preg_match_all(
            "/'craft' => 'charpentier',(.*?)\n            \],/s",
            $this->recipes(),
            $matches,
        );

        self::assertGreaterThanOrEqual(8, \count($matches[1]), 'L\'extraction des recettes du charpentier a echoue.');

        $consumingThePlank = array_filter(
            $matches[1],
            static fn (string $body): bool => str_contains($body, "['slug' => 'crafted-plank'"),
        );

        self::assertGreaterThanOrEqual(
            \count($matches[1]) - 2,
            \count($consumingThePlank),
            'La planche doit traverser le metier : seules la recette qui la produit et celle du manche s\'en passent.',
        );
    }

    /**
     * Le charpentier vend au forgeron.
     *
     * Une hache est un fer **sur un bois**, et jusqu'a ce jalon le fer se
     * passait du bois. C'est la seule sortie du metier consommee par un autre —
     * sans elle, le charpentier serait le premier metier dont personne n'achete
     * la production (ECO-14).
     */
    public function testTheSmithBuysTheCarpentersHaft(): void
    {
        preg_match("/'recipe_steel_axe' => \[(.*?)\n            \],/s", $this->recipes(), $block);

        self::assertNotEmpty($block, 'La recette de la hache d\'acier a disparu.');
        self::assertStringContainsString("['slug' => 'crafted-wood-haft'", $block[1]);
    }

    /**
     * Le metier existe, et l'etabli le montre.
     *
     * Un domaine sans onglet a l'etabli serait un arbre qu'on monte pour rien :
     * les recettes seraient apprises et jamais realisables.
     */
    public function testTheCarpenterIsADomainAndAWorkbenchTab(): void
    {
        self::assertStringContainsString(
            "'carpenter' => ['title' => 'Charpentier'",
            (string) file_get_contents($this->root() . '/src/DataFixtures/DomainFixtures.php'),
        );

        self::assertStringContainsString(
            "'charpentier'",
            (string) file_get_contents($this->root() . '/src/Controller/Game/CraftingController.php'),
        );

        $skills = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');
        self::assertStringContainsString('getCarpenterSkills()', $skills);
        self::assertStringContainsString("\$d = 'carpenter';", $skills);
    }

    /**
     * Toute recette du charpentier est ouverte par un nœud d'arbre.
     *
     * Une recette qu'aucun skill ne debloque est du contenu mort (ECO-18) — et
     * dix recettes mortes n'auraient rien repare du tout.
     */
    public function testEveryCarpenterRecipeIsUnlockedBySomeSkill(): void
    {
        preg_match_all("/'slug' => '(recipe-[a-z0-9-]+)',\s*\n\s*'craft' => 'charpentier'/", $this->recipes(), $matches);

        self::assertNotEmpty($matches[1], 'Aucune recette de charpente trouvee : le test ne verifie rien.');

        $skills = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');

        foreach ($matches[1] as $slug) {
            self::assertStringContainsString(
                sprintf("'%s'", $slug),
                $skills,
                sprintf('"%s" n\'est ouverte par aucun noeud.', $slug),
            );
        }
    }

    /**
     * L'ameublement d'une demeure a une voie joueur.
     *
     * Le style se payait **uniquement** en Gils : un cosmetique que rien de
     * joueur ne produisait. Le necessaire est le seul endroit ou la ligne du
     * bois touche le housing.
     */
    public function testTheFurnishingKitIsTheCarpentersWayIntoHousing(): void
    {
        self::assertStringContainsString(
            "'result_ref' => 'crafted_furnishing_kit'",
            $this->recipes(),
        );

        self::assertStringContainsString(
            "FURNISHING_KIT_SLUG = 'crafted-furnishing-kit'",
            (string) file_get_contents($this->root() . '/src/GameEngine/Housing/HousingManager.php'),
        );
    }
}
