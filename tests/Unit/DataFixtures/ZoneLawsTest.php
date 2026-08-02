<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Les lois de zone valent contrat (ZON-33).
 *
 * GAME_ZONES § 0 en enonce dix. Cinq sont deja tenues ailleurs — les raretes
 * inversees par `OreSourceReferenceTest`, l'harmonie des recoltes par
 * `HarvestHarmonyTest`, les affinites par `ResourceAffinityCoverageTest`. Ce
 * fichier prend les quatre qui n'avaient pas de gardien, et elles ont un point
 * commun : **leur violation ne se voit pas**.
 *
 * - Une zone **sans exclusivite** n'a aucune raison qu'on y aille. Rien ne
 *   plante ; elle se vide, simplement, et on met des mois a comprendre pourquoi.
 * - Un **objectif de quete sans source** rend la quete infaisable, et le journal
 *   continue d'afficher « 0 / 3 » sans dire ou chercher. C'est le defaut le plus
 *   grave des quatre, parce que le joueur croit que c'est lui qui cherche mal.
 * - Un **ingredient sans source** rend une recette incraftable, silencieusement.
 * - Une **zone coupee du graphe** est du contenu livre et inatteignable.
 *
 * Deux defauts trouves a l'ecriture, et corriges par le meme jalon : le
 * champignon veneneux ne s'achetait qu'a l'echoppe de Morwen, et la racine de
 * marais n'existait nulle part — alors que la quete qui les demande dit
 * « **recoltez** ». Tous deux avaient echappe a la loi 9 parce que leurs slugs
 * ne portent pas le prefixe `plant-` sur lequel ZON-35 s'appuyait : c'est une
 * faille de nommage, et c'est pourquoi les lois d'ici ne s'appuient sur aucun
 * prefixe.
 */
class ZoneLawsTest extends TestCase
{
    /**
     * Les zones du **jeu de base** (GAME_ZONES § 1).
     *
     * La Mer de Sel, le Pas de Givre et le Glacier du Silence sont livres mais
     * appartiennent a l'Extension 1 (§ 4) : la loi 1 ne s'y applique pas encore,
     * leur dossier n'etant pas ecrit.
     *
     * @var list<string>
     */
    private const BASE_WORLD = [
        'village-de-lumiere',
        'quartier-des-jardins',
        'vallons-d-aubepine',
        'foret-des-murmures',
        'mines-profondes',
        'marais-brumeux',
        'crete-de-ventombre',
        'dunes-d-ambre',
        'cite-ensevelie',
    ];

    /**
     * La seule zone du monde de base sans exclusivite, et ce n'est pas un oubli.
     *
     * GAME_ZONES § 2.1 traite « le Fanal + Jardins » comme **un seul dossier** :
     * le sanctuaire. Les Jardins en sont le quartier d'artisanat — ni faune, ni
     * filon, par construction (« le Fanal garantit le plancher, jamais le
     * plafond »). Leur donner une exclusivite serait donner au sanctuaire une
     * raison de le quitter.
     *
     * @var list<string>
     */
    private const SANCTUARY_ANNEX = ['quartier-des-jardins'];

    /**
     * Matieres volontairement sans source dans le jeu de base.
     *
     * Vide depuis OBJ-02b : les minerais d'extension et leurs recettes ne
     * sont plus livres du tout — la reserve est un fichier
     * (docs/EXTENSION_RESERVE.md), pas une exception de test.
     *
     * @var list<string>
     */
    private const EXTENSION_RESERVED = [];

    /**
     * Profils de palier du calibrage (BALANCE § 22.3, en tete de `world_1.yaml`).
     *
     * @var array<string, int>
     */
    private const TIER_PROFILES = [
        '72/25200' => 0,
        '60/32400' => 1,
        '32/28800' => 2,
        '24/36000' => 3,
        '22/64800' => 4,
    ];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * @return array{zones: list<array<string, mixed>>, connections: list<array<string, mixed>>}
     */
    private function world(): array
    {
        $loader = new ZoneDefinitionLoader($this->root());

        return $loader->loadFile($loader->defaultFile());
    }

