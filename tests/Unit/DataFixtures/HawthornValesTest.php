<?php

namespace App\Tests\Unit\DataFixtures;

use App\Enum\SettlementRank;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Les Vallons d'Aubepine, et les raisons qui les font exister (ZON-30).
 *
 * La zone n'est pas du decor ajoute : elle repare deux defauts nommes, et ce
 * fichier verrouille les proprietes qui les reparent. Renommer un filon ou
 * baisser un effectif ne casserait rien de visible — c'est exactement pour ca
 * qu'il faut l'ecrire ici.
 *
 * 1. **La zone-ecole du depeceur.** Toute sa faune est depecable, et calme. La
 *    Foret ne pouvait pas jouer ce role : sa faune est disputee par tout le
 *    serveur et melee de creatures qu'on ne depece pas.
 * 2. **L'exclusivite du lin** (GAME_ZONES, loi 1). Aucune autre zone n'en
 *    produit ; c'est ce qui ramenera un veteran dans une zone d'Acte I.
 * 3. **Jamais un goulot.** Les profils sont T0 et T1, les deux paliers les plus
 *    genereux du calibrage (BALANCE § 22.3).
 */
class HawthornValesTest extends TestCase
{
    private const SLUG = 'vallons-d-aubepine';

    /**
     * Profils de filon autorises dans les Vallons : `capacity => respawn`.
     * Ce sont les deux paliers que BALANCE § 22.3 declare non-goulots.
     */
    private const OPEN_PROFILES = [72 => 25200, 60 => 32400];

