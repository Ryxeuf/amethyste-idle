<?php

namespace App\Tests\Integration\Progression;

use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Entity\Game\Skill;
use App\Enum\BindType;
use App\GameEngine\Progression\DomainCatalogDescriptions;
use App\GameEngine\Progression\FoundTreeCatalog;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Les cinq lois de l'arbre retrouve (DOM-10).
 *
 * GAME_ONBOARDING § 6.4 (arbitrage A17) et GAME_WORLD § 12.3. Une couche d'arbres
 * **hors registre**, ouverts par un parchemin qu'une rencontre remet — et cette
 * rencontre est declenchee par un **accomplissement**, jamais par un tirage.
 *
 * Ce que ca repare : *terminer un arbre ne donnait rien*. Le dernier palier etait
 * un cul-de-sac ; il devient une condition de rencontre.
 */
class FoundTreeContractTest extends AbstractIntegrationTestCase
{
    /**
     * **Loi 1 — lateral, jamais vertical.**.
     *
     * Des options, jamais de la puissance : sinon le joueur qui n'a pas croise
     * le vieux Nain est mecaniquement derriere. Le test le lit ou ca se voit —
     * aucun nœud d'un arbre retrouve ne porte de statistique de combat ni de
     * levier.
     */
    public function testAFoundTreeGrantsOptionsNeverPower(): void
    {
        $offenders = [];

        foreach ($this->foundDomains() as $domain) {
            foreach ($domain->getSkills() as $skill) {
                if ($skill->getDamage() > 0 || $skill->getHeal() > 0 || $skill->getHit() > 0
                    || $skill->getCritical() > 0 || $skill->getLife() > 0 || $skill->getLevers() !== []) {
                    $offenders[] = $skill->getSlug();
                }
            }
        }

        self::assertSame([], $offenders, "Ces nœuds d'arbre retrouve accordent de la puissance :\n" . implode("\n", $offenders));
    }

    /**
     * **Loi 2 — cumulatif, jamais manque.**.
     *
     * La rencontre reste disponible indefiniment pour quiconque remplit la
     * condition : pas de premier arrive, pas de fenetre, pas de date. Le test
     * porte sur la **forme** de la declaration : il n'existe aucun champ ou
     * ecrire une date, un quota ou une chance — *ce qu'on ne peut pas ecrire ne
     * peut pas deriver*.
     */
    public function testNothingCanExpireOrRunOut(): void
    {
        foreach ($this->catalog()->trees() as $key => $tree) {
            self::assertSame(
                ['label', 'earned_by', 'parchment'],
                array_keys($tree),
                sprintf('« %s » declare autre chose qu\'un accomplissement et un parchemin.', $key),
            );
        }
    }

    /**
     * **Loi 3 — jamais necessaire.**.
     *
     * Aucune recette, aucun palier, aucune quete normale n'en depend. Ce que le
     * prospecteur ouvre est une **voie alternative** : ce qu'elle produit
     * s'obtient autrement, et rien d'autre ne la consomme comme prerequis.
     */
    public function testNoNormalProgressionDependsOnAFoundTree(): void
    {
        $foundSkillIds = [];
        foreach ($this->foundDomains() as $domain) {
            foreach ($domain->getSkills() as $skill) {
                $foundSkillIds[(int) $skill->getId()] = $skill->getSlug();
            }
        }
        self::assertNotSame([], $foundSkillIds, 'Aucun arbre retrouve charge : le contrat ne mesure rien.');

        $dependents = [];
        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            foreach ($skill->getRequirements() as $requirement) {
                if (isset($foundSkillIds[(int) $requirement->getId()])
                    && !isset($foundSkillIds[(int) $skill->getId()])) {
                    $dependents[] = sprintf('%s exige %s', $skill->getSlug(), $requirement->getSlug());
                }
            }
        }

        self::assertSame([], $dependents, "Une progression normale depend d'un arbre retrouve :\n" . implode("\n", $dependents));

        // Et ce qu'il produit s'obtient **ailleurs**. La premiere ecriture de
        // ce test exigeait une autre **recette**, et elle a eu tort : le fer
        // s'obtient au filon, pas a l'etabli. *La question n'est pas « une autre
        // recette existe-t-elle ? » mais « cet objet a-t-il une autre source ? »*
        // — une voie alternative qui serait la seule voie n'en serait pas une.
        $gathered = $this->gatheredSlugs();

