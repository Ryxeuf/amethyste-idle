<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * La chaine de production a une verticale (ECO-25).
 *
 * L'audit ECO-24 avait trouve une chaine **batie horizontalement, plate
 * verticalement** : les biens finis consommaient bien des intermediaires, mais
 * les intermediaires ne se consommaient pas entre eux. Consequence mesurable :
 * `recipe_orichalcum_ingot` (niveau 8) ne devait **rien** a ce qu'un debutant
 * extrait. Le jour ou les veterans sont tous a l'orichalque, le cuivre ne vaut
 * plus rien et la Foret des murmures n'interesse plus personne — c'est le
 * « creux du milieu » (GAME_WORLD § 5.5).
 *
 * Deux proprietes se tiennent ici, et elles tirent en sens contraire :
 *
 * 1. **Chaque palier de raffinage consomme le palier d'en dessous.** C'est ce
 *    qui rend la demande en matiere de debut proportionnelle a l'activite de
 *    fin de jeu.
 * 2. **Le chemin du debutant reste intact.** Le bronze se forge toujours
 *    directement depuis le cuivre et l'etain ; le chainage ne commence qu'au
 *    deuxieme palier. Sans cette borne, la mecanique qui sauve le milieu
 *    fermerait l'entree.
 *
 * Le **coefficient est 1** a chaque etage, et ce n'est pas un reglage : l'effet
 * est multiplicatif sur la profondeur. A 1, un lingot d'orichalque entraine
 * 2 cuivre + 2 etain ; a 2, il en entraine 32 (BALANCE § 21.4).
 */
class ProductionChainTest extends TestCase
{
    /**
     * L'echelle du metal, du bas vers le haut.
     *
     * Chaque entree declare le raffine du palier inferieur qu'elle doit
     * consommer. Le bronze n'y figure pas : c'est le palier d'entree.
     *
     * @var array<string, string>
     */
    private const METAL_LADDER = [
        'recipe_iron_ingot' => 'crafted-bronze-ingot',
        'recipe_cobalt_ingot' => 'crafted-iron-ingot',
        'recipe_mithril_ingot' => 'crafted-cobalt-ingot',
        'recipe_adamantite_ingot' => 'crafted-mithril-ingot',
        'recipe_orichalcum_ingot' => 'crafted-adamantite-ingot',
    ];

    /**
     * Les six orphelines de niveau >= 3 relevees par l'audit (BALANCE § 21.2).
     *
     * Elles sont nommees ici pour que leur guerison soit **verifiee**, et non
     * simplement affirmee dans un document.
     *
     * @var list<string>
     */
    private const AUDITED_ORPHANS = [
        'recipe_cobalt_ingot',
        'recipe_steel_chainmail',
        'recipe_adamantite_ingot',
        'recipe_orichalcum_ingot',
        'recipe_poison_vial',
        'recipe_masterwork_drakehide_cloak',
    ];

    /**
     * Les trois gemmes brutes du monde. Aucune n'avait de consommateur.
     *
     * @var list<string>
     */
    private const RAW_GEMS = ['ore-ruby', 'ore-emerald', 'ore-diamond'];

    /**
     * Objets qui n'avaient ni producteur ni consommateur (BALANCE § 21.7).
     *
     * @var list<string>
     */
    private const REVIVED_ITEMS = ['crafted-iron-ingot', 'crafted-gold-ingot'];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function source(): string
    {
        return (string) file_get_contents($this->root() . '/src/DataFixtures/RecipeFixtures.php');
    }

    /**
     * Reference de recette => { level, ingredients, result_ref }.
     *
     * @return array<string, array{level: int, ingredients: list<string>, result: string}>
     */
    private function recipes(): array
    {
        preg_match_all(
            "/'(recipe_[a-z0-9_]+)' => \[(.*?)\n            \],/s",
            $this->source(),
            $matches,
            \PREG_SET_ORDER,
        );

        $recipes = [];
        foreach ($matches as [, $reference, $body]) {
            preg_match("/'required_level' => (\d+)/", $body, $level);
            preg_match("/'result_ref' => '([a-z0-9_]+)'/", $body, $result);
            preg_match_all("/'slug' => '([a-z0-9-]+)', 'quantity' => \d+/", $body, $ingredients);

            $recipes[$reference] = [
                'level' => (int) ($level[1] ?? 0),
                'ingredients' => $ingredients[1],
                'result' => $result[1] ?? '',
            ];
        }

        return $recipes;
    }

    public function testTheExtractionSeesEnoughRecipesToJudge(): void
    {
        $this->assertGreaterThan(80, \count($this->recipes()), 'L\'extraction des recettes a echoue : rien n\'est verifie.');
    }

    // =====================================================================
    // 1. L'echelle du metal
    // =====================================================================

    public function testEveryRefiningTierConsumesTheOneBelow(): void
    {
        $recipes = $this->recipes();
        $broken = [];

        foreach (self::METAL_LADDER as $reference => $expected) {
            $this->assertArrayHasKey($reference, $recipes, sprintf('La recette "%s" a disparu.', $reference));

            if (!\in_array($expected, $recipes[$reference]['ingredients'], true)) {
                $broken[] = sprintf('%s n\'exige pas %s', $reference, $expected);
            }
        }

        $this->assertSame(
            [],
            $broken,
            'Un palier de raffinage qui ne consomme pas celui d\'en dessous rend la matiere de debut inutile des '
            . 'que les veterans montent : c\'est exactement le creux du milieu.',
        );
    }