    private function fixture(string $relative): string
    {
        return (string) file_get_contents($this->root() . '/' . $relative);
    }

    // =====================================================================
    // Loi 1 — chaque zone a une raison qu'on y aille
    // =====================================================================

    /**
     * Chaque zone du monde de base a au moins une **source exclusive** : une
     * matiere ou une bete qu'on ne trouve nulle part ailleurs.
     *
     * Sans elle, la zone n'est qu'un detour : tout ce qu'elle donne se prend
     * ailleurs, souvent plus pres. Rien ne signale le defaut — la zone existe,
     * s'affiche, et se vide.
     */
    public function testEveryBaseZoneHasAnExclusiveSource(): void
    {
        $zonesByItem = [];
        $zonesByMonster = [];

        foreach ($this->world()['zones'] as $zone) {
            $slug = (string) $zone['slug'];
            foreach ((array) ($zone['gather'] ?? []) as $resource) {
                $zonesByItem[(string) $resource['item']][$slug] = true;
            }
            foreach ((array) ($zone['mobs'] ?? []) as $mob) {
                $zonesByMonster[(string) $mob['monster']][$slug] = true;
            }
        }

        $barren = [];
        foreach (self::BASE_WORLD as $slug) {
            if (\in_array($slug, self::SANCTUARY_ANNEX, true)) {
                continue;
            }

            $exclusive = 0;
            foreach ([$zonesByItem, $zonesByMonster] as $index) {
                foreach ($index as $zones) {
                    if ($zones === [$slug => true]) {
                        ++$exclusive;
                    }
                }
            }

            if ($exclusive === 0) {
                $barren[] = $slug;
            }
        }

        self::assertSame(
            [],
            $barren,
            'Ces zones n\'ont aucune source exclusive : tout ce qu\'elles donnent se prend ailleurs. Rien ne '
            . 'plantera — elles se videront, et il faudra des mois pour comprendre pourquoi.',
        );
    }

    /**
     * Les zones du monde de base sont **toutes** declarees.
     *
     * Le garde-fou du garde-fou : sans lui, retirer une zone de `world_1.yaml`
     * ferait passer la loi 1 en vert au lieu de la faire echouer.
     */
    public function testTheBaseWorldIsFullyDeclared(): void
    {
        $declared = [];
        foreach ($this->world()['zones'] as $zone) {
            $declared[] = (string) $zone['slug'];
        }

        $missing = array_values(array_diff(self::BASE_WORLD, $declared));

        self::assertSame([], $missing, 'Ces zones du monde de base ne sont plus declarees.');
    }

    // =====================================================================
    // Loi 2 — les raretes inversees, sur toute la ligne du metal
    // =====================================================================

    /**
     * Le nombre de sources d'un minerai **ne croit jamais** avec son palier.
     *
     * `OreSourceReferenceTest` tient les deux bouts de l'echelle — plusieurs
     * zones pour le cuivre et l'etain, une seule au-dessus de T3. La loi 2 dit
     * la forme entiere : un debutant ne doit jamais chercher une matiere de
     * base, un veteran ne doit jamais la trouver en passant.
     *
     * Le palier d'un minerai est celui de sa source **la plus accessible** : ce
     * qui compte est ou le joueur le rencontre pour la premiere fois, pas le
     * filon le plus exigeant qui en donne.
     */
    public function testMetalSourcesNeverGrowWithTheTier(): void
    {
        $tiers = [];
        $sources = [];

        foreach ($this->world()['zones'] as $zone) {
            foreach ((array) ($zone['gather'] ?? []) as $resource) {
                $item = (string) $resource['item'];
                if (!str_starts_with($item, 'ore-')) {
                    continue;
                }

                $profile = sprintf('%d/%d', $resource['capacity'], $resource['respawn_seconds']);
                $tier = self::TIER_PROFILES[$profile] ?? null;
                if ($tier === null) {
                    continue;
                }

                $tiers[$item] = min($tiers[$item] ?? \PHP_INT_MAX, $tier);
                $sources[$item] = ($sources[$item] ?? 0) + 1;
            }
        }

        self::assertNotEmpty($tiers, 'Aucun filon de metal calibre trouve : la loi 2 ne verifie rien.');

        $widestByTier = [];
        foreach ($tiers as $item => $tier) {
            $widestByTier[$tier] = max($widestByTier[$tier] ?? 0, $sources[$item]);
        }
        ksort($widestByTier);

        $previous = \PHP_INT_MAX;
        $inversions = [];
        foreach ($widestByTier as $tier => $widest) {
            if ($widest > $previous) {
                $inversions[] = sprintf('T%d atteint %d sources la ou le palier precedent plafonnait a %d', $tier, $widest, $previous);
            }
            $previous = $widest;
        }

        self::assertSame(
            [],
            $inversions,
            'La ligne du metal remonte : un palier haut a plus de sources que le palier sous lui. C\'est la '
            . 'rarete inversee a l\'envers.',
        );
    }

