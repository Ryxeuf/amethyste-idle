<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\App\Player;
use App\Enum\Purity;
use App\GameEngine\Economy\PurityDefinitionException;
use App\GameEngine\Economy\PurityDefinitionLoader;
use App\GameEngine\Economy\PurityDrawer;
use App\GameEngine\Economy\PurityScope;
use App\GameEngine\Progression\ActionYieldResolver;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\Repository\WeeklyOutcropRepository;
use App\Repository\ZoneVeinRepository;
use PHPUnit\Framework\TestCase;

/**
 * D'ou vient la bande d'un lot (ECO-22).
 *
 * Trois proprietes portent le jalon, et l'ordre entre elles est la vraie regle :
 * **le plafond de vitalite prime sur le savoir**. Un recolteur chevronne qui
 * continuerait de tirer du parfait dans un filon a sec annulerait le signal que
 * ZON-37 existe pour rendre lisible — et la couche de rarete redeviendrait
 * inerte, exactement comme avant le recalibrage.
 */
class PurityDrawerTest extends TestCase
{
    public function testAThrivingVeinCanYieldEveryBand(): void
    {
        self::assertSame(Purity::Parfait, $this->drawer()->ceiling(100, 100));
    }

    public function testAPressedVeinStopsYieldingFlawlessLongBeforeItRunsDry(): void
    {
        $drawer = $this->drawer();

        self::assertSame(Purity::Pur, $drawer->ceiling(50, 100));
        self::assertSame(Purity::Clair, $drawer->ceiling(20, 100));
        self::assertSame(Purity::Trouble, $drawer->ceiling(5, 100));
    }

    /**
     * Un filon a sec rend encore — une recolte n'echoue jamais (ZON-37) — mais
     * il ne rend plus que du trouble.
     */
    public function testAnExhaustedVeinStillYieldsABand(): void
    {
        self::assertSame(Purity::Trouble, $this->drawer()->ceiling(0, 100));
    }

    /**
     * La propriete centrale du jalon. Le plafond est applique **apres** le
     * savoir, donc il l'ecrase.
     */
    public function testTheVitalityCeilingOverridesTheGathererSkill(): void
    {
        $weights = $this->drawer()->weightsFor(Purity::Clair, 100);

        self::assertSame(0, $weights[Purity::Pur->value]);
        self::assertSame(0, $weights[Purity::Parfait->value]);
        self::assertGreaterThan(0, $weights[Purity::Clair->value]);
    }

    /**
     * Le savoir deplace les poids d'un cran a la fois. Un raccourci du trouble
     * vers le parfait ferait du niveau de recolte un **achat de rarete**, ce que
     * le socle de monde ecarte explicitement.
     */
    public function testSkillNeverBuysFlawlessDirectly(): void
    {
        $drawer = $this->drawer();

        $novice = $drawer->weightsFor(Purity::Parfait, 0);
        $veteran = $drawer->weightsFor(Purity::Parfait, 100);

        self::assertSame($novice[Purity::Parfait->value], $veteran[Purity::Parfait->value]);
        self::assertGreaterThan($novice[Purity::Pur->value], $veteran[Purity::Pur->value]);
        self::assertLessThan($novice[Purity::Trouble->value], $veteran[Purity::Trouble->value]);
    }

    public function testSkillShiftIsCapped(): void
    {
        $drawer = $this->drawer();

        self::assertSame(
            $drawer->weightsFor(Purity::Parfait, 25),
            $drawer->weightsFor(Purity::Parfait, 500),
        );
    }

    /**
     * Un poids total nul apres plafonnement laisserait le tirage sans issue.
     * La bande plafond reste alors la seule possible — rendre `null` obligerait
     * chaque appelant a gerer un cas qui ne veut rien dire.
     */
    public function testTheCeilingBandIsAlwaysPossible(): void
    {
        $weights = $this->drawerWithWeights(['trouble' => 0, 'clair' => 0, 'pur' => 0, 'parfait' => 1])
            ->weightsFor(Purity::Trouble, 0);

        self::assertSame(1, array_sum($weights));
        self::assertSame(1, $weights[Purity::Trouble->value]);
    }

    public function testAMaterialOutsideTheScopeGetsNoBand(): void
    {
        self::assertNull($this->drawer()->draw(new Player(), 'herb-sage', 100, 100));
    }

