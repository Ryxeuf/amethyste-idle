<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\Game\Domain;
use App\GameEngine\Progression\DomainCatalogDefinitionException;
use App\GameEngine\Progression\DomainCatalogDescriptions;
use PHPUnit\Framework\TestCase;

/**
 * Le contrat du catalogue (ONB-09).
 *
 * Deux lois, et elles tirent en sens inverse — c'est ce qui les rend utiles :
 *
 * 1. **Le catalogue est complet.** Chaque arbre livre y a son entree. Un arbre
 *    absent est un arbre dont personne ne sait qu'il peut s'ouvrir.
 * 2. **Le catalogue omet.** Il n'a aucun champ pour un nœud, une valeur ou un
 *    prerequis, et le loader refuse toute cle inconnue plutot que de l'ignorer.
 */
class DomainCatalogContractTest extends TestCase
{
    private function descriptions(): DomainCatalogDescriptions
    {
        return new DomainCatalogDescriptions(\dirname(__DIR__, 4));
    }

    /**
     * Loi 1 — un arbre livre sans entree de catalogue est inatteignable a la
     * lecture : le joueur ne peut pas decider d'ouvrir ce qu'il ignore.
     */
    public function testEveryShippedTreeHasACatalogueEntry(): void
    {
        $declared = array_keys($this->descriptions()->all());

        $missing = array_values(array_diff($this->shippedDomainSlugs(), $declared));
        self::assertSame([], $missing, sprintf(
            'Ces arbres n\'ont aucune entree de catalogue : %s.',
            implode(', ', $missing),
        ));
    }

    /**
     * Et l'inverse : une entree qui ne designe aucun arbre est un texte que
     * personne ne lira jamais, donc une divergence qui s'installe en silence.
     */
    public function testTheCatalogueDescribesNothingThatDoesNotExist(): void
    {
        $orphans = array_values(array_diff(array_keys($this->descriptions()->all()), $this->shippedDomainSlugs()));

        self::assertSame([], $orphans, sprintf(
            'Ces entrees de catalogue ne designent aucun arbre livre : %s.',
            implode(', ', $orphans),
        ));
    }

    /**
     * Loi 2 — le catalogue ne dit ni les nœuds, ni les valeurs, ni le premier
     * nœud, ni la specialisation terminale (GAME_ONBOARDING § 6.2).
     *
     * Le loader **refuse** un champ inconnu au lieu de l'ignorer. Ignorer
     * laisserait la donnee s'accumuler dans le fichier jusqu'au jour ou
     * quelqu'un l'afficherait, en toute bonne foi.
     */
    public function testTheLoaderRefusesAnythingBeyondTheTwoAllowedSentences(): void
    {
        $this->expectException(DomainCatalogDefinitionException::class);

        $this->descriptions()->normalize([
            'domains' => [
                'mineur' => [
                    'teaches' => 'À lire un filon.',
                    'equips' => 'Pioches.',
                    'first_node' => 'miner-copper-xs',
                ],
            ],
        ]);
    }

    public function testTheLoaderRefusesAnEntryWithoutItsTwoSentences(): void
    {
        $this->expectException(DomainCatalogDefinitionException::class);

        $this->descriptions()->normalize([
            'domains' => ['mineur' => ['teaches' => 'À lire un filon.']],
        ]);
    }

    /**
     * Le fichier livre se lit, et chaque entree porte bien ses deux phrases.
     */
    public function testTheShippedCatalogueLoads(): void
    {
        $entries = $this->descriptions()->all();

        self::assertNotEmpty($entries);
        foreach ($entries as $slug => $entry) {
            self::assertNotSame('', trim($entry['teaches']), sprintf('« %s » n\'apprend rien.', $slug));
            self::assertNotSame('', trim($entry['equips']), sprintf('« %s » ne permet de porter rien.', $slug));
        }
    }

    /**
     * Slugs des domaines livres, derives exactement comme l'entite le fait.
     *
     * @return list<string>
     */
    private function shippedDomainSlugs(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/DomainFixtures.php');

        preg_match_all("/'[a-z]+' => \['title' => '([^']+)'/", $source, $matches);
        self::assertNotEmpty($matches[1], 'Aucun domaine trouve : la loi ne verifierait rien.');

        $slugs = [];
        foreach ($matches[1] as $title) {
            $domain = new Domain();
            $domain->setTitle($title);
            $slugs[] = mb_strtolower($domain->getSlug());
        }

        return $slugs;
    }
}
