<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Loi transverse : un minerai exige par une recette doit etre obtenable (ECO-24b).
 *
 * L'audit de la chaine de production (BALANCE §21.5) a trouve six minerais de
 * haut palier consommes par des recettes livrees et **qu'aucun filon declare ne
 * donne**. Ils ne vivaient que comme `ObjectLayer` herites — un chemin de
 * recolte que le pivot PBBG a laisse sans porte d'entree : ni
 * `/game/harvest/{spotId}` ni `/api/gathering/harvest/{spotId}` ne sont cites
 * par un gabarit ou un controleur Stimulus, et l'ecran de zone se contente d'en
 * **compter** les points d'interet. Autrement dit, la moitie haute de la ligne
 * du metal etait injouable, en silence.
 *
 * Le symptome est muet, comme toujours dans cette famille de defauts (ECO-02,
 * ECO-19, refonte des zones de depart) : la recette s'affiche, l'etabli la
 * refuse, et rien ne dit pourquoi.
 *
 * Trois voies rendent un minerai obtenable, et le test les accepte toutes :
 * un **filon declare** dans le YAML de zone, une **recette qui le produit**
 * (la transmutation alchimique du mithril), ou une **reserve d'extension**
 * explicitement listee ci-dessous.
 */
class OreSourceReferenceTest extends TestCase
{
    /**
     * Minerais volontairement sans source dans le jeu de base.
     *
     * Ce ne sont pas des oublis : la carte des minerais (GAME_ZONES §3) les
     * reserve aux extensions, et leurs recettes d'alliage sont livrees d'avance
     * a dessein — chaque lingot d'extension exige un intrant du jeu de base, ce
     * qui renverra les veterans dans les zones anciennes (GAME_WORLD §5.5).
     *
     * La liste doit rester **courte et justifiee**. Une entree qui gagne un
     * filon doit en sortir : `testNoStaleExtensionReservation` s'en assure.
     *
     * @var array<string, string>
     */
    private const EXTENSION_RESERVED = [
        // OBJ-02b : plus rien ici — l'adamantite et l'astretal ne sont plus
        // livres du tout (leurs recettes non plus), la reserve est un fichier
        // (docs/EXTENSION_RESERVE.md), pas une exception de test. Une entree
        // ne revient ici que si une extension livre le minerai AVANT son
        // filon, ce qui serait deja un defaut.
    ];

    /**
     * Minerais dont la rarete inversee impose plusieurs sources.
     *
     * « Une matiere de base doit etre presente dans beaucoup de zones, une
     * matiere de haut palier dans tres peu » (BALANCE §21.5b). Le bronze exige
     * cuivre et etain a parts egales : l'etain a source unique etranglait la
     * recette par sa moitie la plus rare.
     *
     * @var list<string>
     */
    private const BASE_ORES = ['ore-copper', 'ore-tin'];

    /**
     * L'amethyste, seule matiere du monde a n'avoir aucun palier (ZON-40).
     *
     * Les lois de rarete inversee de ce fichier parlent de **minerais** : une
     * matiere de haut palier a une source, une source unique la rend rare.
     * L'amethyste n'entre pas dans ce raisonnement, et le canon le dit sans
     * detour (GAME_WORLD § 13.3) : « **aucun gisement** — l'amethyste est un
     * sous-produit universel : toute action, partout, peut en rendre, en
     * quantite et bande variables selon la zone ». Elle est rare **par sa
     * bande**, jamais par sa geographie.
     *
     * L'exception est donc nommee ici plutot que contournee : elle vaut pour
     * cette matiere-la, pour cette raison-la, et pour aucune autre. Le jour ou
     * une seconde matiere sans palier apparaitrait, c'est cette liste qu'il
     * faudrait defendre — pas un test a reecrire.
     *
     * @var list<string>
     */
    private const WITHOUT_TIER = ['ore-amethyst-crystal'];

