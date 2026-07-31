<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Zone;
use App\GameEngine\Zone\ZoneDefinitionException;
use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

class ZoneDefinitionLoaderTest extends TestCase
{
    private ZoneDefinitionLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new ZoneDefinitionLoader('/project');
    }

    public function testDefaultFileTargetsWorldOne(): void
    {
        self::assertSame('/project/config/game/zones/world_1.yaml', $this->loader->defaultFile());
    }

    public function testNormalizeMinimalZone(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'village' => ['name' => 'Village'],
            ],
        ]);

        self::assertCount(1, $result['zones']);
        $zone = $result['zones'][0];
        self::assertSame('village', $zone['slug']);
        self::assertSame('Village', $zone['name']);
        self::assertSame(Zone::TYPE_WILDERNESS, $zone['type']);
        self::assertFalse($zone['safe']);
        self::assertTrue($zone['enabled']);
        self::assertNull($zone['explore']);
        self::assertNull($zone['gather']);
        self::assertSame([], $result['connections']);
    }

    public function testNormalizeFullZoneWithExploreGatherAndTranslations(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'foret' => [
                    'name' => 'Forêt',
                    'name_en' => 'Forest',
                    'description' => 'Des arbres',
                    'description_en' => 'Trees',
                    'type' => 'wilderness',
                    'safe' => false,
                    'source_map' => 'Forêt des Murmures',
                    'explore' => [
                        'weights' => ['mob' => 60, 'nothing' => 40, 'ignored' => 'x'],
                        'chest_gils_min' => 5,
                        'chest_gils_max' => 30,
                    ],
                    'gather' => [
                        ['slug' => 'herbes', 'item' => 'plant-mint', 'profession' => 'herbalism', 'capacity' => 24, 'respawn_seconds' => 900, 'yield_min' => 1, 'yield_max' => 3],
                    ],
                ],
            ],
        ]);

        $zone = $result['zones'][0];
        self::assertSame('Forest', $zone['name_en']);
        self::assertSame('Forêt des Murmures', $zone['source_map']);
        self::assertSame(['mob' => 60, 'nothing' => 40], $zone['explore']['weights']);
        self::assertSame(5, $zone['explore']['chest_gils_min']);
        self::assertSame(30, $zone['explore']['chest_gils_max']);
        self::assertCount(1, $zone['gather']);
        self::assertSame('plant-mint', $zone['gather'][0]['item']);
        self::assertSame(3, $zone['gather'][0]['yield_max']);
    }

    /**
     * ECO-24c — la normalisation est une **liste blanche**.
     *
     * Une cle absente du normaliseur est perdue en silence entre le YAML et la
     * base : le gate se declarerait sans jamais s'appliquer, et rien ne s'en
     * plaindrait. Le round-trip est donc un test a part entiere.
     */
    public function testNormalizeKeepsTheVeinSkillGate(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'mines' => [
                    'name' => 'Mines',
                    'gather' => [
                        ['slug' => 'sombracier', 'item' => 'ore-darksteel', 'profession' => 'mining', 'requires_skill' => 'miner-darksteel-xs'],
                        ['slug' => 'cuivre', 'item' => 'ore-copper', 'profession' => 'mining'],
                    ],
                ],
            ],
        ]);

        $gather = $result['zones'][0]['gather'];
        self::assertSame('miner-darksteel-xs', $gather[0]['requires_skill']);
        // Le gate est opt-in : un filon qui ne declare rien ne porte pas la cle.
        self::assertArrayNotHasKey('requires_skill', $gather[1]);
    }

    public function testNormalizeBidirectionalConnection(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'a' => ['name' => 'A'],
                'b' => ['name' => 'B'],
            ],
            'connections' => [
                ['from' => 'a', 'to' => 'b', 'travel_seconds' => 300, 'bidirectional' => true],
            ],
        ]);

        self::assertCount(1, $result['connections']);
        $connection = $result['connections'][0];
        self::assertSame('a', $connection['from']);
        self::assertSame('b', $connection['to']);
        self::assertSame(300, $connection['travel_seconds']);
        self::assertTrue($connection['bidirectional']);
        self::assertFalse($connection['requires_discovery']);
        self::assertTrue($connection['enabled']);
    }

    public function testMapShapeIsNormalized(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'a' => ['name' => 'A', 'map_x' => 50, 'map_y' => 55, 'map_shape' => "  40,40   60,40\n60,60 40,60 "],
            ],
        ]);

        // Les espaces surnumeraires disparaissent : la valeur part telle quelle
        // dans l'attribut `points` d'un <polygon>.
        self::assertSame('40,40 60,40 60,60 40,60', $result['zones'][0]['map_shape']);
    }

    public function testAZoneWithoutMapShapeHasNoOutline(): void
    {
        $result = $this->loader->normalize(['zones' => ['a' => ['name' => 'A']]]);

        self::assertNull($result['zones'][0]['map_shape']);
    }

    public function testMapShapeWithTooFewPointsRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['name' => 'A', 'map_shape' => '10,10 20,20']]]);
    }

    public function testMalformedMapShapePointRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['name' => 'A', 'map_shape' => '10,10 20;20 30,30']]]);
    }

    public function testOutOfRangeMapShapePointRejected(): void
    {
        // Les coordonnees sont des pourcentages : 140 sortirait du cadre.
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['name' => 'A', 'map_shape' => '10,10 140,20 30,30']]]);
    }

    /**
     * Le bandeau d'une zone, quand il est declare, l'est sous une seule forme.
     */
    public function testIllustrationIsNormalized(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'foret-des-murmures' => ['name' => 'Foret', 'illustration' => 'zones/foret-des-murmures.webp'],
            ],
        ]);

        self::assertSame('zones/foret-des-murmures.webp', $result['zones'][0]['illustration']);
    }

    /**
     * L'absence de bandeau est valide, et reste la norme.
     *
     * Les douze images arrivent une par une : exiger la cle des maintenant
     * rendrait le monde inimportable jusqu'a la derniere.
     */
    public function testAZoneWithoutIllustrationHasNone(): void
    {
        $result = $this->loader->normalize(['zones' => ['a' => ['name' => 'A']]]);

        self::assertNull($result['zones'][0]['illustration']);
    }

    /**
     * Un chemin qui remonte l'arborescence est refuse.
     *
     * La valeur part dans un attribut `src` : elle ne doit pas pouvoir designer
     * autre chose qu'un bandeau.
     */
    public function testIllustrationEscapingTheFolderRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['name' => 'A', 'illustration' => '../secret.png']]]);
    }

    /**
     * Un chemin absolu est refuse : la racine est implicite, pas negociable.
     */
    public function testAbsoluteIllustrationRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['name' => 'A', 'illustration' => '/images/zones/a.webp']]]);
    }

    /**
     * Une extension libre est refusee : le format des bandeaux est arrete.
     */
    public function testIllustrationWithAnotherExtensionRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['name' => 'A', 'illustration' => 'zones/a.png']]]);
    }

    /**
     * Le bandeau d'une **autre** zone est refuse.
     *
     * C'est le rejet qui compte le plus, et le seul qu'une validation de forme
     * ne verrait pas : le chemin est parfaitement bien ecrit, il designe
     * simplement la mauvaise zone. La loi de nommage est tenue par le code
     * plutot que par la discipline.
     */
    public function testIllustrationOfAnotherZoneRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['name' => 'A', 'illustration' => 'zones/autre-zone.webp']]]);
    }

    public function testEmptyZonesRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => []]);
    }

    public function testZoneMissingNameRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['type' => 'city']]]);
    }

    public function testUnknownZoneTypeRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize(['zones' => ['a' => ['name' => 'A', 'type' => 'nowhere']]]);
    }

    public function testConnectionToUnknownZoneRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize([
            'zones' => ['a' => ['name' => 'A']],
            'connections' => [['from' => 'a', 'to' => 'ghost']],
        ]);
    }

    public function testSelfLoopingConnectionRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize([
            'zones' => ['a' => ['name' => 'A']],
            'connections' => [['from' => 'a', 'to' => 'a']],
        ]);
    }

    public function testGatherResourceMissingItemRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->loader->normalize([
            'zones' => [
                'a' => [
                    'name' => 'A',
                    'gather' => [['slug' => 'x', 'profession' => 'mining']],
                ],
            ],
        ]);
    }

    /**
     * ZON-26b — la population declaree.
     *
     * Un `Mob` n'atteignait sa zone que par une carte : `WorldEntityZoneListener`
     * derive `Mob.zone` de `Mob.map` via `Zone::sourceMap`. Une zone declaree
     * sans carte ne pouvait donc avoir **aucune rencontre**, ce qui bloquait
     * toute nouvelle zone hostile.
     */
    public function testPnjsBlockIsNormalized(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'dune' => [
                    'name' => 'Dune',
                    'type' => 'wilderness',
                    'pnjs' => [
                        [
                            'slug' => 'dunes-caravanier',
                            'name' => 'Yazid',
                            'name_en' => 'Yazid',
                            'class_type' => 'merchant',
                            'greeting' => 'Le sable avale tout.',
                            'shop_items' => ['antidote', 'life-potion'],
                            'opens_at' => 6,
                            'closes_at' => 19,
                        ],
                        ['slug' => 'dunes-guetteuse', 'name' => 'Tahira'],
                    ],
                ],
            ],
        ]);

        $pnjs = $result['zones'][0]['pnjs'];
        self::assertCount(2, $pnjs);
        self::assertSame('dunes-caravanier', $pnjs[0]['slug']);
        self::assertSame(['antidote', 'life-potion'], $pnjs[0]['shop_items']);
        self::assertSame(6, $pnjs[0]['opens_at']);
        self::assertSame('villager', $pnjs[1]['class_type'], 'Un habitant sans metier est un villageois.');
        self::assertNull($pnjs[1]['shop_items'], 'Sans boutique declaree, le PNJ ne vend rien.');
        self::assertNull($pnjs[1]['opens_at'], 'Sans horaire, le PNJ est toujours joignable.');
    }

    /**
     * Un PNJ est un individu, la ou une creature est un effectif : sans slug,
     * re-jouer l'import le dupliquerait.
     */
    public function testAPnjEntryWithoutSlugIsRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);

        $this->loader->normalize([
            'zones' => [
                'dune' => ['name' => 'Dune', 'pnjs' => [['name' => 'Yazid']]],
            ],
        ]);
    }

    public function testAPnjEntryWithoutNameIsRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);

        $this->loader->normalize([
            'zones' => [
                'dune' => ['name' => 'Dune', 'pnjs' => [['slug' => 'dunes-caravanier']]],
            ],
        ]);
    }

    public function testAZoneWithoutPnjsBlockHasNoResident(): void
    {
        $result = $this->loader->normalize([
            'zones' => ['dune' => ['name' => 'Dune']],
        ]);

        self::assertNull($result['zones'][0]['pnjs']);
    }

    public function testMobsBlockIsNormalized(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'dune' => [
                    'name' => 'Dune',
                    'type' => 'wilderness',
                    'mobs' => [
                        ['monster' => 'scorpion', 'count' => 3],
                        ['monster' => 'creeping_shadow', 'nocturnal' => true, 'group_tag' => 'nuit'],
                    ],
                ],
            ],
        ]);

        $mobs = $result['zones'][0]['mobs'];
        self::assertCount(2, $mobs);
        self::assertSame('scorpion', $mobs[0]['monster']);
        self::assertSame(3, $mobs[0]['count']);
        self::assertFalse($mobs[0]['nocturnal']);
        self::assertNull($mobs[0]['group_tag']);
        self::assertSame(1, $mobs[1]['count'], 'Un effectif absent vaut un individu, pas zero.');
        self::assertTrue($mobs[1]['nocturnal']);
        self::assertSame('nuit', $mobs[1]['group_tag']);
    }

    public function testAMobEntryWithoutMonsterIsRejected(): void
    {
        $this->expectException(ZoneDefinitionException::class);
        $this->expectExceptionMessage('missing "monster"');

        $this->loader->normalize([
            'zones' => [
                'dune' => ['name' => 'Dune', 'type' => 'wilderness', 'mobs' => [['count' => 3]]],
            ],
        ]);
    }

    /**
     * Un effectif nul viderait la zone en silence : le plancher a 1 rend le
     * cas impossible plutot que de le laisser passer.
     */
    public function testAZeroCountIsFlooredToOne(): void
    {
        $result = $this->loader->normalize([
            'zones' => [
                'dune' => ['name' => 'Dune', 'type' => 'wilderness', 'mobs' => [['monster' => 'scorpion', 'count' => 0]]],
            ],
        ]);

        self::assertSame(1, $result['zones'][0]['mobs'][0]['count']);
    }

    public function testAZoneWithoutMobsBlockHasNullPopulation(): void
    {
        $result = $this->loader->normalize([
            'zones' => ['calme' => ['name' => 'Calme', 'type' => 'city']],
        ]);

        self::assertNull($result['zones'][0]['mobs']);
    }

    public function testLoadFileParsesShippedWorldOne(): void
    {
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 4));
        $result = $loader->loadFile($loader->defaultFile());

        $slugs = array_column($result['zones'], 'slug');
        self::assertContains('village-de-lumiere', $slugs);
        self::assertContains('crete-de-ventombre', $slugs);
        // HOU-01 : le lotissement doit rester joignable — une zone
        // residentielle coupee du hub rendrait le housing inaccessible.
        self::assertContains('quartier-des-jardins', $slugs);
        // ZON-26b : la premiere zone livree **sans carte d'origine**. Elle est
        // la preuve que le chemin declaratif fonctionne de bout en bout.
        self::assertContains('dunes-d-ambre', $slugs);
        // Acte 4 (tache 128b) : les deux bras longs, sel au sud et glace au nord.
        self::assertContains('mer-de-sel', $slugs);
        self::assertContains('glacier-du-silence', $slugs);
        // Le compte est epingle volontairement : il attrape une edition
        // accidentelle du graphe livre. 8 depuis ZON-26a (etoile + anneau),
        // 9 depuis HOU-01 (rattachement du Quartier des Jardins au hub),
        // 10 depuis ZON-26b (les Dunes d'Ambre au sud du marais),
        // 14 depuis 128b (les quatre zones de l'Acte 4),
        // 17 depuis ZON-30 (les Vallons, relies au hub, a la Foret et au Marais).
        self::assertCount(17, $result['connections']);

        $dunes = null;
        foreach ($result['zones'] as $zone) {
            if ('dunes-d-ambre' === $zone['slug']) {
                $dunes = $zone;
            }
        }

        self::assertNotNull($dunes);
        self::assertNull($dunes['source_map'], 'Les Dunes n\'ont aucune carte : c\'est tout l\'interet.');
        self::assertNotNull($dunes['mobs'], 'Une zone hostile sans population declaree n\'aurait aucune rencontre.');
        // ZON-26b-b : le bloc `explore` des Dunes tire un PNJ ; sans habitant
        // declare, cette branche du tirage ne menerait a rien.
        self::assertNotNull($dunes['pnjs'], 'Une zone sans carte d\'origine n\'a d\'habitants que declares.');
        self::assertContains('dunes-caravanier-yazid', array_column($dunes['pnjs'], 'slug'));
    }

    /**
     * Toute zone placee sur la carte porte un contour, et son centre declare
     * tombe dedans.
     *
     * Les contours sont traces a la main contre l'illustration : rien dans le
     * format ne garantit qu'un polygone entoure bien sa pastille. Sans ce test,
     * une retouche de coordonnees pourrait poser le point d'une zone hors de
     * son propre territoire — le brouillard percerait alors a un endroit et la
     * pastille s'afficherait a un autre, sans qu'aucune erreur ne se leve.
     */
    public function testShippedWorldOneKeepsEveryZoneCenterInsideItsOwnShape(): void
    {
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 4));
        $result = $loader->loadFile($loader->defaultFile());

        $placed = 0;
        foreach ($result['zones'] as $zone) {
            if (null === $zone['map_x'] || null === $zone['map_y']) {
                continue;
            }
            ++$placed;

            $shape = $zone['map_shape'];
            self::assertNotNull(
                $shape,
                sprintf('La zone "%s" est placee sur la carte mais n\'a aucun contour.', $zone['slug']),
            );

            $polygon = array_map(
                static fn (string $point): array => array_map('intval', explode(',', $point)),
                explode(' ', (string) $shape),
            );

            self::assertTrue(
                $this->isPointInPolygon($zone['map_x'], $zone['map_y'], $polygon),
                sprintf(
                    'Le centre (%d,%d) de la zone "%s" tombe hors de son propre contour.',
                    $zone['map_x'],
                    $zone['map_y'],
                    $zone['slug'],
                ),
            );
        }

        // Le compte est epingle comme celui des connexions : il attrape une
        // zone placee sur la carte sans qu'on y pense.
        self::assertSame(12, $placed);
    }

    /**
     * Lancer de rayon horizontal (algorithme pair-impair).
     *
     * @param list<array<int, int>> $polygon
     */
    private function isPointInPolygon(int $x, int $y, array $polygon): bool
    {
        $inside = false;
        $count = \count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = $polygon[$i];
            [$xj, $yj] = $polygon[$j];

            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Tout ingredient recoltable exige par une recette doit se trouver
     * quelque part (correctif 128b).
     *
     * Quatre plantes nourrissaient huit recettes d'alchimie de niveau 2 a 10
     * sans qu'aucune zone ne les produise — et l'une d'elles, la mandragore,
     * etait meme **exigee par une quete**. Le contenu existait, la boucle qui
     * y menait n'existait pas.
     *
     * Les plantes vendues en boutique PNJ sont exclues : elles ont deja une
     * source.
     */
    public function testEveryGatherableIngredientOfARecipeIsReachable(): void
    {
        $root = \dirname(__DIR__, 4);
        $items = (string) file_get_contents($root . '/src/DataFixtures/ItemFixtures.php');
        $recipes = (string) file_get_contents($root . '/src/DataFixtures/RecipeFixtures.php');

        $pnjStock = '';
        foreach ((array) glob($root . '/src/DataFixtures/*PnjFixtures.php') as $file) {
            $pnjStock .= (string) file_get_contents((string) $file);
        }

        preg_match_all("/'slug' => '((?:ore|plant|fish)-[a-z-]+)'/", $items, $matches);

        $loader = new ZoneDefinitionLoader($root);
        $definition = $loader->loadFile($loader->defaultFile());
        $gathered = [];
        foreach ($definition['zones'] as $zone) {
            foreach ($zone['gather'] ?? [] as $resource) {
                $gathered[] = $resource['item'];
            }
        }

        $unreachable = [];
        foreach (array_unique($matches[1]) as $slug) {
            if (\in_array($slug, $gathered, true)) {
                continue;
            }
            if (!str_contains($recipes, "'" . $slug . "'")) {
                continue;
            }
            if (str_contains($pnjStock, "'" . $slug . "'")) {
                continue;
            }
            $unreachable[] = $slug;
        }

        self::assertSame([], $unreachable, 'Une recette exige un ingredient qu\'aucune zone ne produit et qu\'aucun PNJ ne vend.');
    }

    public function testShippedWorldOneHasNoIsolatedZone(): void
    {
        // ZON-26a : une zone injoignable est un contenu mort. La verification
        // porte sur l'intention du graphe, pas sur un effectif fige.
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 4));
        $result = $loader->loadFile($loader->defaultFile());

        $connected = [];
        foreach ($result['connections'] as $connection) {
            $connected[$connection['from']] = true;
            $connected[$connection['to']] = true;
        }

        foreach (array_column($result['zones'], 'slug') as $slug) {
            self::assertArrayHasKey($slug, $connected, sprintf('La zone "%s" n\'est reliee a aucune autre.', $slug));
        }
    }
}