    /**
     * Le chainage se paie **une** unite par etage, jamais deux.
     *
     * L'effet est multiplicatif sur la profondeur : a 2, un lingot d'orichalque
     * entrainerait 32 cuivre au lieu de 2, et la matiere de debut deviendrait le
     * goulot du jeu — l'inverse exact de ce que la mecanique cherche.
     */
    public function testTheChainCoefficientIsOne(): void
    {
        $source = $this->source();
        $offenders = [];

        foreach (self::METAL_LADDER as $reference => $expected) {
            preg_match("/'{$reference}' => \[(.*?)\n            \],/s", $source, $block);
            preg_match("/'slug' => '{$expected}', 'quantity' => (\d+)/", $block[1] ?? '', $quantity);

            if (($quantity[1] ?? '0') !== '1') {
                $offenders[] = sprintf('%s exige %s x%s', $reference, $expected, $quantity[1] ?? '?');
            }
        }

        $this->assertSame([], $offenders, 'Le coefficient de chainage doit rester a 1 (BALANCE § 21.4).');
    }

    /**
     * Le palier d'entree ne se chaine pas : le bronze part du brut.
     */
    public function testTheBeginnerPathIsUntouched(): void
    {
        $bronze = $this->recipes()['recipe_bronze_ingot'] ?? null;

        $this->assertNotNull($bronze);
        $this->assertSame(['ore-copper', 'ore-tin'], $bronze['ingredients']);
        $this->assertSame(1, $bronze['level']);
    }

    /**
     * Aucune recette ne consomme un **raffine** d'un palier superieur au sien.
     *
     * Une chaine qui remonte est un blocage circulaire : la recette de niveau 3
     * exigerait un objet que seul le niveau 4 produit, et personne ne pourrait
     * jamais commencer.
     *
     * Le controle ne porte que sur les objets `crafted-*`. Une matiere premiere
     * peut parfaitement etre **a la fois** recoltee et produite — c'est le cas
     * d'`ore-mithril`, qui a un filon a la Crete *et* une transmutation
     * alchimique de haut niveau (BALANCE § 19). Le filon suffit a la rendre
     * accessible ; la transmutation n'est qu'une seconde voie, et la compter
     * comme un prerequis ferait crier au blocage la ou il n'y en a pas.
     */
    public function testNoRecipeConsumesARefinedItemFromAHigherTier(): void
    {
        $recipes = $this->recipes();

        $producedAt = [];
        foreach ($recipes as $data) {
            $slug = str_replace('_', '-', $data['result']);
            if (str_starts_with($slug, 'crafted-')) {
                $producedAt[$slug] = $data['level'];
            }
        }

        $offenders = [];
        foreach ($recipes as $reference => $data) {
            foreach ($data['ingredients'] as $ingredient) {
                $producerLevel = $producedAt[$ingredient] ?? null;
                if ($producerLevel !== null && $producerLevel > $data['level']) {
                    $offenders[] = sprintf('%s (niv %d) exige %s (produit au niv %d)', $reference, $data['level'], $ingredient, $producerLevel);
                }
            }
        }

        $this->assertSame([], $offenders, 'Une chaine qui remonte ne peut jamais demarrer.');
    }

    // =====================================================================
    // 2. Les orphelines de l'audit
    // =====================================================================

    /**
     * Les six orphelines de niveau >= 3 consomment desormais un produit
     * d'artisanat.
     */
    public function testTheAuditedOrphansAreChainedNow(): void
    {
        $recipes = $this->recipes();
        $stillOrphan = [];

        foreach (self::AUDITED_ORPHANS as $reference) {
            $ingredients = $recipes[$reference]['ingredients'] ?? [];
            $chained = array_filter($ingredients, static fn (string $slug): bool => str_starts_with($slug, 'crafted-'));

            if ($chained === []) {
                $stillOrphan[] = $reference;
            }
        }

        $this->assertSame([], $stillOrphan, 'Ces recettes de niveau >= 3 ne doivent rien a l\'artisanat d\'en dessous.');
    }

    // =====================================================================
    // 3. Ce qui ne servait a rien sert enfin
    // =====================================================================

    /**
     * Les trois gemmes brutes ont un consommateur.
     *
     * Trois filons declares produisaient un objet que **rien** ne consommait —
     * une exclusivite sans debouche est un mensonge de level design.
     */
    public function testEveryRawGemIsConsumedBySomeRecipe(): void
    {
        $consumed = [];
        foreach ($this->recipes() as $data) {
            foreach ($data['ingredients'] as $ingredient) {
                $consumed[$ingredient] = true;
            }
        }

        $unused = array_values(array_filter(self::RAW_GEMS, static fn (string $gem): bool => !isset($consumed[$gem])));

        $this->assertSame([], $unused, 'Ces gemmes brutes se recoltent sans que rien ne les consomme.');
    }

    /**
     * Les deux lingots morts sont produits **et** consommes.
     *
     * Un objet produit par rien est du contenu mort ; un objet consomme par rien
     * l'est tout autant. Les deux moities comptent.
     */
    public function testTheRevivedIngotsAreBothProducedAndConsumed(): void
    {
        $recipes = $this->recipes();

        $produced = [];
        $consumed = [];
        foreach ($recipes as $data) {
            if ($data['result'] !== '') {
                $produced[str_replace('_', '-', $data['result'])] = true;
            }
            foreach ($data['ingredients'] as $ingredient) {
                $consumed[$ingredient] = true;
            }
        }

        foreach (self::REVIVED_ITEMS as $slug) {
            $this->assertArrayHasKey($slug, $produced, sprintf('"%s" n\'est produit par aucune recette.', $slug));
            $this->assertArrayHasKey($slug, $consumed, sprintf('"%s" n\'est consomme par aucune recette.', $slug));
        }
    }
}
