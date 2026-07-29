<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\App\Zone;
use App\Enum\Purity;
use App\GameEngine\Economy\PurityDefinitionLoader;
use App\GameEngine\Economy\PurityDrawer;
use App\GameEngine\Economy\PurityScope;
use App\GameEngine\Progression\ActionYieldResolver;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\World\GameTimeService;
use App\GameEngine\Zone\ZoneDefinitionLoader;
use App\Repository\WeeklyOutcropRepository;
use App\Repository\ZoneVeinRepository;
use PHPUnit\Framework\TestCase;

/**
 * Une zone, une facon dont le temps s'est depose (ZON-32).
 *
 * Les signatures de GAME_ZONES § 2 etaient **ecrites dans un document et
 * absentes du jeu** : deux zones se recoltaient a l'identique. Ce fichier
 * verrouille ce que la table declarative change, et surtout ce qu'elle ne doit
 * jamais changer.
 *
 * 1. **Les Mines et la Crete sont le pendant l'une de l'autre** — beaucoup et
 *    trouble contre peu et pur. Les deux zones s'expliquent l'une par l'autre,
 *    et si elles tiraient pareil, aucune des deux ne dirait rien.
 * 2. **Le Marais tire haut la nuit.** C'est l'information exclusive type du
 *    prospecteur : savoir *quand* frapper.
 * 3. **Le signe deplace les poids, jamais le plafond.** Sinon la Crete rendrait
 *    du parfait sur un filon ereinte, et la vitalite, la Paleur et
 *    l'Affleurement seraient effaces d'un seul geste.
 * 4. **Le parfait ne bouge jamais.** Il ne s'achete ni a l'experience, ni a la
 *    geographie.
 */
class AmethystSignatureTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 4);
    }

    // =====================================================================
    // Ce que la table dit
    // =====================================================================

    /**
     * Les Mines et la Crete se lisent l'une contre l'autre.
     */
    public function testTheMinesAndTheRidgeAreEachOthersOpposite(): void
    {
        $signatures = (new PurityDefinitionLoader($this->root()))->load()['signatures'];

        self::assertLessThan(0, $signatures['mines-profondes']['weight_shift']);
        self::assertGreaterThan(0, $signatures['crete-de-ventombre']['weight_shift']);
    }

    /**
     * La Foret est la reference, et elle est inscrite **explicitement**.
     *
     * Une absence ne distingue pas « neutre par nature » de « pas encore
     * decrit » ; l'ecrire a zero tranche.
     */
    public function testTheForestIsTheDeclaredReference(): void
    {
        $signatures = (new PurityDefinitionLoader($this->root()))->load()['signatures'];

        self::assertArrayHasKey('foret-des-murmures', $signatures);
        self::assertSame(0, $signatures['foret-des-murmures']['weight_shift']);
    }

    /**
     * Le Marais est la seule zone erratique du monde livre : elle depose mal le
     * jour et bien la nuit.
     */
    public function testTheMarshIsTheOnlyErraticZone(): void
    {
        $erratic = [];
        foreach ((new PurityDefinitionLoader($this->root()))->load()['signatures'] as $slug => $signature) {
            if ($signature['weight_shift'] !== $signature['night_weight_shift']) {
                $erratic[] = $slug;
            }
        }

        self::assertSame(['marais-brumeux'], $erratic);
    }

    /**
     * Toute zone signee existe reellement.
     *
     * Une signature pour une zone disparue est un vestige, et un vestige non
     * signale finit par etre pris pour une intention.
     */
    public function testEverySignedZoneExists(): void
    {
        $loader = new ZoneDefinitionLoader($this->root());
        $slugs = array_column($loader->loadFile($loader->defaultFile())['zones'], 'slug');

        $ghosts = array_values(array_diff(
            array_keys((new PurityDefinitionLoader($this->root()))->load()['signatures']),
            $slugs,
        ));

        self::assertSame([], $ghosts);
    }

    /**
     * **Le Fanal et les Jardins ne rendent aucune amethyste** (canon § 2.1).
     *
     * Le Cristal sous la Voute est un **cœur**, pas un gisement : on vit a cote
     * de la plus grande amethyste du monde et on n'en ramasse pas un eclat. Le
     * verrou n'est pas une signature a zero — ce serait dire « peu » — mais
     * l'absence de tout filon du perimetre de purete.
     */
    public function testNothingOfTheVaultEverYieldsAmethyst(): void
    {
        $loader = new ZoneDefinitionLoader($this->root());
        $scope = new PurityScope(new PurityDefinitionLoader($this->root()));

        foreach ($loader->loadFile($loader->defaultFile())['zones'] as $zone) {
            if (!\in_array($zone['slug'], ['village-de-lumiere', 'quartier-des-jardins'], true)) {
                continue;
            }

            foreach ($zone['gather'] ?? [] as $resource) {
                self::assertFalse(
                    $scope->coversSlug($resource['item']),
                    sprintf('"%s" rend de l\'amethyste sous la Voute, ou le temps ne se depose pas.', $zone['slug']),
                );
            }
        }
    }

    // =====================================================================
    // Ce que la table fait au tirage
    // =====================================================================

    public function testTheRidgePullsTheDrawUpwards(): void
    {
        $weights = $this->drawer()->weightsFor(Purity::Parfait, 0, $this->zone('crete-de-ventombre'));

        self::assertSame(['trouble' => 35, 'clair' => 30, 'pur' => 34, 'parfait' => 1], $weights);
    }

    public function testTheMinesPushTheDrawDownwards(): void
    {
        $weights = $this->drawer()->weightsFor(Purity::Parfait, 0, $this->zone('mines-profondes'));

        self::assertSame(['trouble' => 80, 'clair' => 19, 'pur' => 0, 'parfait' => 1], $weights);
    }

    /**
     * Le Marais nocturne est le premier endroit ou un joueur d'Acte II voit du
     * **Pur**. Le jour, le meme filon n'en rend aucun.
     */
    public function testTheMarshOnlyGivesPureAtNight(): void
    {
        $marsh = $this->zone('marais-brumeux');

        $day = $this->drawer()->weightsFor(Purity::Parfait, 0, $marsh);
        $night = $this->drawer(true)->weightsFor(Purity::Parfait, 0, $marsh);

        self::assertSame(0, $day['pur']);
        self::assertGreaterThan(0, $night['pur']);
        self::assertGreaterThan($day['clair'] + $day['pur'], $night['clair'] + $night['pur']);
    }

    /**
     * Une zone absente de la table tire comme la reference.
     *
     * Livrer une zone neuve ne doit pas exiger d'en decrire la geologie avant
     * qu'on sache ce qu'elle sera.
     */
    public function testAnUnsignedZoneDrawsLikeTheReference(): void
    {
        $drawer = $this->drawer();

        self::assertSame(
            $drawer->weightsFor(Purity::Parfait, 0, $this->zone('foret-des-murmures')),
            $drawer->weightsFor(Purity::Parfait, 0, $this->zone('une-zone-sans-signature')),
        );
    }

    /**
     * **Le parfait ne bouge jamais**, dans un sens comme dans l'autre.
     *
     * Ni le savoir ni la geographie n'y donnent acces : c'est ce qui le garde
     * rare sans table de drop (GAME_WORLD § 5.4).
     */
    public function testNoSignatureEverTouchesThePerfect(): void
    {
        $drawer = $this->drawer();

        foreach (['crete-de-ventombre', 'mines-profondes', 'dunes-d-ambre', 'cite-ensevelie'] as $slug) {
            self::assertSame(1, $drawer->weightsFor(Purity::Parfait, 0, $this->zone($slug))['parfait']);
        }
    }

    /**
     * **Le signe ne perce jamais le plafond.**
     *
     * Un filon ereinte de la Crete rend du trouble comme partout ailleurs : la
     * geologie decrit une distribution, la vitalite pose une borne, et c'est la
     * borne qui gagne.
     */
    public function testTheRichestSignatureStillObeysAnExhaustedVein(): void
    {
        $weights = $this->drawer()->weightsFor(Purity::Trouble, 0, $this->zone('crete-de-ventombre'));

        self::assertSame(0, $weights['clair']);
        self::assertSame(0, $weights['pur']);
        self::assertSame(0, $weights['parfait']);
        self::assertGreaterThan(0, $weights['trouble']);
    }

    // =====================================================================
    // Fixtures
    // =====================================================================

    private function zone(string $slug): Zone
    {
        return (new Zone())->setSlug($slug)->setName($slug);
    }

    private function drawer(bool $night = false): PurityDrawer
    {
        $loader = new PurityDefinitionLoader($this->root());

        $veinRepository = $this->createMock(ZoneVeinRepository::class);
        $veinRepository->method('findOneByZoneAndSlug')->willReturn(null);

        $settlementLoader = $this->createMock(SettlementDefinitionLoader::class);
        $settlementLoader->method('load')->willReturn(['paleness' => [
            'rise_per_pressure' => 0.08,
            'daily_recovery' => 0.04,
            'max' => 0.6,
            'visible_from' => 0.1,
            'dulls_purity_from' => 0.3,
        ]]);

        $gameTime = $this->createMock(GameTimeService::class);
        $gameTime->method('isNight')->willReturn($night);

        return new PurityDrawer(
            new PurityScope($loader),
            $loader,
            new ActionYieldResolver(),
            $this->createMock(WeeklyOutcropRepository::class),
            $veinRepository,
            $settlementLoader,
            $gameTime,
        );
    }
}