    /**
     * @return array{zones: list<array<string, mixed>>, connections: list<array<string, mixed>>}
     */
    private function world(): array
    {
        $loader = new ZoneDefinitionLoader($this->root());

        return $loader->loadFile($loader->defaultFile());
    }

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * Slug de minerai => zones qui le donnent.
     *
     * @return array<string, list<string>>
     */
    private function veinsByOre(): array
    {
        $veins = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ((array) ($zone['gather'] ?? []) as $resource) {
                $item = (string) $resource['item'];
                if (str_starts_with($item, 'ore-')) {
                    $veins[$item][] = (string) $zone['slug'];
                }
            }
        }

        return $veins;
    }

    /**
     * Minerais cites comme ingredient d'au moins une recette.
     *
     * @return list<string>
     */
    private function oresConsumedByRecipes(): array
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/RecipeFixtures.php');
        preg_match_all("/\['slug' => '(ore-[a-z0-9-]+)', 'quantity'/", $source, $matches);

        $ores = array_values(array_unique($matches[1]));
        sort($ores);

        return $ores;
    }

    /**
     * Minerais produits par une recette — le mithril de la transmutation.
     *
     * Les recettes designent leur resultat par **reference** (`ore_mithril`),
     * pas par slug : la correspondance se lit dans les fixtures d'items plutot
     * que de se deviner en remplacant les tirets bas.
     *
     * @return list<string>
     */
    private function oresProducedByRecipes(): array
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/RecipeFixtures.php');
        preg_match_all("/'result_ref' => '([a-z0-9_]+)'/", $source, $matches);

        $refToSlug = $this->itemRefToSlug();
        $produced = [];
        foreach ($matches[1] as $ref) {
            $slug = $refToSlug[$ref] ?? null;
            if (\is_string($slug) && str_starts_with($slug, 'ore-')) {
                $produced[$slug] = true;
            }
        }

        return array_keys($produced);
    }

    /**
     * @return array<string, string> reference de fixture => slug d'objet
     */
    private function itemRefToSlug(): array
    {
        $map = [];
        foreach ((array) glob($this->root() . '/fixtures/game/item/*.yaml') as $file) {
            $content = (string) file_get_contents((string) $file);
            preg_match_all("/^\s{2}([a-z0-9_]+) \(extends item\):.*?\n\s+slug: '([a-z0-9-]+)'/ms", $content, $matches, \PREG_SET_ORDER);
            foreach ($matches as $match) {
                $map[$match[1]] = $match[2];
            }
        }

        return $map;
    }

    /**
     * La loi : tout minerai exige par une recette est obtenable.
     */
    public function testEveryOreConsumedByARecipeHasASource(): void
    {
        $consumed = $this->oresConsumedByRecipes();
        $this->assertNotEmpty($consumed, 'Le test ne verifie rien si l\'extraction des recettes echoue.');

        $veins = $this->veinsByOre();
        $produced = $this->oresProducedByRecipes();

        $unobtainable = [];
        foreach ($consumed as $ore) {
            if (isset($veins[$ore]) || \in_array($ore, $produced, true) || isset(self::EXTENSION_RESERVED[$ore])) {
                continue;
            }
            $unobtainable[] = $ore;
        }

        $this->assertSame(
            [],
            $unobtainable,
            'Ces minerais sont consommes par une recette sans qu\'aucun filon, aucune recette ni aucune reserve '
            . 'd\'extension ne les donne : les recettes concernees sont injouables, en silence.',
        );
    }

    /**
     * La liste des reserves d'extension ne cite que des minerais reellement
     * sans filon.
     *
     * Sans ce controle, une reserve devenue obsolete dispenserait silencieusement
     * son minerai de la loi ci-dessus — meme motif que la liste des commandes
     * manuelles du calendrier ou du plan de reference i18n.
     */
    public function testNoStaleExtensionReservation(): void
    {
        $veins = $this->veinsByOre();

        $stale = array_values(array_filter(
            array_keys(self::EXTENSION_RESERVED),
            static fn (string $ore): bool => isset($veins[$ore]),
        ));

        $this->assertSame(
            [],
            $stale,
            'Ces minerais ont desormais un filon : les retirer de EXTENSION_RESERVED pour que la loi les couvre.',
        );
    }

    /**
     * Raretes inversees : les matieres de base se trouvent dans plusieurs zones.
     */
    public function testBaseOresAreAvailableInSeveralZones(): void
    {
        $veins = $this->veinsByOre();

        foreach (self::BASE_ORES as $ore) {
            $this->assertGreaterThanOrEqual(
                2,
                \count($veins[$ore] ?? []),
                sprintf(
                    'Le minerai de base "%s" n\'a qu\'une source. Le bronze exige cuivre et etain a parts egales : '
                    . 'une moitie a source unique etrangle la recette entiere.',
                    $ore,
                ),
            );
        }
    }

    /**
     * Raretes inversees, l'autre bout : un minerai de haut palier reste rare.
     *
     * Le palier se lit sur le profil du filon — T3 et T4 sont les deux profils
     * dont la capacite tombe sous 32 (cf. tableau de calibrage en tete de
     * `world_1.yaml`).
     */
    public function testHighTierOresHaveExactlyOneSource(): void
    {
        $counts = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ((array) ($zone['gather'] ?? []) as $resource) {
                $item = (string) $resource['item'];
                if (\in_array($item, self::WITHOUT_TIER, true)) {
                    continue;
                }
                if (str_starts_with($item, 'ore-') && (int) $resource['capacity'] < 32) {
                    $counts[$item] = ($counts[$item] ?? 0) + 1;
                }
            }
        }

        $this->assertNotEmpty($counts, 'Aucun filon de haut palier trouve : le test ne verifie rien.');

        $tooCommon = array_keys(array_filter($counts, static fn (int $n): bool => $n > 1));

        $this->assertSame(
            [],
            $tooCommon,
            'Ces minerais de haut palier ont plusieurs sources. La regle des raretes inversees veut l\'inverse : '
            . 'beaucoup de zones pour la matiere de base, tres peu pour le haut palier.',
        );
    }

    /**
     * Les filons neufs suivent un profil de calibrage, jamais des valeurs a la main.
     *
     * Le levier economique est le debit soutenu `R = capacity x 3600 /
     * respawn_seconds` ; une valeur posee au juge casse le calibrage sans que
     * rien ne s'en plaigne.
     */
    public function testEveryOreVeinUsesACalibratedTierProfile(): void
    {
        // Recalibrage du 2026-07-28 (BALANCE §22.3) : les periodes ont ete
        // allongees d'un facteur 9 a 13 selon le palier, les tampons non —
        // ils sont verrouilles par le bas (GAME_ZONE_ACTIONS §6.5).
        $profiles = [
            '72/25200' => 'T0 fondation',
            '60/32400' => 'T1 commun',
            '32/28800' => 'T2 peu commun',
            '24/36000' => 'T3 rare',
            '22/64800' => 'T4 epique',
            // ZON-40 — l'affleurement d'amethyste. Pas un palier : le plus petit
            // tampon du monde (elle affleure, GAME_ZONES loi 8) pour un debit
            // franc (elle est partout, GAME_WORLD § 13.3). Les deux ensemble.
            '16/21600' => 'A affleurement',
        ];

        $offenders = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ((array) ($zone['gather'] ?? []) as $resource) {
                if (!str_starts_with((string) $resource['item'], 'ore-')) {
                    continue;
                }
                $profile = sprintf('%d/%d', $resource['capacity'], $resource['respawn_seconds']);
                if (!isset($profiles[$profile])) {
                    $offenders[(string) $resource['slug']] = $profile;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Ces filons n\'utilisent aucun profil de palier du tableau de calibrage (en tete de world_1.yaml).',
        );
    }
}
