<?php

namespace App\Tests\Integration\Economy;

use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\Entity\Game\MonsterItem;
use App\Entity\Game\Quest;
use App\Entity\Game\Recipe;
use App\Entity\Game\Skill;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Plancher T1 anti cold-start (ECO-02).
 *
 * L'economie visee est une economie de **production joueur** : l'essentiel du
 * stuff viendra d'autres joueurs. Le risque structurel d'un tel modele est le
 * cold-start — un marche vide, ou un objet dont personne ne produit
 * l'ingredient, et le nouveau venu est bloque sans recours.
 *
 * La garantie posee ici : **tout ingredient d'une recette de premier palier est
 * accessible en solo**, par au moins une source que le joueur controle seul —
 * filon de zone, boutique PNJ, butin de monstre, ou recompense de quete.
 *
 * L'audit qui a motive le jalon trouvait **7 recettes de niveau 1 sur 13**
 * irrealisables, sur trois ingredients sans la moindre source dans le monde
 * livre : `ore-tin` (lingot de bronze, donc toute l'outillage bronze),
 * `plant-chamomile` (premiere recette d'alchimie) et `leather-raw` (les cinq
 * recettes de tanneur — le metier entier). Le defaut etait invisible : rien
 * n'echouait, la recette s'affichait, elle etait juste impossible.
 */
final class ColdStartFloorTest extends AbstractIntegrationTestCase
{
    /**
     * Slugs d'items qu'un joueur peut obtenir **seul**, avec la provenance.
     *
     * @var array<string, list<string>>|null
     */
    private ?array $soloSources = null;

    public function testEveryFirstTierIngredientHasASoloSource(): void
    {
        $sources = $this->soloSources();
        $unreachable = [];

        foreach ($this->firstTierRecipes() as $recipe) {
            foreach ($this->ingredientSlugs($recipe) as $slug) {
                if (!isset($sources[$slug])) {
                    $unreachable[$slug][] = $recipe->getSlug();
                }
            }
        }

        self::assertSame([], $unreachable, sprintf(
            "Ingredient(s) de premier palier sans source solo : %s.\n"
            . "Une recette dont l'ingredient n'est ni recoltable, ni vendu par un PNJ, ni lache par un "
            . "monstre, ni donne en quete est irrealisable — et le defaut est silencieux (cf. ECO-02).\n"
            . 'Ajoutez une source (filon de zone, stock PNJ, table de butin) ou retirez la recette.',
            $this->describe($unreachable)
        ));
    }

    /**
     * Un ingredient de base lie serait invendable : le marche joueur ne pourrait
     * jamais se former sur le palier d'entree, ce que ECO-01 a rendu exprimable
     * et que ce test verrouille.
     */
    public function testEveryFirstTierIngredientIsExchangeable(): void
    {
        $itemRepository = $this->em->getRepository(Item::class);

        foreach ($this->firstTierRecipes() as $recipe) {
            foreach ($this->ingredientSlugs($recipe) as $slug) {
                $item = $itemRepository->findOneBy(['slug' => $slug]);
                self::assertNotNull($item, sprintf(
                    'Ingredient « %s » de la recette « %s » : aucun item ne porte ce slug.',
                    $slug,
                    $recipe->getSlug()
                ));
                self::assertFalse($item->isBoundOnPickup(), sprintf(
                    'L\'ingredient T1 « %s » est lie a l\'obtention : il ne pourra jamais s\'echanger.',
                    $slug
                ));
            }
        }
    }

    /**
     * Le kit d'onboarding (NAR-04) est accorde une fois. S'il est vendu, casse ou
     * perdu, le joueur doit pouvoir le **racheter a un PNJ** — sinon la garantie
     * d'onboarding ne tient qu'au premier passage.
     */
    public function testOnboardingSurvivalKitIsRepurchasableFromNpc(): void
    {
        $shopStock = $this->shopStockSlugs();

        foreach (['short-sword', 'life-potion'] as $slug) {
            self::assertContains($slug, $shopStock, sprintf(
                'Le plancher de survie « %s » n\'est vendu par aucun PNJ : un joueur qui le perd est bloque.',
                $slug
            ));
        }
    }

    /**
     * Chaque metier d'artisanat doit avoir **au moins une** recette de premier
     * palier realisable en solo, faute de quoi le metier est inaccessible a un
     * nouveau joueur — le cas du tanneur avant ECO-02.
     */
    public function testEveryCraftHasAtLeastOneReachableFirstTierRecipe(): void
    {
        $sources = $this->soloSources();
        $reachableByCraft = [];

        foreach ($this->firstTierRecipes() as $recipe) {
            $craft = $recipe->getCraft();
            $reachableByCraft[$craft] ??= false;

            $reachable = true;
            foreach ($this->ingredientSlugs($recipe) as $slug) {
                if (!isset($sources[$slug])) {
                    $reachable = false;
                    break;
                }
            }

            $reachableByCraft[$craft] = $reachableByCraft[$craft] || $reachable;
        }

        self::assertNotEmpty($reachableByCraft, 'Aucune recette de premier palier n\'a ete trouvee.');

        foreach ($reachableByCraft as $craft => $reachable) {
            self::assertTrue($reachable, sprintf(
                'Le metier « %s » n\'a aucune recette de niveau 1 realisable en solo.',
                $craft
            ));
        }
    }

    /**
     * Chaque metier d'artisanat exige un outil equipe (`CraftingManager::checkCraftTool`).
     * L'outil de palier d'entree doit donc etre a la fois **achetable a un PNJ** et
     * **equipable** — c'est-a-dire accorde par un skill via `equip.tool`.
     *
     * Les deux moities manquaient : aucun skill des quatre arbres d'artisanat
     * n'accordait `equip.tool` (l'emplacement s'ouvrait, rien ne pouvait y entrer),
     * et aucun outil de craft n'etait vendu dans le hub de depart.
     */
    public function testEveryCraftHasAnEquippableAndPurchasableEntryTool(): void
    {
        $shopStock = $this->shopStockSlugs();
        $equippable = $this->equippableToolSlugs();
        $itemRepository = $this->em->getRepository(Item::class);

        foreach (Item::CRAFT_TOOL_TYPES as $craft => $toolType) {
            $candidates = [];
            foreach ($equippable as $slug) {
                $tool = $itemRepository->findOneBy(['slug' => $slug]);
                if (null !== $tool && $tool->getToolType() === $toolType && \in_array($slug, $shopStock, true)) {
                    $candidates[] = $slug;
                }
            }

            self::assertNotEmpty($candidates, sprintf(
                'Le metier « %s » n\'a aucun outil de type « %s » a la fois equipable (action `equip.tool` '
                . "d'un skill) et vendu par un PNJ : l'artisanat y est impossible.",
                $craft,
                $toolType
            ));
        }
    }

    /**
     * Les arbres de talent et les recettes ont ete ecrits separement : un skill peut
     * referencer un slug de recette qui n'existe pas, auquel cas il ne debloque rien
     * et l'echec est totalement silencieux.
     *
     * On ne verifie ici que le plancher — chaque metier a **au moins un** skill qui
     * debloque une recette de niveau 1 reellement livree. La reconciliation complete
     * des deux jeux de donnees est un chantier a part (cf. ROADMAP_DONE, ECO-02).
     */
    public function testEveryCraftHasASkillUnlockingAnExistingFirstTierRecipe(): void
    {
        $recipesByCraft = [];
        foreach ($this->firstTierRecipes() as $recipe) {
            $recipesByCraft[$recipe->getCraft()][] = $recipe->getSlug();
        }

        $unlocked = $this->skillUnlockedRecipeSlugs();

        foreach (array_keys(Item::CRAFT_TOOL_TYPES) as $craft) {
            $slugs = $recipesByCraft[$craft] ?? [];
            self::assertNotEmpty($slugs, sprintf('Le metier « %s » n\'a aucune recette de niveau 1.', $craft));

            self::assertNotEmpty(
                array_intersect($slugs, $unlocked),
                sprintf(
                    'Aucun skill ne debloque de recette de niveau 1 existante pour « %s » : '
                    . "le metier n'a pas de porte d'entree.",
                    $craft
                )
            );
        }
    }

    /**
     * Slugs d'outils rendus equipables par un skill (`actions[].equip.tool`).
     *
     * @return list<string>
     */
    private function equippableToolSlugs(): array
    {
        $slugs = [];
        foreach ($this->skillActions('equip.tool') as $action) {
            foreach ($action['slugs'] ?? [] as $slug) {
                if (\is_string($slug) && '' !== $slug) {
                    $slugs[$slug] = true;
                }
            }
        }

        return array_keys($slugs);
    }

    /**
     * @return list<string>
     */
    private function skillUnlockedRecipeSlugs(): array
    {
        $slugs = [];
        foreach ($this->skillActions('craft') as $action) {
            foreach ($action['recipes'] ?? [] as $slug) {
                if (\is_string($slug) && '' !== $slug) {
                    $slugs[$slug] = true;
                }
            }
        }

        return array_keys($slugs);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function skillActions(string $actionKey): array
    {
        $actions = [];
        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            foreach ($skill->getActions() ?? [] as $action) {
                if (\is_array($action) && ($action['action'] ?? null) === $actionKey) {
                    $actions[] = $action;
                }
            }
        }

        return $actions;
    }

    /**
     * @return list<Recipe>
     */
    private function firstTierRecipes(): array
    {
        return $this->em->getRepository(Recipe::class)->findBy(['requiredLevel' => 1]);
    }

    /**
     * @return list<string>
     */
    private function ingredientSlugs(Recipe $recipe): array
    {
        $slugs = [];
        foreach ($recipe->getIngredients() as $ingredient) {
            if (\is_array($ingredient) && isset($ingredient['slug'])) {
                $slugs[] = (string) $ingredient['slug'];
            }
        }

        return $slugs;
    }

    /**
     * Index des slugs obtenables en solo, avec la provenance (pour le message
     * d'echec : savoir *comment* un ingredient est accessible vaut le detour).
     *
     * @return array<string, list<string>>
     */
    private function soloSources(): array
    {
        if (null !== $this->soloSources) {
            return $this->soloSources;
        }

        $sources = [];
        $add = static function (string $slug, string $origin) use (&$sources): void {
            if ('' === $slug) {
                return;
            }
            $sources[$slug] ??= [];
            if (!\in_array($origin, $sources[$slug], true)) {
                $sources[$slug][] = $origin;
            }
        };

        // 1. Filons de zone (action Recolter) — la source de reference du pivot PBBG.
        foreach ($this->em->getRepository(Zone::class)->findAll() as $zone) {
            foreach ($zone->getGatherResources() as $resource) {
                if (\is_array($resource) && isset($resource['item'])) {
                    $add((string) $resource['item'], 'filon:' . $zone->getSlug());
                }
            }
        }

        // 2. Stock des boutiques PNJ — le plancher garanti, insensible au marche joueur.
        foreach ($this->shopStockSlugs() as $slug) {
            $add($slug, 'pnj');
        }

        // 3. Butin de monstre.
        foreach ($this->em->getRepository(MonsterItem::class)->findAll() as $drop) {
            $item = $drop->getItem();
            if ($item instanceof Item) {
                $add($item->getSlug(), 'butin');
            }
        }

        // 4. Recompenses de quete.
        foreach ($this->em->getRepository(Quest::class)->findAll() as $quest) {
            foreach ($quest->getRewards()['items'] ?? [] as $key => $entry) {
                if (\is_array($entry) && isset($entry['genericItemSlug'])) {
                    $add((string) $entry['genericItemSlug'], 'quete');
                } elseif (\is_string($key)) {
                    // Forme heritee : slug => quantite.
                    $add($key, 'quete');
                }
            }
        }

        return $this->soloSources = $sources;
    }

    /**
     * Slugs vendus par un PNJ **rattache a une zone**.
     *
     * Le rattachement n'est pas un detail : depuis ZON-27a, l'ecran de zone ne
     * liste que les PNJ de la zone courante. Un marchand sans zone existe en base
     * et son echoppe repond, mais aucun ecran n'y mene — il ne peut donc pas
     * servir de plancher.
     *
     * @return list<string>
     */
    private function shopStockSlugs(): array
    {
        $slugs = [];
        foreach ($this->em->getRepository(Pnj::class)->findAll() as $pnj) {
            if (null === $pnj->getZone()) {
                continue;
            }
            foreach ($pnj->getShopItems() ?? [] as $slug) {
                if (\is_string($slug) && '' !== $slug) {
                    $slugs[$slug] = true;
                }
            }
        }

        return array_keys($slugs);
    }

    /**
     * @param array<string, list<string>> $unreachable
     */
    private function describe(array $unreachable): string
    {
        $parts = [];
        foreach ($unreachable as $slug => $recipes) {
            $parts[] = sprintf('%s (%s)', $slug, implode(', ', $recipes));
        }

        return implode(' ; ', $parts);
    }
}
