<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * La ligne du tissu, et l'armure des mages (ECO-31).
 *
 * Le trou le plus beant de l'audit d'equipement (BALANCE § 21.7) : sur les 121
 * pieces livrees, **pas une robe**. Pyromancien, hydromancien, necromancien —
 * tous les domaines de sort s'habillaient en cuir et en metal, et aucun metier
 * ne les habillait. Un joueur pouvait jouer un mage de bout en bout sans jamais
 * porter une piece pensee pour lui.
 *
 * Quatre proprietes se tiennent ici :
 *
 * 1. **La categorie tissu existe**, et couvre les memes emplacements que la
 *    serie cuir : ce n'est pas une piece de decor, c'est un pendant.
 * 2. **L'axe est inverse.** Le cuir donne de la protection, le tissu de la
 *    puissance magique, et il la paie en protection. Une robe qui protegerait
 *    autant qu'un plastron ferait du tailleur un forgeron mieux habille.
 * 3. **`crafted-cloth` se reveille.** Livre de longue date, il n'etait produit
 *    ni consomme par rien — l'archetype de l'objet mort.
 * 4. **Le lin a deux debouches.** Une exclusivite de zone dont un seul metier
 *    dependrait s'eteindrait avec lui.
 */
class ClothLineTest extends TestCase
{
    /**
     * Les dix pieces de la ligne, par palier.
     *
     * @var array<string, list<string>> palier => slugs
     */
    private const PIECES = [
        'linen' => ['linen-hood', 'linen-robe', 'linen-gloves'],
        'fine' => ['fine-linen-hood', 'fine-linen-robe', 'fine-linen-gloves'],
        'shadowsilk' => ['shadowsilk-hood', 'shadowsilk-robe'],
        'archivist' => ['archivist-mantle', 'archivist-robe'],
    ];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function recipes(): string
    {
        return (string) file_get_contents($this->root() . '/src/DataFixtures/RecipeFixtures.php');
    }

    /**
     * @return array<string, array<string, mixed>> slug => definition
     */
    private function gear(): array
    {
        /** @var array{'App\Entity\Game\Item': array<string, array<string, mixed>>} $raw */
        $raw = Yaml::parseFile($this->root() . '/fixtures/game/item/gear_item.yaml');

        $bySlug = [];
        foreach ($raw['App\Entity\Game\Item'] as $definition) {
            if (\is_array($definition) && isset($definition['slug'])) {
                $bySlug[(string) $definition['slug']] = $definition;
            }
        }

        self::assertNotEmpty($bySlug, 'L\'extraction des pieces d\'equipement a echoue : rien n\'est verifie.');

        return $bySlug;
    }

    /**
     * Les dix pieces de tissu existent.
     */
    public function testTheClothCategoryExists(): void
    {
        $gear = $this->gear();

        $missing = [];
        foreach (self::PIECES as $pieces) {
            foreach ($pieces as $slug) {
                if (!isset($gear[$slug])) {
                    $missing[] = $slug;
                }
            }
        }

        self::assertSame([], $missing, 'Ces pieces de tissu n\'existent pas : les mages restent en cuir.');
    }

    /**
     * Chaque piece se fabrique.
     *
     * Une piece d'armure sans recette ne tomberait qu'au butin, et le tailleur
     * ne serait qu'un nom dans une liste de domaines.
     */
    public function testEveryClothPieceHasARecipe(): void
    {
        $recipes = $this->recipes();

        $orphans = [];
        foreach (self::PIECES as $pieces) {
            foreach ($pieces as $slug) {
                $reference = str_replace('-', '_', $slug);
                if (!str_contains($recipes, sprintf("'result_ref' => '%s'", $reference))) {
                    $orphans[] = $slug;
                }
            }
        }

        self::assertSame([], $orphans, 'Ces pieces de tissu ne se fabriquent nulle part.');
    }

    /**
     * Toute piece de tissu porte un bonus magique, et **peu** de protection.
     *
     * C'est l'axe du metier. Une robe aussi protectrice qu'un plastron ferait du
     * tailleur un forgeron mieux habille ; une robe sans bonus magique n'aurait
     * aucune raison d'etre portee par la classe a qui elle est destinee.
     */
    public function testClothTradesProtectionForMagic(): void
    {
        $gear = $this->gear();

        foreach (self::PIECES as $pieces) {
            foreach ($pieces as $slug) {
                $piece = $gear[$slug] ?? [];

                self::assertStringContainsString(
                    'magic_boost',
                    (string) ($piece['effect'] ?? ''),
                    sprintf('"%s" ne donne aucune puissance magique.', $slug),
                );
                self::assertLessThanOrEqual(
                    7,
                    (int) ($piece['protection'] ?? 0),
                    sprintf('"%s" protege comme une armure lourde.', $slug),
                );
            }
        }
    }