    // =====================================================================
    // Loi transverse — rien de demande qui ne soit obtenable
    // =====================================================================

    /**
     * Aucun ingredient de recette n'est introuvable.
     *
     * Quatre voies rendent une matiere obtenable, et le test les accepte
     * toutes : un filon, une recette qui la produit, un butin de monstre, un
     * etal de PNJ.
     */
    public function testEveryRecipeIngredientIsObtainable(): void
    {
        $obtainable = $this->obtainableSlugs();

        $orphans = [];
        foreach ($this->recipeIngredients() as $slug) {
            if (!isset($obtainable[$slug]) && !\in_array($slug, self::EXTENSION_RESERVED, true)) {
                $orphans[] = $slug;
            }
        }

        self::assertSame(
            [],
            $orphans,
            'Ces matieres sont exigees par une recette et introuvables dans le monde. L\'etabli refuse, et rien '
            . 'ne dit pourquoi.',
        );
    }

    /**
     * Aucun objectif de quete n'est introuvable — et il se **recolte** quand la
     * quete dit de le recolter.
     *
     * Le pire des defauts muets de la famille : le journal affiche « 0 / 3 » et
     * le joueur croit qu'il cherche mal. C'est ce test qui a trouve la racine de
     * marais, demandee par trois et produite par rien.
     */
    public function testEveryQuestObjectiveIsObtainable(): void
    {
        $obtainable = $this->obtainableSlugs();

        $orphans = [];
        foreach ($this->questCollectObjectives() as $slug) {
            if (!isset($obtainable[$slug])) {
                $orphans[] = $slug;
            }
        }

        self::assertSame(
            [],
            $orphans,
            'Ces objets sont demandes par une quete et introuvables. Le journal affichera « 0 / n » sans jamais '
            . 'dire ou chercher.',
        );
    }

    /**
     * Les deux plantes du Marais que ce jalon a sourcees restent sourcees.
     *
     * Les nommer evite la regression silencieuse : leurs slugs ne portent pas le
     * prefixe `plant-`, donc aucune des lois ecrites avant celle-ci ne les
     * voyait.
     */
    public function testTheTwoUnprefixedSwampPlantsAreGathered(): void
    {
        $gathered = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ((array) ($zone['gather'] ?? []) as $resource) {
                $gathered[(string) $resource['item']] = (string) $zone['slug'];
            }
        }

