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

        // MAT-03 : les materia ne s'ecrivent plus, elles se derivent des sorts
        // (`MateriaCatalogFixtures`). Leur slug est deductible — `m<niveau du
        // sort>-<slug du sort>` — donc le catalogue connu l'est aussi.
        $spellSource = (string) file_get_contents($root . '/src/DataFixtures/SpellFixtures.php');
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $spellSource, $blocks, PREG_OFFSET_CAPTURE);
        foreach ($blocks[1] as $i => [$key, $offset]) {
            $end = isset($blocks[1][$i + 1]) ? $blocks[1][$i + 1][1] : \strlen($spellSource);
            $body = substr($spellSource, $offset, $end - $offset);
            if (preg_match("/'slug' => '([a-z0-9-]+)'/", $body, $slug)) {
                preg_match("/'level' => (\d+)/", $body, $level);
                $slugs[] = sprintf('m%d-%s', (int) ($level[1] ?? 1), $slug[1]);
            }
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

    /**
     * Une zone hostile declare sa faune — elle ne l'herite plus d'une carte.
     *
     * C'est ce que ZON-26b ferme. Jusqu'ici, `Mob.zone` pouvait etre **derive**
     * de `Mob.map` via `Zone::sourceMap` : une zone sans `mobs:` n'etait donc
     * pas forcement vide, elle etait *illisible*. Le Marais et la Crete ont
     * vecu ainsi jusqu'au bout, seules zones du monde dont la population ne se
     * lisait nulle part — il fallait ouvrir une fixture PHP et suivre un nom de
     * carte pour savoir ce qui y vivait.
     *
     * La loi porte sur `safe: false` et non sur le type : c'est **l'hostilite**
     * qui promet des rencontres. Une cite sans faune n'est pas un oubli ; une
     * zone ou l'on peut mourir et qui ne declare rien, si.
     */
    public function testEveryHostileZoneDeclaresItsOwnWildlife(): void
    {
        $barren = [];
        $hostile = 0;

        foreach ($this->world()['zones'] as $zone) {
            if (($zone['safe'] ?? false) !== false) {
                continue;
            }

            ++$hostile;
            if ([] === ($zone['mobs'] ?? [])) {
                $barren[] = (string) $zone['slug'];
            }
        }

        $this->assertGreaterThan(0, $hostile, 'Aucune zone hostile lue : le test ne verifie rien.');
        $this->assertSame(
            [],
            $barren,
            'Ces zones hostiles ne declarent aucune faune. Une zone vide et une zone dont la population se '
            . 'derive en silence d\'une carte TMX se ressemblent trait pour trait — et c\'est la seconde qui '
            . 'coute des heures a diagnostiquer.',
        );
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
