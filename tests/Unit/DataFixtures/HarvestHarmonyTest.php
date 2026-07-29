<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Les recoltes harmonisees, ou la loi 9 (ZON-35).
 *
 * L'audit a trouve un rapport de cinq contre un entre l'herboriste — 22 plantes,
 * dont douze sans source ou sans debouche — et le bucheron, qui en avait quatre.
 * Le desequilibre n'etait pas le compte : c'etait qu'un tiers du catalogue
 * **ne servait a rien**, et qu'un catalogue qui ment sur sa taille finit par
 * etre calibre sur ce mensonge.
 *
 * Ce fichier tient les deux invariants de la loi 9 (GAME_ZONES § 3 ter), et ils
 * se lisent dans les deux sens :
 *
 * 1. **Rien ne se recolte sans debouche.** Un filon dont la matiere n'a pas de
 *    recette est une promesse vide : le joueur ramasse, remplit son sac, et
 *    decouvre au comptoir que sa recolte ne vaut que son prix de vente.
 * 2. **Rien n'existe sans source.** Une matiere citee par une recette et
 *    introuvable dans le monde est pire : elle rend la recette incraftable sans
 *    que rien ne le dise.
 *
 * Les deux defauts sont muets. C'est pour cela qu'ils se verrouillent ici.
 */
class HarvestHarmonyTest extends TestCase
{
    /**
     * Les cinq plantes purgees par ce jalon.
     *
     * Elles n'avaient ni filon ni recette. Les nommer garantit qu'un retour
     * silencieux se voie : les ressusciter demande de les sourcer **et** de leur
     * donner un debouche, pas de recoller une entree de fixture.
     *
     * @var list<string>
     */
    private const PURGED = [
        'plant-dreamlily',
        'plant-sunblossom',
        'plant-thunderroot',
        'plant-whisperweed',
        'plant-wolfsbane',
    ];

    /**
     * Le profil de palier T4 (BALANCE § 22.3).
     *
     * @var array{0: int, 1: int}
     */
    private const TIER_FOUR = [22, 64800];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function recipes(): string
    {
        return (string) file_get_contents($this->root() . '/src/DataFixtures/RecipeFixtures.php');
    }

    /**
     * @return list<array<string, mixed>> tous les filons du monde
     */
    private function veins(): array
    {
        $loader = new ZoneDefinitionLoader($this->root());

        $veins = [];
        foreach ($loader->loadFile($loader->defaultFile())['zones'] as $zone) {
            foreach ($zone['gather'] ?? [] as $resource) {
                $resource['zone'] = $zone['slug'];
                $veins[] = $resource;
            }
        }

        self::assertNotEmpty($veins, 'L\'extraction des filons a echoue : rien n\'est verifie.');

        return $veins;
    }

