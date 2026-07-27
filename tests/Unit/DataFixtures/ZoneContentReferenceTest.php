<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou du contenu declaratif de zone.
 *
 * `ZoneDefinitionLoader` ne valide que la **forme** : il ne touche jamais la
 * base, et ne peut donc pas savoir si le monstre, l'objet ou l'item de boutique
 * qu'une zone nomme existe reellement. `ZoneImporter` s'en apercoit a
 * l'execution, et sa reaction est de **poser un avertissement dans un rapport
 * d'import** puis de continuer — ce qui suppose que quelqu'un le lise.
 *
 * Le symptome en jeu est muet : une creature inconnue disparait simplement de
 * la population, un filon dont l'item n'existe pas est saute par
 * `GatherService::getGatherables()` sans un mot, et un article de boutique
 * introuvable ne s'affiche jamais. Meme motif que `QuestReferenceTest` et
 * `ZoneBossEventTest` : de la donnee declarative que plus rien ne relie a du
 * comportement.
 */
class ZoneContentReferenceTest extends TestCase
{
    /**
     * @return array{zones: list<array<string, mixed>>, connections: list<array<string, mixed>>}
     */
    private function world(): array
    {
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 3));

        return $loader->loadFile($loader->defaultFile());
    }

    /**
     * @return list<string>
     */
    private function knownMonsters(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/MonsterFixtures.php');
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $source, $matches);

        return $matches[1];
    }

    /**
     * Les objets viennent de deux sources : la grosse fixture PHP (plantes,
     * outils, equipement) et les fichiers YAML de `fixtures/game/item`
     * (minerais, poissons, cuirs, materia).
     *
     * @return list<string>
     */
    private function knownItems(): array
    {
        $root = \dirname(__DIR__, 3);

        preg_match_all("/'slug' => '([a-z0-9-]+)'/", (string) file_get_contents($root . '/src/DataFixtures/ItemFixtures.php'), $matches);
        $slugs = $matches[1];

        foreach ((array) glob($root . '/fixtures/game/item/*.yaml') as $file) {
            preg_match_all("/slug: '([a-z0-9-]+)'/", (string) file_get_contents((string) $file), $matches);
            $slugs = array_merge($slugs, $matches[1]);
        }

        return array_values(array_unique($slugs));
    }

    public function testEveryDeclaredMobExists(): void
    {
        $known = $this->knownMonsters();
        $this->assertNotEmpty($known, 'Le test ne verifie rien si l\'extraction du bestiaire echoue.');

        $declared = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ($zone['mobs'] ?? [] as $mob) {
                $declared[$mob['monster']] = $zone['slug'];
            }
        }

        $this->assertNotEmpty($declared, 'Aucune population declaree : le test ne verifie rien.');
        foreach ($declared as $monster => $zoneSlug) {
            $this->assertContains(
                $monster,
                $known,
                sprintf('La zone "%s" peuple un monstre inexistant ("%s") : l\'import le sautera avec un simple avertissement.', $zoneSlug, $monster),
            );
        }
    }

    public function testEveryGatherResourcePointsToARealItem(): void
    {
        $known = $this->knownItems();
        $this->assertNotEmpty($known, 'Le test ne verifie rien si l\'extraction des objets echoue.');

        foreach ($this->world()['zones'] as $zone) {
            foreach ($zone['gather'] ?? [] as $resource) {
                $this->assertContains(
                    $resource['item'],
                    $known,
                    sprintf('Le filon "%s" de la zone "%s" produit un objet inexistant ("%s") : il sera simplement absent de l\'ecran de zone.', $resource['slug'], $zone['slug'], $resource['item']),
                );
            }
        }
    }

    public function testEveryShopItemOfADeclaredPnjExists(): void
    {
        $known = $this->knownItems();

        foreach ($this->world()['zones'] as $zone) {
            foreach ($zone['pnjs'] ?? [] as $pnj) {
                foreach ($pnj['shop_items'] ?? [] as $item) {
                    $this->assertContains(
                        $item,
                        $known,
                        sprintf('Le PNJ "%s" (zone "%s") vend un objet inexistant ("%s") : l\'article ne s\'affichera jamais.', $pnj['slug'], $zone['slug'], $item),
                    );
                }
            }
        }
    }

    /**
     * Un filon est identifie par son slug **dans sa zone** : c'est la cle que
     * `ZoneVein` utilise pour porter le stock partage. Deux entrees homonymes
     * dans une meme zone partageraient donc le meme stock tout en promettant
     * deux ressources differentes.
     */
    public function testGatherSlugsAreUniqueWithinAZone(): void
    {
        foreach ($this->world()['zones'] as $zone) {
            $slugs = array_column($zone['gather'] ?? [], 'slug');
            $this->assertSame(
                array_values(array_unique($slugs)),
                $slugs,
                sprintf('La zone "%s" declare deux filons de meme slug : ils partageraient le meme stock.', $zone['slug']),
            );
        }
    }

    /**
     * Le pool nocturne (ZON-17) filtre les creatures **presentes dans la
     * zone** ; s'il ne rend rien, `ExploreService::resolveMob()` retombe sur le
     * vivier complet. Une nuit thematique qui nomme une espece absente n'est
     * donc pas une erreur visible : c'est une nuit qui ressemble au jour.
     *
     * La verification ne porte que sur les zones qui declarent leur population.
     * Celles qui l'heritent encore d'une carte TMX (`source_map`) la tiennent
     * hors de ce fichier.
     */
    public function testNightPoolsAreActuallyPopulated(): void
    {
        foreach ($this->world()['zones'] as $zone) {
            $pool = $zone['explore']['night']['mob_slugs'] ?? null;
            if (null === $pool || null === ($zone['mobs'] ?? null)) {
                continue;
            }

            $present = array_column($zone['mobs'], 'monster');
            foreach ($pool as $monster) {
                $this->assertContains(
                    $monster,
                    $present,
                    sprintf('La zone "%s" reserve sa nuit a "%s" sans qu\'aucun individu n\'y vive : la nuit ressemblera au jour.', $zone['slug'], $monster),
                );
            }
        }
    }

    /**
     * Un PNJ declare en YAML est upserte **par slug** ; un PNJ des fixtures PHP
     * n'en porte aucun. Declarer en YAML quelqu'un qui existe deja en PHP ne
     * met donc rien a jour : cela cree un second habitant du meme nom, dans la
     * meme zone.
     */
    public function testDeclaredPnjsDoNotDuplicateThePhpFixtures(): void
    {
        $phpPnjs = '';
        foreach ((array) glob(\dirname(__DIR__, 3) . '/src/DataFixtures/*PnjFixtures.php') as $file) {
            $phpPnjs .= (string) file_get_contents((string) $file);
        }
        $this->assertNotEmpty($phpPnjs, 'Le test ne verifie rien si aucune fixture PNJ n\'est lue.');

        $slugs = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ($zone['pnjs'] ?? [] as $pnj) {
                $slugs[] = $pnj['slug'];
                $this->assertStringNotContainsString(
                    $pnj['name'],
                    $phpPnjs,
                    sprintf('Le PNJ "%s" existe deja dans les fixtures PHP : la zone "%s" en aurait deux du meme nom.', $pnj['name'], $zone['slug']),
                );
            }
        }

        $this->assertNotEmpty($slugs, 'Aucun habitant declare : le test ne verifie rien.');
        $this->assertSame(
            array_values(array_unique($slugs)),
            $slugs,
            'Deux zones declarent le meme slug de PNJ : la seconde deplacerait le PNJ de la premiere.',
        );
    }
}
