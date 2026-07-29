<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Economy\ResourceAffinityCatalog;
use App\GameEngine\Economy\ResourceAffinityDefinitionLoader;
use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * La loi 10 appliquee au monde livre (ZON-36).
 *
 * Le catalogue teste la **regle** ; ce fichier teste qu'elle couvre ce qui
 * existe. Les deux defauts qu'il ferme sont muets, comme ceux de la loi 9 :
 *
 * 1. une ressource **sans affinite** ne se verrait qu'au jour ou un systeme
 *    consommateur la sauterait sans rien dire — une fusion qui ne trouve pas ses
 *    intrants Eau, une robe qui ne sert aucun mage ;
 * 2. une **correction morte** — une decision ecrite pour un slug qui n'existe
 *    plus — donnerait l'illusion d'une table complete, et personne ne la
 *    relirait avant longtemps.
 *
 * Il tient aussi le garde-fou du prefixe : `leather-` designe autant la depouille
 * que la botte qu'on en tire.
 */
class ResourceAffinityCoverageTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function catalog(): ResourceAffinityCatalog
    {
        return new ResourceAffinityCatalog(new ResourceAffinityDefinitionLoader($this->root()));
    }

    /**
     * Tous les items produits par un filon du monde.
     *
     * @return list<string>
     */
    private function harvestedSlugs(): array
    {
        $loader = new ZoneDefinitionLoader($this->root());

        $slugs = [];
        foreach ($loader->loadFile($loader->defaultFile())['zones'] as $zone) {
            foreach ($zone['gather'] ?? [] as $resource) {
                $slugs[] = (string) $resource['item'];
            }
        }

        $slugs = array_values(array_unique($slugs));
        sort($slugs);

        self::assertNotEmpty($slugs, 'L\'extraction des filons a echoue : rien n\'est verifie.');

        return $slugs;
    }

    /**
     * Tous les slugs d'items declares, quel que soit le chemin de fixtures.
     *
     * @return list<string>
     */
    private function declaredSlugs(): array
    {
        preg_match_all(
            "/'slug' => '([a-z0-9-]+)'/",
            (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php'),
            $matches,
        );
        $slugs = $matches[1];

        foreach ((array) glob($this->root() . '/fixtures/game/item/*.yaml') as $file) {
            preg_match_all("/slug: '([a-z0-9-]+)'/", (string) file_get_contents((string) $file), $matches);
            $slugs = array_merge($slugs, $matches[1]);
        }

        $slugs = array_values(array_unique($slugs));
        sort($slugs);

        return $slugs;
    }

    // =====================================================================
    // Ce qui se recolte porte un flux
    // =====================================================================

    /**
     * Toute matiere qu'un filon produit a une affinite — sauf l'amethyste, qui
     * est le substrat et non un flux.
     */
    public function testEveryHarvestedResourceCarriesAnAffinity(): void
    {
        $catalog = $this->catalog();

        $orphans = [];
        foreach ($this->harvestedSlugs() as $slug) {
            if (\in_array($slug, $catalog->withoutAffinity(), true)) {
                continue;
            }

            if ($catalog->affinityOf($slug) === null) {
                $orphans[] = $slug;
            }
        }

        self::assertSame(
            [],
            $orphans,
            'Ces matieres se recoltent sans porter de flux. Le defaut est muet : les systemes qui liront '
            . 'l\'affinite les sauteront sans rien dire.',
        );
    }

    /**
     * L'amethyste reste sans affinite, et c'est un cas teste, pas un oubli
     * tolere (GAME_WORLD § 2.2).
     */
    public function testAmethystStaysWithoutAffinity(): void
    {
        $catalog = $this->catalog();

        self::assertSame(['ore-amethyst-crystal'], $catalog->withoutAffinity());
        self::assertTrue($catalog->covers('ore-amethyst-crystal'));
        self::assertNull($catalog->affinityOf('ore-amethyst-crystal'));
    }

    // =====================================================================
    // La table ne parle pas de matieres qui n'existent pas
    // =====================================================================

    /**
     * Une correction ecrite pour un slug absent est une decision qui ne
     * s'applique a rien, et qui donne a la table l'air d'etre complete.
     */
    public function testNoCorrectionNamesAMissingItem(): void
    {
        $declared = $this->declaredSlugs();

        $ghosts = [];
        foreach (array_keys($this->catalog()->corrections()) as $slug) {
            if (!\in_array($slug, $declared, true)) {
                $ghosts[] = $slug;
            }
        }

        self::assertSame([], $ghosts, 'Ces corrections nomment des matieres qui n\'existent pas.');
    }

    // =====================================================================
    // Le garde-fou du prefixe
    // =====================================================================

    /**
     * Tout ce qui porte un prefixe de ligne est **couvert ou exclu**, jamais
     * laisse au hasard.
     *
     * C'est le test qui rend l'exclusion load-bearing : une piece `leather-cape`
     * livree demain ferait rougir la CI au lieu de devenir silencieusement une
     * matiere premiere Bete.
     */
    public function testEveryPrefixedSlugIsEitherCoveredOrExplicitlyExcluded(): void
    {
        $catalog = $this->catalog();
        $table = (new ResourceAffinityDefinitionLoader($this->root()))->load();

        $prefixes = [];
        foreach ($table['lines'] as $linePrefixes) {
            $prefixes = array_merge($prefixes, $linePrefixes);
        }

        $undecided = [];
        foreach ($this->declaredSlugs() as $slug) {
            foreach ($prefixes as $prefix) {
                if (!str_starts_with($slug, $prefix)) {
                    continue;
                }

                if (!$catalog->covers($slug) && !\in_array($slug, $table['excluded'], true)) {
                    $undecided[] = $slug;
                }
            }
        }

        self::assertSame(
            [],
            array_values(array_unique($undecided)),
            'Ces slugs portent un prefixe de ligne sans que la table ait tranche : ressource ou pas ?',
        );
    }

    /**
     * Symetrique : une exclusion qui ne correspond a rien laisse croire qu'un
     * arbitrage a ete rendu la ou plus rien ne le demande.
     */
    public function testNoExclusionNamesAMissingItem(): void
    {
        $declared = $this->declaredSlugs();
        $table = (new ResourceAffinityDefinitionLoader($this->root()))->load();

        $ghosts = [];
        foreach ($table['excluded'] as $slug) {
            if (!\in_array($slug, $declared, true)) {
                $ghosts[] = $slug;
            }
        }

        self::assertSame([], $ghosts, 'Ces exclusions nomment des matieres qui n\'existent plus.');
    }

    /**
     * Les matieres nommees hors prefixe existent aussi — le depecage des Vallons
     * et l'ambre des Dunes entrent par la, et une faute de frappe y serait
     * invisible.
     */
    public function testEveryNamedLineSlugExists(): void
    {
        $declared = $this->declaredSlugs();
        $table = (new ResourceAffinityDefinitionLoader($this->root()))->load();

        $ghosts = [];
        foreach ($table['line_slugs'] as $slugs) {
            foreach ($slugs as $slug) {
                if (!\in_array($slug, $declared, true)) {
                    $ghosts[] = $slug;
                }
            }
        }

        self::assertSame([], $ghosts, 'Ces matieres nommees dans une ligne n\'existent pas.');
    }
}
