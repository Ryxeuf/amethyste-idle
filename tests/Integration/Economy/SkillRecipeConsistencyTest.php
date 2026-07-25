<?php

namespace App\Tests\Integration\Economy;

use App\Entity\App\Pnj;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Entity\Game\Skill;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Coherence entre les arbres de talent et les recettes (ECO-18).
 *
 * Les deux jeux de donnees ont ete ecrits separement et jamais croises. Un skill
 * qui cite un slug de recette inexistant **ne debloque rien, sans la moindre
 * erreur** : le joueur depense ses points, le skill s'affiche appris, et aucune
 * recette n'apparait. Symetriquement, une recette qu'aucun skill ne debloque est
 * du contenu mort.
 *
 * L'audit trouvait 33 slugs cites inexistants et 37 recettes jamais debloquees.
 * Les rattachements evidents ont ete faits ; le reste demande de **creer du
 * contenu** (recettes d'acier, de cuir de dragon, carquois, elixirs manquants),
 * ce qui n'est pas de la plomberie et sort du perimetre de ce jalon.
 *
 * Les deux listes ci-dessous figent donc la dette restante : elle est visible,
 * elle ne peut plus grandir en silence, et chaque entree devra disparaitre avec
 * la recette correspondante.
 */
final class SkillRecipeConsistencyTest extends AbstractIntegrationTestCase
{
    /**
     * Slugs cites par un skill mais sans recette livree.
     *
     * Tous correspondent a du **contenu a ecrire**, pas a une faute de frappe :
     * il n'existe aucune recette d'acier, de cuir de dragon, de carquois, de
     * pierre a aiguiser, ni d'elixir de vitesse ou de transmutation.
     *
     * @var list<string>
     */
    private const RECIPES_TO_AUTHOR = [
        'recipe-dragon-boots',
        'recipe-dragon-vest',
        'recipe-enchanted-vest',
        'recipe-energy-potion-standard',
        'recipe-hardened-quiver',
        'recipe-heavy-steel-plate',
        'recipe-iron-chainmail',
        'recipe-iron-sword',
        'recipe-leather-quiver',
        'recipe-speed-elixir',
        'recipe-steel-axe',
        'recipe-steel-chainmail',
        'recipe-steel-dagger',
        'recipe-steel-plate',
        'recipe-steel-sword',
        'recipe-transmute-rare',
        'recipe-whetstone',
    ];

    /**
     * Recettes livrees qu'aucun skill ne debloque.
     *
     * Une seule subsiste : aucun skill d'alchimie ne parle de poison, et en
     * inventer un serait une decision de game design, pas une correction.
     *
     * @var list<string>
     */
    private const RECIPES_WITHOUT_TREE_NODE = [
        'recipe-poison-vial',
    ];

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
