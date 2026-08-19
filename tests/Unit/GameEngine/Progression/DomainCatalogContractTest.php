<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\Game\Domain;
use App\Enum\Element;
use App\GameEngine\Fight\ElementalMark;
use App\GameEngine\Progression\DomainCatalogDefinitionException;
use App\GameEngine\Progression\DomainCatalogDescriptions;
use App\GameEngine\Progression\FoundTreeCatalog;
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
 *
 * ARC-13b-b en ajoute une troisieme, qui tire dans le meme sens que la
 * deuxieme : **chaque element qui marque dit la trace qu'il laisse, et aucune
 * trace ne laisse fuir une valeur**. La phrase est le seul endroit du
 * catalogue ou une duree pourrait passer, puisque le loader n'a pas de champ
 * pour elle.
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
     * Loi 3 — chaque element **marque** dit ce qu'il laisse (ARC-13b-b).
     *
     * Les huit elements qui portent une marque ont leur phrase, et aucun
     * element de plus. `wood` n'y figure pas et ne doit pas y figurer : il
     * n'est l'element d'aucun arbre de combat, donc il ne marque rien — lui
     * ecrire une trace serait promettre une mecanique qui n'existe pas.
     */
    public function testEveryMarkedElementSaysTheTraceItLeaves(): void
    {
        $declared = array_keys($this->descriptions()->allElements());
        sort($declared);

        $marked = array_map(
            static fn (Element $element): string => $element->value,
            ElementalMark::markedElements(),
        );
        sort($marked);

        self::assertSame($marked, $declared, 'Les traces du catalogue ne recouvrent pas exactement les elements qui marquent.');
    }

    /**
     * Une trace ne dit **jamais** de valeur (GAME_ONBOARDING § 6.2).
     *
     * Le loader tient la loi par omission — il n'existe aucun champ pour une
     * durée ou un pourcentage —, mais rien n'empeche d'ecrire « pendant 2 tours »
     * dans la phrase elle-meme. Ce test ferme cette porte-la : aucun chiffre,
     * aucun signe de pourcentage, aucun vocabulaire de duree chiffree.
     */
    public function testATraceNeverLeaksAValue(): void
    {
        $offenders = [];

        foreach ($this->descriptions()->allElements() as $element => $trace) {
            if (preg_match('/\d|%/u', $trace) === 1) {
                $offenders[] = sprintf('%s : « %s »', $element, $trace);
            }
        }

        self::assertSame([], $offenders, implode("\n", $offenders));
    }

    /**
     * Le bloc des traces est **facultatif**, et une cle inconnue est refusee.
     *
     * Facultatif, parce qu'un catalogue sans traces reste un catalogue valide ;
     * strict, parce qu'une phrase rangee sous un element inexistant
     * n'apparaitrait nulle part et que personne ne s'en apercevrait.
     */
    public function testTheLoaderRefusesATraceForSomethingThatIsNotAnElement(): void
    {
        self::assertSame([], $this->descriptions()->normalizeElements([]));

        $this->expectException(DomainCatalogDefinitionException::class);
        $this->descriptions()->normalizeElements(['elements' => ['fue' => 'Une trace.']]);
    }

    /**
     * Slugs des arbres **publics** livres, derives exactement comme l'entite le fait.
     *
     * Les arbres **retrouves** (DOM-10) en sont retires, et c'est leur
     * definition meme : *ce n'est pas un arbre cache, c'est un arbre qui n'a
     * pas de vendeur*. Les deux lois du catalogue continuent de tirer en sens
     * inverse sur tout le reste — l'exception est nommee ici plutot que
     * dispersee en cas particuliers dans chacune.
     *
     * @return list<string>
     */
    private function shippedDomainSlugs(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/DomainFixtures.php');

        preg_match_all("/'([a-z]+)' => \['title' => '([^']+)'/", $source, $matches, \PREG_SET_ORDER);
        self::assertNotEmpty($matches, 'Aucun domaine trouve : la loi ne verifierait rien.');

        $found = (new FoundTreeCatalog(\dirname(__DIR__, 4)))->keys();

        $slugs = [];
        foreach ($matches as [, $key, $title]) {
            if (\in_array($key, $found, true)) {
                continue;
            }

            $domain = new Domain();
            $domain->setTitle($title);
            $slugs[] = mb_strtolower($domain->getSlug());
        }

        return $slugs;
    }
}