        foreach (['poisonous-mushroom', 'swamp-root'] as $slug) {
            self::assertArrayHasKey($slug, $gathered, sprintf('"%s" ne se recolte nulle part.', $slug));
            self::assertSame('marais-brumeux', $gathered[$slug], sprintf('"%s" a quitte le Marais.', $slug));
        }
    }

    // =====================================================================
    // Le graphe — rien de livre qui soit inatteignable
    // =====================================================================

    /**
     * Toute zone declaree se rejoint depuis le Fanal.
     *
     * Une zone coupee du graphe est du contenu livre et injouable — le meme
     * defaut, en plus grand, que celui d'une matiere sans filon.
     */
    public function testEveryZoneIsReachableFromTheSanctuary(): void
    {
        $reachable = $this->reachableFrom('village-de-lumiere');

        $declared = [];
        foreach ($this->world()['zones'] as $zone) {
            if ($zone['enabled'] ?? true) {
                $declared[] = (string) $zone['slug'];
            }
        }

        $stranded = array_values(array_diff($declared, array_keys($reachable)));

        self::assertSame([], $stranded, 'Ces zones sont livrees et ne se rejoignent depuis nulle part.');
    }

    /**
     * La Cite ensevelie reste au bout de la route du sud.
     *
     * GAME_ZONES § 1 ecrivait la liaison **Dunes → Cite directe** au lancement,
     * la Mer de Sel devant s'intercaler a l'ouverture de l'Extension 1. Le monde
     * livre a pris l'autre chemin : la Mer de Sel est deja la, et la route passe
     * par elle. L'ecart est assume et documente (§ 1, note de graphe) — ajouter
     * la liaison directe **raccourcirait** la route, ce que la note interdit
     * explicitement (« le monde s'agrandit sans jamais retrecir »).
     *
     * Ce qui se verrouille est donc l'invariant qui protege le joueur, et sa
     * clause conditionnelle : la Cite est atteignable depuis les Dunes, et si la
     * Mer de Sel quittait le graphe, la liaison directe devrait exister.
     */
    public function testTheBuriedCityStaysAtTheEndOfTheSouthernRoad(): void
    {
        $neighbours = $this->neighbours();

        self::assertArrayHasKey(
            'cite-ensevelie',
            $this->reachableFrom('dunes-d-ambre'),
            'La Cite ensevelie ne se rejoint plus depuis les Dunes : la route du sud est coupee.',
        );

        if (!isset($neighbours['mer-de-sel'])) {
            self::assertContains(
                'cite-ensevelie',
                $neighbours['dunes-d-ambre'] ?? [],
                'La Mer de Sel a quitte le graphe : la piste des caravanes d\'autrefois doit alors relier les '
                . 'Dunes a la Cite directement (GAME_ZONES § 1).',
            );
        }
    }

    // =====================================================================
    // Extraction
    // =====================================================================

    /**
     * @return array<string, list<string>>
     */
    private function neighbours(): array
    {
        $adjacency = [];
        foreach ($this->world()['connections'] as $connection) {
            // Une liaison desactivee ne mene nulle part : la compter ferait
            // passer pour joignable une zone que le joueur ne peut pas atteindre.
            if (!($connection['enabled'] ?? true)) {
                continue;
            }

            $from = (string) $connection['from'];
            $to = (string) $connection['to'];

            $adjacency[$from][] = $to;
            if ($connection['bidirectional'] ?? false) {
                $adjacency[$to][] = $from;
            }
        }

        return $adjacency;
    }

    /**
     * @return array<string, true>
     */
    private function reachableFrom(string $origin): array
    {
        $adjacency = $this->neighbours();

        $seen = [$origin => true];
        $stack = [$origin];
        while ($stack !== []) {
            $current = array_pop($stack);
            foreach ($adjacency[$current] ?? [] as $next) {
                if (!isset($seen[$next])) {
                    $seen[$next] = true;
                    $stack[] = $next;
                }
            }
        }

        return $seen;
    }

    /**
     * Tout ce qu'un joueur peut obtenir, par n'importe laquelle des quatre voies.
     *
     * @return array<string, true>
     */
    private function obtainableSlugs(): array
    {
        $obtainable = [];

        foreach ($this->world()['zones'] as $zone) {
            foreach ((array) ($zone['gather'] ?? []) as $resource) {
                $obtainable[(string) $resource['item']] = true;
            }
            foreach ((array) ($zone['pnjs'] ?? []) as $pnj) {
                foreach ((array) ($pnj['shop_items'] ?? []) as $item) {
                    $obtainable[(string) $item] = true;
                }
            }
        }

        $refToSlug = $this->itemRefToSlug();

        preg_match_all("/'result_ref' => '([a-z0-9_]+)'/", $this->fixture('src/DataFixtures/RecipeFixtures.php'), $matches);
        foreach ($matches[1] as $reference) {
            if (isset($refToSlug[$reference])) {
                $obtainable[$refToSlug[$reference]] = true;
            }
        }

        preg_match_all("/'item' => '([a-z0-9_]+)'/", $this->fixture('src/DataFixtures/MonsterItemFixtures.php'), $matches);
        foreach ($matches[1] as $reference) {
            if (isset($refToSlug[$reference])) {
                $obtainable[$refToSlug[$reference]] = true;
            }
        }

        foreach ((array) glob($this->root() . '/src/DataFixtures/*Fixtures.php') as $file) {
            preg_match_all("/'shopItems' => \[(.*?)\]/s", (string) file_get_contents((string) $file), $blocks);
            foreach ($blocks[1] as $block) {
                preg_match_all("/'([a-z0-9-]+)'/", $block, $items);
                foreach ($items[1] as $slug) {
                    $obtainable[$slug] = true;
                }
            }
        }

        self::assertNotEmpty($obtainable, 'L\'extraction des sources a echoue : rien n\'est verifie.');

        return $obtainable;
    }

    /**
     * @return list<string>
     */
    private function recipeIngredients(): array
    {
        preg_match_all(
            "/\['slug' => '([a-z0-9-]+)', 'quantity'/",
            $this->fixture('src/DataFixtures/RecipeFixtures.php'),
            $matches,
        );

        $slugs = array_values(array_unique($matches[1]));
        sort($slugs);

        self::assertNotEmpty($slugs, 'Aucun ingredient de recette trouve : la loi transverse ne verifie rien.');

        return $slugs;
    }

    /**
     * @return list<string>
     */
    private function questCollectObjectives(): array
    {
        preg_match_all(
            "/'collect' => \[(.*?)\]/s",
            $this->fixture('src/DataFixtures/QuestFixtures.php'),
            $blocks,
        );

        $slugs = [];
        foreach ($blocks[1] as $block) {
            preg_match_all("/'([a-z0-9-]+)' => \d+/", $block, $items);
            $slugs = array_merge($slugs, $items[1]);
        }

        $slugs = array_values(array_unique($slugs));
        sort($slugs);

        self::assertNotEmpty($slugs, 'Aucun objectif de collecte trouve : la loi ne verifie rien.');

        return $slugs;
    }

    /**
     * @return array<string, string> reference de fixture => slug d'objet
     */
    private function itemRefToSlug(): array
    {
        $map = [];

        // Lecture ligne a ligne plutot qu'en une expression sur tout le
        // fichier : `ItemFixtures.php` fait pres de cinq mille lignes, et une
        // expression a quantificateurs imbriques y depasse la limite de retour
        // arriere de PCRE — `preg_match_all` rend alors zero correspondance
        // **sans erreur**. Le defaut serait muet, et le test passerait en
        // verifiant le vide.
        $reference = null;
        foreach (explode("\n", $this->fixture('src/DataFixtures/ItemFixtures.php')) as $line) {
            if (preg_match("/^\s+'([a-z0-9_]+)' => \[$/", $line, $match) === 1) {
                $reference = $match[1];

                continue;
            }

            if ($reference !== null && preg_match("/'slug' => '([a-z0-9-]+)'/", $line, $match) === 1) {
                $map[$reference] = $match[1];
                $reference = null;
            }
        }

        foreach ((array) glob($this->root() . '/fixtures/game/item/*.yaml') as $file) {
            preg_match_all(
                "/^\s{2}([a-z0-9_]+)(?: \(extends [a-z0-9_]+\))?:.*?\n\s+slug: '([a-z0-9-]+)'/ms",
                (string) file_get_contents((string) $file),
                $matches,
                \PREG_SET_ORDER,
            );
            foreach ($matches as $match) {
                $map[$match[1]] = $match[2];
            }
        }

        self::assertNotEmpty($map, 'La correspondance reference => slug est vide : rien n\'est verifie.');

        return $map;
    }
}