    /**
     * @return array{zones: list<array<string, mixed>>, connections: list<array<string, mixed>>}
     */
    private function world(): array
    {
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 3));

        return $loader->loadFile($loader->defaultFile());
    }

    /**
     * @return array<string, mixed>
     */
    private function vales(): array
    {
        foreach ($this->world()['zones'] as $zone) {
            if (self::SLUG === $zone['slug']) {
                return $zone;
            }
        }

        self::fail('Les Vallons d\'Aubepine sont absents du monde.');
    }

    public function testTheValesAreAWildernessOnTheSouthernRoad(): void
    {
        $zone = $this->vales();

        self::assertSame('wilderness', $zone['type']);
        self::assertFalse($zone['safe']);
        self::assertTrue($zone['enabled']);
    }

    /**
     * Le lin ne pousse **que** la (loi 1 : une zone, une source exclusive).
     *
     * Sans exclusivite, une zone d'Acte I ne revoit jamais un veteran : elle
     * n'a rien qu'il ne trouve ailleurs, plus pres et plus vite.
     */
    public function testFlaxGrowsInTheValesAndNowhereElse(): void
    {
        $sources = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ($zone['gather'] ?? [] as $resource) {
                if ('plant-flax' === $resource['item']) {
                    $sources[] = $zone['slug'];
                }
            }
        }

        self::assertSame([self::SLUG], $sources);
    }

    /**
     * Aucun filon des Vallons n'est un goulot.
     *
     * C'est la zone du debutant : un tampon etroit y transformerait
     * l'apprentissage en attente, et la premiere heure de jeu en file d'attente.
     */
    public function testNoVeinOfTheValesIsEverABottleneck(): void
    {
        foreach ($this->vales()['gather'] as $resource) {
            self::assertArrayHasKey(
                $resource['capacity'],
                self::OPEN_PROFILES,
                sprintf('Le filon "%s" sort des paliers T0/T1.', $resource['slug']),
            );
            self::assertSame(
                self::OPEN_PROFILES[$resource['capacity']],
                $resource['respawn_seconds'],
                sprintf('Le filon "%s" melange la capacite d\'un palier et la periode d\'un autre.', $resource['slug']),
            );
        }
    }

    /**
     * Les recoltes de la zone sont celles qui ont ete actées.
     *
     * Trois a la livraison — le ble (chaine de cuisine), le lin (fibre) et la
     * perche (peche d'apprentissage) — puis le hetre, que ZON-34 y a pose :
     * les Vallons sont l'une des **deux** sources du bois commun, celle du
     * bocage, et c'est ce qui empeche le T0 de la ligne du bois d'etre un
     * goulot.
     */
    public function testTheDeclaredHarvestsAreThere(): void
    {
        $items = array_column($this->vales()['gather'], 'item');
        sort($items);

        self::assertSame(['fish-perch', 'plant-flax', 'plant-wheat', 'wood-beech'], $items);
    }

    /**
     * **La nuit des Vallons est calme.** Aucun nocturne, et aucun pool nocturne
     * qui restreindrait le vivier : c'est le contraste voulu avec la Foret, et
     * la raison pour laquelle un debutant peut y jouer le soir.
     */
    public function testTheNightOfTheValesStaysCalm(): void
    {
        $zone = $this->vales();

        foreach ($zone['mobs'] as $mob) {
            self::assertFalse($mob['nocturnal'] ?? false, sprintf('"%s" rode la nuit dans une zone qui ne doit pas.', $mob['monster']));
        }

        self::assertSame([], $zone['explore']['night']['mob_slugs'] ?? []);
        // Et l'on s'y bat moins la nuit que le jour, la ou la Foret fait
        // l'inverse.
        self::assertLessThan($zone['explore']['weights']['mob'], $zone['explore']['night']['weights']['mob']);
    }

    /**
     * Toute la faune des Vallons se **depece**.
     *
     * C'est la propriete qui fait de la zone une ecole : le debutant qui tue
     * n'importe quoi ici en tire une matiere, et apprend le geste sans avoir a
     * choisir sa proie. « Depecer » ne veut pas dire « peau » — on plume un
     * corbeau et on leve une piece de gibier ; ce qui compte est qu'aucune
     * carcasse ne reparte vide.
     */
    public function testEverythingThatLivesInTheValesYieldsSomethingToTheSkinner(): void
    {
        $loot = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/MonsterItemFixtures.php');
        $butchered = ['leather_raw', 'leather_thick', 'feather_raw', 'meat_game'];

        foreach ($this->vales()['mobs'] as $mob) {
            $yields = false;
            foreach ($butchered as $material) {
                if (str_contains($loot, sprintf("['monster' => '%s', 'item' => '%s'", $mob['monster'], $material))) {
                    $yields = true;
                    break;
                }
            }

            self::assertTrue($yields, sprintf('"%s" vit dans la zone-ecole du depeceur sans rien laisser a depecer.', $mob['monster']));
        }
    }

    /**
     * La zone est **sur la route** : hub, Foret et Marais.
     *
     * C'est ce qui y depose du sediment sans que personne n'ait a s'y arreter
     * (levier 4, GAME_WORLD § 5.5) — et donc ce qui rend son foyer atteignable.
     */
    public function testTheValesSitOnTheSouthernRoad(): void
    {
        $neighbours = [];
        foreach ($this->world()['connections'] as $connection) {
            if (self::SLUG === $connection['from']) {
                $neighbours[] = $connection['to'];
            }
            if (self::SLUG === $connection['to']) {
                $neighbours[] = $connection['from'];
            }
        }

        sort($neighbours);

        self::assertSame(['foret-des-murmures', 'marais-brumeux', 'village-de-lumiere'], $neighbours);
    }

    /**
     * Le hub est plus pres des Vallons que de toute autre zone.
     *
     * « Une demi-heure de marche du Fanal » n'est pas qu'une phrase de lore :
     * c'est ce qui fait de cette zone la premiere que verra un debutant.
     */
    public function testTheValesAreTheClosestZoneToTheHub(): void
    {
        $fromHub = [];
        foreach ($this->world()['connections'] as $connection) {
            // Les Jardins sont un quartier du hub, pas une zone du dehors : les
            // compter ferait gagner la comparaison a une porte interieure.
            if ('village-de-lumiere' === $connection['from'] && 'quartier-des-jardins' !== $connection['to']) {
                $fromHub[$connection['to']] = $connection['travel_seconds'];
            }
        }

        self::assertArrayHasKey(self::SLUG, $fromHub);
        self::assertSame(min($fromHub), $fromHub[self::SLUG]);
    }

    /**
     * La zone naît en **Ruine**, stock nul.
     *
     * Le Vieux Moulin est une ruine dans le lore parce qu'il en est une dans
     * les donnees : le foyer que les joueurs y feront lever sera le leur, et
     * c'est le premier chantier collectif que le monde livre leur offre.
     */
    public function testTheValesAreBornAsARuin(): void
    {
        $seed = (new SettlementDefinitionLoader(\dirname(__DIR__, 3)))->load()['seed'];

        self::assertArrayHasKey(self::SLUG, $seed);
        self::assertSame(SettlementRank::Ruin, $seed[self::SLUG]['rank']);
        self::assertSame(0, $seed[self::SLUG]['stock']);
    }
}