    /**
     * Slugs de toutes les plantes et de tous les poissons declares.
     *
     * @return list<string>
     */
    private function declaredHarvestables(): array
    {
        $slugs = [];

        preg_match_all(
            "/'slug' => '((?:plant|fish)-[a-z0-9-]+)'/",
            (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php'),
            $matches,
        );
        $slugs = $matches[1];

        foreach ((array) glob($this->root() . '/fixtures/game/item/*.yaml') as $file) {
            preg_match_all("/slug: '((?:plant|fish)-[a-z0-9-]+)'/", (string) file_get_contents((string) $file), $matches);
            $slugs = array_merge($slugs, $matches[1]);
        }

        $slugs = array_values(array_unique($slugs));
        sort($slugs);

        return $slugs;
    }

    // =====================================================================
    // Les deux invariants de la loi 9
    // =====================================================================

    /**
     * Rien ne se recolte sans debouche.
     */
    public function testEveryHarvestableHasAConsumer(): void
    {
        $recipes = $this->recipes();

        $orphans = [];
        foreach ($this->veins() as $vein) {
            $item = (string) $vein['item'];
            if (!str_contains($recipes, sprintf("['slug' => '%s'", $item))) {
                $orphans[] = sprintf('%s (%s)', $item, $vein['zone']);
            }
        }

        self::assertSame(
            [],
            array_values(array_unique($orphans)),
            'Ces filons produisent une matiere que rien ne consomme. Le joueur ramasse, remplit son sac, et '
            . 'decouvre au comptoir que sa recolte ne vaut que son prix de vente.',
        );
    }

    /**
     * Rien n'existe sans source.
     *
     * Le defaut symetrique, et le plus grave des deux : une matiere citee par
     * une recette et introuvable dans le monde rend la recette incraftable, sans
     * que rien ne le dise.
     */
    public function testEveryDeclaredPlantAndFishHasAVein(): void
    {
        $sourced = [];
        foreach ($this->veins() as $vein) {
            $sourced[(string) $vein['item']] = true;
        }

        $unsourced = array_values(array_filter(
            $this->declaredHarvestables(),
            static fn (string $slug): bool => !isset($sourced[$slug]),
        ));

        self::assertSame([], $unsourced, 'Ces plantes ou poissons sont livres sans qu\'aucun filon ne les produise.');
    }

    /**
     * Les cinq plantes mortes ne reviennent pas par la petite porte.
     */
    public function testThePurgedPlantsStayPurged(): void
    {
        $declared = $this->declaredHarvestables();

        foreach (self::PURGED as $slug) {
            self::assertNotContains(
                $slug,
                $declared,
                sprintf('"%s" est de retour : il lui faut alors une source **et** un debouche.', $slug),
            );
        }
    }

    // =====================================================================
    // Le palier haut de la peche
    // =====================================================================

    /**
     * Le poisson-lune et le kraken juvenile se pechent enfin.
     *
     * Tous deux etaient **cuisines sans etre pechables** : deux recettes livrees
     * par ECO-29 dont la matiere n'existait nulle part. C'est le trou de palier
     * haut que la loi 9 relevait chez le pecheur.
     */
    public function testTheTwoTopTierFishAreFinallyCatchable(): void
    {
        $byItem = [];
        foreach ($this->veins() as $vein) {
            $byItem[(string) $vein['item']] = $vein;
        }

        foreach (['fish-moonfish', 'fish-baby-kraken'] as $slug) {
            self::assertArrayHasKey($slug, $byItem, sprintf('"%s" ne se peche nulle part.', $slug));

            [$capacity, $respawn] = self::TIER_FOUR;
            self::assertSame($capacity, $byItem[$slug]['capacity'], sprintf('%s hors profil T4.', $slug));
            self::assertSame($respawn, $byItem[$slug]['respawn_seconds'], sprintf('%s hors profil T4.', $slug));

            self::assertArrayHasKey(
                'requires_skill',
                $byItem[$slug],
                sprintf('"%s" est une prise de palier 4 : elle se gagne sur un savoir (ECO-24c).', $slug),
            );
        }
    }

    /**
     * Le monde compte toujours sept poissons, et tous se pechent.
     *
     * Le compte est epingle a cote de la source : `FishSourceReferenceTest`
     * verifie qu'ils ont un debouche, celui-ci qu'ils ont un filon. Les deux
     * moities de la loi, sur le meme catalogue.
     */
    public function testAllSevenFishAreSourced(): void
    {
        $fished = [];
        foreach ($this->veins() as $vein) {
            if ('fishing' === $vein['profession']) {
                $fished[] = (string) $vein['item'];
            }
        }

        $fished = array_values(array_unique($fished));
        sort($fished);

        self::assertSame([
            'fish-baby-kraken',
            'fish-carp',
            'fish-electric-eel',
            'fish-moonfish',
            'fish-perch',
            'fish-salmon',
            'fish-trout',
        ], $fished);
    }

    // =====================================================================
    // Ce qui absorbe les banales
    // =====================================================================

    /**
     * Les quatre herbes banales sont absorbees par le melange d'epices.
     *
     * ECO-29 avait reporte les epices en attendant ce jalon ; c'est ici que la
     * promesse se solde. Elles ne sont pas mortes — elles ont un filon — elles
     * sont **inutiles**, ce qui est pire : le joueur les ramasse sans savoir
     * qu'il perd son temps.
     */
    public function testTheFourCommonHerbsFeedTheSpiceBlend(): void
    {
        preg_match("/'recipe_spice_blend' => \[(.*?)\n            \],/s", $this->recipes(), $block);

        self::assertNotEmpty($block, 'Le melange d\'epices n\'existe pas.');

        foreach (['plant-dandelion', 'plant-nettle', 'plant-rosemary', 'plant-echinacea'] as $slug) {
            self::assertStringContainsString(
                sprintf("['slug' => '%s'", $slug),
                $block[1],
                sprintf('"%s" n\'entre pas dans le melange.', $slug),
            );
        }
    }

    /**
     * Le melange lui-meme a une demande.
     *
     * Un intermediaire que rien ne consomme deplacerait le probleme d'un cran au
     * lieu de le resoudre : quatre herbes inutiles deviendraient une epice
     * inutile.
     */
    public function testTheSpiceBlendIsItselfConsumed(): void
    {
        self::assertStringContainsString(
            "['slug' => 'crafted-spice-blend'",
            $this->recipes(),
            'Le melange d\'epices ne sert a rien : le probleme a juste change de nom.',
        );
    }

    /**
     * Les six plantes rares entrent dans l'alchimie haute.
     *
     * Elles avaient toutes un filon et aucune recette. Les purger aurait vide
     * six zones d'une exclusivite ; les raccorder etait la seule option qui
     * garde la geographie intacte.
     */
    public function testTheSixRarePlantsFeedHighAlchemy(): void
    {
        $recipes = $this->recipes();

        $orphans = [];
        foreach ([
            'plant-frostcap',
            'plant-ghostshroom',
            'plant-voidfruit',
            'plant-dragonleaf',
            'plant-moonflower',
            'plant-phoenixflower',
        ] as $slug) {
            if (!str_contains($recipes, sprintf("['slug' => '%s'", $slug))) {
                $orphans[] = $slug;
            }
        }

        self::assertSame([], $orphans, 'Ces plantes rares se recoltent sans que rien ne les consomme.');
    }
}
