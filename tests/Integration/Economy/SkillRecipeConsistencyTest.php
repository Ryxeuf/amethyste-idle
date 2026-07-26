<?php

namespace App\Tests\Integration\Economy;

use App\Entity\App\Pnj;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Entity\Game\Skill;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Coherence entre les arbres de talent et les recettes (ECO-18, ECO-19).
 *
 * Les deux jeux de donnees ont ete ecrits separement et jamais croises. Un skill
 * qui cite un slug de recette inexistant **ne debloque rien, sans la moindre
 * erreur** : le joueur depense ses points, le skill s'affiche appris, et aucune
 * recette n'apparait. Symetriquement, une recette qu'aucun skill ne debloque est
 * du contenu mort.
 *
 * L'audit trouvait 33 slugs cites inexistants et 37 recettes jamais debloquees.
 * ECO-18 a fait les rattachements evidents (33 → 17, 37 → 1) ; ECO-19 a ecrit
 * les 17 recettes manquantes et rattache la derniere orpheline. **Les deux
 * listes d'exception sont desormais vides.**
 *
 * Elles restent en place comme soupape : y ajouter une entree est un aveu
 * explicite, et le troisieme test verifie qu'une entree declaree correspond
 * toujours a un manque reel.
 */
final class SkillRecipeConsistencyTest extends AbstractIntegrationTestCase
{
    /**
     * Slugs cites par un skill mais sans recette livree.
     *
     * **La liste est vide** : les 17 recettes que l'audit ECO-18 avait relevees
     * comme du contenu a ecrire ont ete ecrites en ECO-19.
     *
     * Y ajouter une entree est un aveu explicite, pas une commodite — le
     * troisieme test verifie qu'une entree declaree est toujours reellement
     * manquante.
     *
     * @var list<string>
     */
    private const RECIPES_TO_AUTHOR = [];

    /**
     * Recettes livrees qu'aucun skill ne debloque.
     *
     * **La liste est vide** : `recipe-poison-vial`, seule rescapee apres
     * ECO-18, a rejoint le nœud « Concentration alchimique ».
     *
     * @var list<string>
     */
    private const RECIPES_WITHOUT_TREE_NODE = [];

    public function testEverySkillCitesAnExistingRecipe(): void
    {
        $existing = $this->recipeSlugs();
        $unknown = [];

        foreach ($this->skillRecipeSlugs() as $slug => $skills) {
            if (isset($existing[$slug]) || \in_array($slug, self::RECIPES_TO_AUTHOR, true)) {
                continue;
            }
            $unknown[] = sprintf('%s (%s)', $slug, implode(', ', $skills));
        }

        self::assertSame([], $unknown, sprintf(
            "Skill(s) citant une recette inexistante : %s.\n"
            . "Le skill s'apprend, ne debloque rien, et n'emet aucune erreur (ECO-18).\n"
            . 'Creez la recette, corrigez le slug, ou declarez-la dans RECIPES_TO_AUTHOR.',
            implode(' ; ', $unknown)
        ));
    }

    public function testEveryRecipeIsUnlockedBySomeSkill(): void
    {
        $unlocked = $this->skillRecipeSlugs();
        $unreachable = [];

        foreach (array_keys($this->recipeSlugs()) as $slug) {
            if (isset($unlocked[$slug]) || \in_array($slug, self::RECIPES_WITHOUT_TREE_NODE, true)) {
                continue;
            }
            $unreachable[] = $slug;
        }

        self::assertSame([], $unreachable, sprintf(
            "Recette(s) qu'aucun skill ne debloque : %s.\n"
            . 'Du contenu livre mais inatteignable — rattachez-le a un rang d\'arbre.',
            implode(', ', $unreachable)
        ));
    }

    /**
     * Une entree de dette qui n'en est plus une doit sortir de la liste, sinon
     * les exceptions survivent au probleme qu'elles decrivaient.
     */
    public function testDeclaredDebtIsStillReal(): void
    {
        $existing = $this->recipeSlugs();
        $unlocked = $this->skillRecipeSlugs();

        foreach (self::RECIPES_TO_AUTHOR as $slug) {
            self::assertArrayNotHasKey($slug, $existing, sprintf(
                'La recette « %s » existe desormais : retirez-la de RECIPES_TO_AUTHOR.',
                $slug
            ));
        }

        foreach (self::RECIPES_WITHOUT_TREE_NODE as $slug) {
            self::assertArrayNotHasKey($slug, $unlocked, sprintf(
                'La recette « %s » est desormais debloquee : retirez-la de RECIPES_WITHOUT_TREE_NODE.',
                $slug
            ));
        }
    }

    /**
     * Verrouille l'acquis d'ECO-02 : un outil vendu par un PNJ doit etre
     * equipable, faute de quoi on vend au joueur un objet qu'il ne pourra jamais
     * utiliser.
     */
    public function testEveryCraftToolSoldByAnNpcIsEquippableThroughTheTrees(): void
    {
        $equippable = $this->equippableToolSlugs();
        $craftToolTypes = array_values(Item::CRAFT_TOOL_TYPES);
        $itemRepository = $this->em->getRepository(Item::class);
        $unusable = [];

        foreach ($this->npcStockSlugs() as $slug) {
            $item = $itemRepository->findOneBy(['slug' => $slug]);
            if (null === $item || !\in_array($item->getToolType(), $craftToolTypes, true)) {
                continue;
            }
            if (!isset($equippable[$slug])) {
                $unusable[] = $slug;
            }
        }

        self::assertSame([], $unusable, sprintf(
            'Outil(s) d\'artisanat vendus par un PNJ mais qu\'aucun skill ne rend equipable : %s.',
            implode(', ', $unusable)
        ));
    }

    /**
     * @return array<string, true>
     */
    private function recipeSlugs(): array
    {
        $slugs = [];
        foreach ($this->em->getRepository(Recipe::class)->findAll() as $recipe) {
            $slugs[$recipe->getSlug()] = true;
        }

        self::assertNotEmpty($slugs, 'Aucune recette chargee : les fixtures de craft sont absentes.');

        return $slugs;
    }

    /**
     * Slug de recette → skills qui la debloquent.
     *
     * @return array<string, list<string>>
     */
    private function skillRecipeSlugs(): array
    {
        $map = [];
        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            foreach ($skill->getActions() ?? [] as $action) {
                if (!\is_array($action) || ($action['action'] ?? null) !== 'craft') {
                    continue;
                }
                foreach ($action['recipes'] ?? [] as $slug) {
                    if (\is_string($slug) && '' !== $slug) {
                        $map[$slug][] = $skill->getSlug();
                    }
                }
            }
        }

        return $map;
    }

    /**
     * @return array<string, true>
     */
    private function equippableToolSlugs(): array
    {
        $slugs = [];
        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            foreach ($skill->getActions() ?? [] as $action) {
                if (!\is_array($action) || ($action['action'] ?? null) !== 'equip.tool') {
                    continue;
                }
                foreach ($action['slugs'] ?? [] as $slug) {
                    if (\is_string($slug) && '' !== $slug) {
                        $slugs[$slug] = true;
                    }
                }
            }
        }

        return $slugs;
    }

    /**
     * @return list<string>
     */
    private function npcStockSlugs(): array
    {
        $slugs = [];
        foreach ($this->em->getRepository(Pnj::class)->findAll() as $pnj) {
            foreach ($pnj->getShopItems() ?? [] as $slug) {
                if (\is_string($slug) && '' !== $slug) {
                    $slugs[$slug] = true;
                }
            }
        }

        return array_keys($slugs);
    }
}