    /**
     * Le tirage reel, repete : il doit **toujours** rester sous le plafond. Une
     * seule sortie au-dessus suffirait a rendre la vitalite decorative.
     */
    public function testEveryDrawStaysUnderTheCeiling(): void
    {
        $drawer = $this->drawer();
        $player = new Player();

        for ($i = 0; $i < 200; ++$i) {
            $band = $drawer->draw($player, 'ore-copper', 20, 100);

            self::assertNotNull($band);
            self::assertTrue(Purity::Clair->isAtLeast($band), sprintf('Bande "%s" au-dessus du plafond clair.', $band->value));
        }
    }

    /**
     * Un plafond qui ne descend pas laisserait le parfait accessible a un filon
     * a sec, ce qui annulerait le signal de vitalite.
     */
    public function testCeilingsMustDescend(): void
    {
        $this->expectException(PurityDefinitionException::class);
        $this->expectExceptionMessageMatches('/must descend/');

        (new PurityDefinitionLoader('/project'))->normalize($this->rawWith([
            ['at_least' => 0.3, 'band' => 'pur'],
            ['at_least' => 0.6, 'band' => 'parfait'],
        ]));
    }

    /**
     * Un plafond qui ne descend pas jusqu'a zero laisserait un filon epuise sans
     * bande du tout — et la recolte rendrait des lots sans purete dans un
     * perimetre qui en exige une.
     */
    public function testCeilingsMustReachZero(): void
    {
        $this->expectException(PurityDefinitionException::class);
        $this->expectExceptionMessageMatches('/must end at 0/');

        (new PurityDefinitionLoader('/project'))->normalize($this->rawWith([
            ['at_least' => 0.5, 'band' => 'pur'],
        ]));
    }

    /**
     * Contrat sur le fichier livre : le parfait reste rare **par construction**,
     * c'est ce qui rendra la materia rare sans table de drop.
     */
    public function testTheShippedTableKeepsFlawlessRare(): void
    {
        $draw = (new PurityDefinitionLoader(\dirname(__DIR__, 4)))->load()['draw'];

        $total = array_sum($draw['base_weights']);
        self::assertGreaterThan(0, $total);
        self::assertLessThan(
            $total * 0.05,
            $draw['base_weights'][Purity::Parfait->value],
            'Le parfait doit rester sous 5 % du tirage de base : la rarete de la materia en depend.',
        );
    }

    /**
     * @param list<array{at_least: float, band: string}> $ceilings
     *
     * @return array<string, mixed>
     */
    private function rawWith(array $ceilings): array
    {
        return [
            'scope' => ['slug_prefixes' => ['ore-']],
            'draw' => [
                'base_weights' => ['trouble' => 60, 'clair' => 30, 'pur' => 9, 'parfait' => 1],
                'vitality_ceilings' => $ceilings,
                'skill_weight_per_point' => 1,
                'skill_weight_cap' => 25,
            ],
        ];
    }

    private function drawer(): PurityDrawer
    {
        return $this->drawerWithWeights(['trouble' => 60, 'clair' => 30, 'pur' => 9, 'parfait' => 1]);
    }

    /**
     * @param array<string, int> $weights
     */
    private function drawerWithWeights(array $weights): PurityDrawer
    {
        $loader = $this->createMock(PurityDefinitionLoader::class);
        $loader->method('load')->willReturn([
            'scope' => ['slug_prefixes' => ['ore-'], 'excluded_slugs' => [], 'included_slugs' => []],
            'draw' => [
                'base_weights' => $weights,
                'vitality_ceilings' => [
                    ['at_least' => 0.66, 'band' => Purity::Parfait],
                    ['at_least' => 0.33, 'band' => Purity::Pur],
                    ['at_least' => 0.10, 'band' => Purity::Clair],
                    ['at_least' => 0.0, 'band' => Purity::Trouble],
                ],
                'skill_weight_per_point' => 1,
                'skill_weight_cap' => 25,
            ],
        ]);

        return new PurityDrawer(new PurityScope($loader), $loader, new ActionYieldResolver(), $this->createMock(WeeklyOutcropRepository::class), ...$this->palenessStubs());
    }

    /**
     * FOY-11 : ces tests portent sur l'affleurement et le tirage, pas sur la
     * Paleur. Un depot sans filon pali laisse la seconde borne inactive — l'etat
     * normal d'un monde qu'on n'a pas encore ereinte.
     *
     * @return array{0: \App\Repository\ZoneVeinRepository, 1: \App\GameEngine\Settlement\SettlementDefinitionLoader}
     */
    private function palenessStubs(): array
    {
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

        return [$veinRepository, $settlementLoader];
    }
}
