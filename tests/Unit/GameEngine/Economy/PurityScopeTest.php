<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\Game\Item;
use App\GameEngine\Economy\PurityDefinitionException;
use App\GameEngine\Economy\PurityDefinitionLoader;
use App\GameEngine\Economy\PurityScope;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Ce qui porte une bande de purete, et ce qui reste fongible (ECO-21).
 *
 * Le perimetre est **le** garde-fou du jalon. Star Wars Galaxies a donne des
 * statistiques a toutes ses ressources et a transforme son artisanat en
 * tableur ; la loi la plus importante ici n'est donc pas « l'amethyste porte une
 * bande » mais « **les herbes n'en portent pas** ». Un plancher T1 qui demande a
 * un debutant de comparer des lots avant sa premiere epee serait un mur.
 */
class PurityScopeTest extends TestCase
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function slugProvider(): array
    {
        return [
            'minerai de base' => ['ore-copper', true],
            'minerai de haut palier' => ['ore-mithril', true],
            'gemme' => ['ore-ruby', true],
            'cristal d\'amethyste' => ['ore-amethyst-crystal', true],
            'herbe' => ['herb-sage', false],
            'poisson' => ['fish-trout', false],
            'cuir' => ['leather-wolf', false],
            'bois' => ['wood-oak', false],
            'potion' => ['healing-potion-small', false],
        ];
    }

    #[DataProvider('slugProvider')]
    public function testTheScopeCoversTheCrystalLineAndNothingElse(string $slug, bool $covered): void
    {
        self::assertSame($covered, $this->scope()->coversSlug($slug));
    }

    public function testAnItemIsAskedThroughItsSlug(): void
    {
        $ore = new Item();
        $ore->setSlug('ore-cobalt');

        $herb = new Item();
        $herb->setSlug('herb-sage');

        self::assertTrue($this->scope()->coversItem($ore));
        self::assertFalse($this->scope()->coversItem($herb));
    }

    /**
     * Demander la bande d'une botte d'herbes est une question legitime dont la
     * reponse est « elle n'en a pas » — pas une exception.
     */
    public function testTheAbsenceOfAnItemIsNotAnError(): void
    {
        self::assertFalse($this->scope()->coversItem(null));
    }

    public function testAnExcludedSlugLosesItsBandDespiteThePrefix(): void
    {
        $scope = $this->scopeWith(['slug_prefixes' => ['ore-'], 'excluded_slugs' => ['ore-copper']]);

        self::assertFalse($scope->coversSlug('ore-copper'));
        self::assertTrue($scope->coversSlug('ore-tin'));
    }

    public function testAnIncludedSlugGetsABandWithoutThePrefix(): void
    {
        $scope = $this->scopeWith(['slug_prefixes' => ['ore-'], 'included_slugs' => ['gemme-des-vallons']]);

        self::assertTrue($scope->coversSlug('gemme-des-vallons'));
    }

    /**
     * Un perimetre vide n'est pas un perimetre etroit : c'est une purete qui ne
     * s'applique nulle part, et rien ne le dirait. `Recipe.quality` resterait
     * endormi exactement comme avant le jalon.
     */
    public function testAnEmptyScopeIsRefusedAtLoad(): void
    {
        $this->expectException(PurityDefinitionException::class);
        $this->expectExceptionMessageMatches('/scope is empty/');

        (new PurityDefinitionLoader('/project'))->normalize(['scope' => ['slug_prefixes' => []]]);
    }

    /**
     * Ecrire une matiere des deux cotes est une contradiction, et la resoudre
     * silencieusement ferait dependre le perimetre de l'ordre du code.
     */
    public function testASlugCannotBeBothIncludedAndExcluded(): void
    {
        $this->expectException(PurityDefinitionException::class);
        $this->expectExceptionMessageMatches('/both included and excluded/');

        (new PurityDefinitionLoader('/project'))->normalize([
            'scope' => [
                'slug_prefixes' => ['ore-'],
                'included_slugs' => ['ore-copper'],
                'excluded_slugs' => ['ore-copper'],
            ],
        ]);
    }

    /**
     * Contrat sur le fichier livre : le perimetre couvre la ligne du cristal et
     * laisse fongible tout ce qui nourrit le plancher T1.
     */
    public function testTheShippedScopeKeepsTheBeginnerMaterialsFungible(): void
    {
        $scope = new PurityScope(new PurityDefinitionLoader(\dirname(__DIR__, 4)));

        self::assertTrue($scope->coversSlug('ore-copper'));
        self::assertFalse($scope->coversSlug('herb-sage'));
        self::assertFalse($scope->coversSlug('leather-wolf'));
    }

    private function scope(): PurityScope
    {
        return $this->scopeWith(['slug_prefixes' => ['ore-']]);
    }

    /**
     * @param array<string, list<string>> $scope
     */
    private function scopeWith(array $scope): PurityScope
    {
        $loader = $this->createMock(PurityDefinitionLoader::class);
        $loader->method('load')->willReturn([
            'scope' => [
                'slug_prefixes' => $scope['slug_prefixes'] ?? [],
                'excluded_slugs' => $scope['excluded_slugs'] ?? [],
                'included_slugs' => $scope['included_slugs'] ?? [],
            ],
        ]);

        return new PurityScope($loader);
    }
}