    /**
     * Le tissu couvre les memes emplacements que la serie cuir.
     *
     * Un pendant qui ne couvrirait que le torse laisserait le mage en gants de
     * cuir et en capuche de metal : le metier existerait sans equiper personne.
     */
    public function testTheClothLineCoversTheSameSlotsAsLeather(): void
    {
        $gear = $this->gear();

        $covered = [];
        foreach (self::PIECES as $pieces) {
            foreach ($pieces as $slug) {
                $covered[(string) ($gear[$slug]['gear_location'] ?? '?')] = true;
            }
        }

        foreach (['head', 'chest', 'hand'] as $slot) {
            self::assertArrayHasKey($slot, $covered, sprintf('Aucune piece de tissu pour l\'emplacement "%s".', $slot));
        }
    }

    /**
     * L'objet mort se reveille : `crafted-cloth` est produit **et** consomme.
     *
     * Il etait livre depuis longtemps sans qu'une seule recette le fasse ni ne
     * l'emploie — l'archetype du contenu mort (BALANCE § 21.7).
     */
    public function testTheDeadClothItemIsBothProducedAndConsumed(): void
    {
        $recipes = $this->recipes();

        self::assertStringContainsString("'result_ref' => 'crafted_cloth'", $recipes, '"crafted-cloth" n\'est produit par rien.');
        self::assertStringContainsString("['slug' => 'crafted-cloth'", $recipes, '"crafted-cloth" n\'est consomme par rien.');
    }

    /**
     * Le lin des Vallons a **deux** debouches, dont un hors du tailleur.
     *
     * Une exclusivite de zone dont un seul metier dependrait s'eteindrait avec
     * lui : le jour ou plus personne ne coud, la liniere du gue ne vaut plus
     * rien. Le cuir s'y coud au fil de lin, et la demande tient a deux pieds.
     */
    public function testFlaxFeedsTwoTrades(): void
    {
        preg_match_all(
            "/'craft' => '([a-z]+)',(.*?)\n            \],/s",
            $this->recipes(),
            $matches,
            \PREG_SET_ORDER,
        );

        $trades = [];
        foreach ($matches as [, $craft, $body]) {
            if (str_contains($body, "['slug' => 'plant-flax'")) {
                $trades[$craft] = true;
            }
        }

        self::assertGreaterThanOrEqual(
            2,
            \count($trades),
            sprintf('Le lin n\'alimente que : %s.', implode(', ', array_keys($trades)) ?: 'personne'),
        );
    }

    /**
     * Le metier existe, et l'etabli le montre.
     */
    public function testTheTailorIsADomainAndAWorkbenchTab(): void
    {
        self::assertStringContainsString(
            "'tailor' => ['title' => 'Tailleur'",
            (string) file_get_contents($this->root() . '/src/DataFixtures/DomainFixtures.php'),
        );

        self::assertStringContainsString(
            "'tailleur'",
            (string) file_get_contents($this->root() . '/src/Controller/Game/CraftingController.php'),
        );

        $skills = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');
        self::assertStringContainsString('getTailorSkills()', $skills);
        self::assertStringContainsString("\$d = 'tailor';", $skills);
    }

    /**
     * Toute recette du tailleur est ouverte par un nœud d'arbre.
     *
     * Une recette qu'aucun skill ne debloque est du contenu mort (ECO-18) — et
     * onze recettes mortes n'auraient habille personne.
     */
    public function testEveryTailorRecipeIsUnlockedBySomeSkill(): void
    {
        preg_match_all("/'slug' => '(recipe-[a-z0-9-]+)',\s*\n\s*'craft' => 'tailleur'/", $this->recipes(), $matches);

        self::assertNotEmpty($matches[1], 'Aucune recette de couture trouvee : le test ne verifie rien.');

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
     * Le premier palier se coud **sans dependre d'un autre metier**.
     *
     * C'est le plancher T1 du lanceur de sorts (ECO-02) : les trois pieces de
     * lin ne consomment que du lin et de la toile. Un mage debutant qui devrait
     * acheter une laniere a un tanneur avant d'avoir sa premiere robe serait
     * exactement le cold-start que le plancher existe pour empecher.
     */
    public function testTheFirstTierDressesAMageWithoutAnotherTrade(): void
    {
        $recipes = $this->recipes();

        foreach (['recipe_linen_hood', 'recipe_linen_gloves', 'recipe_linen_robe'] as $reference) {
            preg_match("/'{$reference}' => \[(.*?)\n            \],/s", $recipes, $block);

            self::assertNotEmpty($block, sprintf('La recette "%s" a disparu.', $reference));

            preg_match_all("/'slug' => '([a-z0-9-]+)', 'quantity'/", $block[1], $ingredients);

            self::assertSame(
                ['crafted-cloth'],
                array_values(array_unique($ingredients[1])),
                sprintf('"%s" exige autre chose que de la toile.', $reference),
            );
        }
    }
}