        foreach ($this->foundRecipes() as $recipe) {
            $result = $recipe->getResult();
            self::assertNotNull($result);

            $otherSources = \in_array($result->getSlug(), $gathered, true) ? 1 : 0;
            foreach ($this->em->getRepository(Recipe::class)->findAll() as $other) {
                if ($other->getId() !== $recipe->getId() && $other->getResult()?->getId() === $result->getId()) {
                    ++$otherSources;
                }
            }

            self::assertGreaterThan(
                0,
                $otherSources,
                sprintf('« %s » est la seule source de %s : un arbre retrouve deviendrait necessaire.', $recipe->getSlug(), $result->getSlug()),
            );
        }
    }

    /**
     * **Loi 4 — la condition est un accomplissement.**.
     *
     * Le slug d'un nœud reellement livre, jamais un jet. Un accomplissement qui
     * ne designerait rien rendrait l'arbre inatteignable **en silence**.
     */
    public function testTheConditionIsAnAccomplishmentThatExists(): void
    {
        foreach ($this->catalog()->trees() as $key => $tree) {
            $skill = $this->em->getRepository(Skill::class)->findOneBy(['slug' => $tree['earned_by']]);

            self::assertNotNull(
                $skill,
                sprintf('« %s » se gagne par « %s », qui n\'existe pas : l\'arbre serait inatteignable.', $key, $tree['earned_by']),
            );
            self::assertGreaterThan(
                0,
                $skill->getRequiredPoints(),
                sprintf('« %s » se gagne par un nœud gratuit : ce n\'est pas un accomplissement.', $key),
            );
        }
    }

    /**
     * **Loi 5 — le parchemin est lie.**.
     *
     * L'unique exception aux quatre conditions du parchemin de registre : *ce
     * qui circule entre joueurs est l'information, jamais l'objet*. Sans cela,
     * le premier decouvreur met le secret a l'hotel des ventes, et il meurt en
     * deux jours.
     */
    public function testTheFoundParchmentIsBoundAndUnsellable(): void
    {
        foreach ($this->catalog()->trees() as $key => $tree) {
            $parchment = $this->em->getRepository(Item::class)->findOneBy(['slug' => $tree['parchment']['slug']]);
            self::assertNotNull($parchment, sprintf('Le parchemin de « %s » manque.', $key));

            self::assertSame(BindType::BindOnPickup, $parchment->getBindType(), sprintf('Le parchemin de « %s » circule.', $key));
            self::assertFalse($parchment->getBindType()->isTradableBeforeUse());
            self::assertSame(0, $parchment->getPrice(), sprintf('Le parchemin de « %s » a un prix : aucune echoppe n\'en vend.', $key));
        }
    }

    /**
     * **Hors registre** : le catalogue public ne le mentionne pas.
     *
     * *Ce n'est pas un arbre cache : c'est un arbre qui n'a pas de vendeur.* Le
     * joueur ne peut pas decider de l'ouvrir, parce qu'il ignore qu'il existe.
     */
    public function testAFoundTreeIsAbsentFromThePublicCatalogue(): void
    {
        $described = array_keys((new DomainCatalogDescriptions(\dirname(__DIR__, 3)))->all());

        foreach ($this->foundDomains() as $domain) {
            self::assertNotContains(
                mb_strtolower($domain->getSlug()),
                $described,
                sprintf('« %s » figure au catalogue public : il cesserait d\'etre retrouve.', $domain->getTitle()),
            );
        }
    }

    /**
     * @return list<Domain>
     */
    private function foundDomains(): array
    {
        return array_values(array_filter(
            $this->em->getRepository(Domain::class)->findAll(),
            static fn (Domain $domain): bool => $domain->isOffRegister(),
        ));
    }

    /**
     * Ce que les filons rendent — l'autre source, celle qui n'est pas un etabli.
     *
     * @return list<string>
     */
    private function gatheredSlugs(): array
    {
        $slugs = [];
        foreach (glob(\dirname(__DIR__, 3) . '/config/game/zones/*.yaml') ?: [] as $file) {
            preg_match_all('/item:\s*([a-z0-9-]+)/', (string) file_get_contents($file), $matches);
            foreach ($matches[1] as $slug) {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * @return list<Recipe>
     */
    private function foundRecipes(): array
    {
        $slugs = [];
        foreach ($this->foundDomains() as $domain) {
            foreach ($domain->getSkills() as $skill) {
                foreach ($skill->getActions() ?? [] as $action) {
                    if (\is_array($action) && ($action['action'] ?? null) === 'craft') {
                        foreach ($action['recipes'] ?? [] as $slug) {
                            $slugs[] = (string) $slug;
                        }
                    }
                }
            }
        }

        $recipes = [];
        foreach (array_unique($slugs) as $slug) {
            $recipe = $this->em->getRepository(Recipe::class)->findOneBy(['slug' => $slug]);
            self::assertNotNull($recipe, sprintf('La recette « %s » manque.', $slug));
            $recipes[] = $recipe;
        }

        return $recipes;
    }

    private function catalog(): FoundTreeCatalog
    {
        return new FoundTreeCatalog(\dirname(__DIR__, 3));
    }
}
