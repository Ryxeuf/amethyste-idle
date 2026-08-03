<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Region;
use App\Enum\ReputationTier;
use App\GameEngine\Reputation\FactionTensionDefinitionException;
use App\GameEngine\Reputation\ShadowsMarketCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Les trois garde-fous du receleur, et ce que le loader refuse (FAC-06).
 *
 * Le canon les nomme : la coupe toujours superieure a la taxe max de cite
 * (sans quoi le marche gris renverse le HV), le plafond de lots (la Confrerie
 * n'aime pas les gros volumes), l'acces au palier Ami (elle ne travaille pas
 * avec des inconnus). Les trois vivent en config et un defaut ne se verrait
 * pas a l'execution — le loader le transforme en erreur.
 */
class ShadowsMarketCatalogTest extends TestCase
{
    private function catalog(): ShadowsMarketCatalog
    {
        return new ShadowsMarketCatalog(\dirname(__DIR__, 4));
    }

    public function testTheShippedBlockCarriesTheThreeGuards(): void
    {
        $catalog = $this->catalog();

        self::assertGreaterThan(
            Region::MAX_TAX_RATE_PERCENT,
            $catalog->fenceCutPercent(),
            'La coupe du receleur doit rester strictement au-dessus de la taxe max de cite : en gils, le receleur perd toujours face au HV.',
        );
        self::assertGreaterThan(0, $catalog->weeklyLotCap());
        self::assertSame(ReputationTier::Ami, $catalog->fenceRequiredTier());
        self::assertGreaterThan(0, $catalog->nightExplorationsThreshold());
        self::assertGreaterThan(0, $catalog->rumorPriceGils());
    }

    /**
     * FAC-07 : le bloc contrefacon livre — la fourchette du compteur, le prix
     * du faux geste, et les trois paliers de l'echelle (l'œil a Honore, le
     * desamorcage et la main a Revere, GAME_WORLD § 12.4).
     */
    public function testTheShippedCounterfeitBlockMatchesTheCanon(): void
    {
        $catalog = $this->catalog();

        self::assertSame(8, $catalog->counterfeitChargesMin(), 'La contrefacon marche ~neuf fois : la fourchette canonique est 8-12.');
        self::assertSame(12, $catalog->counterfeitChargesMax());
        self::assertGreaterThanOrEqual(1, $catalog->counterfeitLootChancePercent());
        self::assertLessThanOrEqual(100, $catalog->counterfeitBacklashPercent());
        self::assertGreaterThan(0, $catalog->counterfeitDefuseEssence());
        self::assertSame(ReputationTier::Honore, $catalog->counterfeitEyeTier());
        self::assertSame(ReputationTier::Revere, $catalog->counterfeitDefuseTier());
        self::assertSame(ReputationTier::Revere, $catalog->counterfeitForgeTier());
    }

    /**
     * La recette de la main du faussaire existe dans les donnees : un slug
     * fantome ferait un gate qui ne garde rien, en silence.
     */
    public function testTheForgeRecipeIsDeclared(): void
    {
        $recipes = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/RecipeFixtures.php');

        self::assertStringContainsString(
            sprintf("'slug' => '%s'", $this->catalog()->counterfeitForgeRecipeSlug()),
            $recipes,
            'Le slug de la main du faussaire ne nomme aucune recette declaree.',
        );
    }

    /**
     * Chaque guichet du receleur est un PNJ declare : une coquille ferait un
     * marche gris sans porte, en silence.
     */
    public function testEveryCounterIsADeclaredZonePnj(): void
    {
        $world = (string) file_get_contents(\dirname(__DIR__, 4) . '/config/game/zones/world_1.yaml');

        foreach ($this->catalog()->counterPnjSlugs() as $slug) {
            self::assertStringContainsString(
                'slug: ' . $slug,
                $world,
                sprintf('Le guichet "%s" du receleur n\'est declare dans aucune zone.', $slug),
            );
        }

        self::assertTrue($this->catalog()->isCounter('village-veilleur-tancrede'));
        self::assertFalse($this->catalog()->isCounter('mines-comptoir-de-la-fonderie'));
        self::assertFalse($this->catalog()->isCounter(null));
    }

    /**
     * La couverture de Tancrede est nocturne : le YAML pose enfin l'horaire
     * que son commentaire promettait — sans lui, le guichet du receleur
     * serait ouvert en plein jour.
     */
    public function testTancredeKeepsNightHours(): void
    {
        $world = (string) file_get_contents(\dirname(__DIR__, 4) . '/config/game/zones/world_1.yaml');

        self::assertSame(1, preg_match(
            '/slug: village-veilleur-tancrede.*?opens_at: 20\n.*?closes_at: 6\n/s',
            $world,
        ), 'Tancrede est un veilleur de nuit : son echoppe ouvre 20h-6h, jamais en continu.');
    }

    public function testACutAtOrBelowTheCityTaxCeilingIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['ruelles' => [
            'approach' => ['night_explorations' => 8],
            'fence' => ['cut_percent' => Region::MAX_TAX_RATE_PERCENT, 'weekly_lot_cap' => 5, 'required_tier' => 'ami', 'counter_pnj_slugs' => ['x']],
            'rumors' => ['price_gils' => 40],
        ]]);
    }

    public function testAZeroLotCapIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['ruelles' => [
            'approach' => ['night_explorations' => 8],
            'fence' => ['cut_percent' => 15, 'weekly_lot_cap' => 0, 'required_tier' => 'ami', 'counter_pnj_slugs' => ['x']],
            'rumors' => ['price_gils' => 40],
        ]]);
    }

    public function testAnUnknownTierIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['ruelles' => [
            'approach' => ['night_explorations' => 8],
            'fence' => ['cut_percent' => 15, 'weekly_lot_cap' => 5, 'required_tier' => 'complice', 'counter_pnj_slugs' => ['x']],
            'rumors' => ['price_gils' => 40],
        ]]);
    }

    public function testAMissingApproachThresholdIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['ruelles' => [
            'fence' => ['cut_percent' => 15, 'weekly_lot_cap' => 5, 'required_tier' => 'ami', 'counter_pnj_slugs' => ['x']],
            'rumors' => ['price_gils' => 40],
        ]]);
    }

    /**
     * FAC-07 : une contrefacon qui trahirait au premier geste serait un
     * piege, pas une trahison — le canon veut qu'elle MARCHE, longtemps.
     */
    public function testAOneShotCounterfeitIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['ruelles' => $this->validRuelles(['charges_min' => 1])]);
    }

    public function testAChargesCeilingBelowTheFloorIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['ruelles' => $this->validRuelles(['charges_max' => 5])]);
    }

    public function testAMissingCounterfeitBlockIsRefused(): void
    {
        $ruelles = $this->validRuelles([]);
        unset($ruelles['counterfeit']);

        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['ruelles' => $ruelles]);
    }

    /**
     * Un bloc ruelles valide, dont le test mute une seule cle : le refus
     * observe vient bien de la cle mutee, jamais d'un voisin.
     *
     * @param array<string, mixed> $counterfeitOverride
     *
     * @return array<string, mixed>
     */
    private function validRuelles(array $counterfeitOverride): array
    {
        return [
            'approach' => ['night_explorations' => 8],
            'fence' => ['cut_percent' => 15, 'weekly_lot_cap' => 5, 'required_tier' => 'ami', 'counter_pnj_slugs' => ['x']],
            'rumors' => ['price_gils' => 40],
            'counterfeit' => $counterfeitOverride + [
                'charges_min' => 8,
                'charges_max' => 12,
                'loot_chance_percent' => 4,
                'backlash_percent_max_life' => 25,
                'defuse_essence' => 3,
                'eye_tier' => 'honore',
                'defuse_tier' => 'revere',
                'forge_tier' => 'revere',
                'forge_recipe_slug' => 'recipe-forgers-hand',
            ],
        ];
    }
}
