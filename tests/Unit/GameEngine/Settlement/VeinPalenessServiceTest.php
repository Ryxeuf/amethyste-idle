<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Zone;
use App\Entity\App\ZoneVein;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\VeinPalenessService;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * L'extraction laisse une trace (FOY-11).
 *
 * Quatre proprietes portent le jalon, et deux d'entre elles sont des
 * **interdits** que le socle de monde pose explicitement :
 *
 * 1. La Paleur monte quand on prend plus vite que le filon ne rend.
 * 2. Elle **redescend pendant qu'on joue** — il n'y a aucune jachere a
 *    proteger, donc rien a gagner a s'abstenir collectivement (§3.5).
 * 3. Elle est **bornee** : un filon pali n'est jamais sterile, c'est ce qui le
 *    distingue d'une Etale (§12.1).
 * 4. Elle mesure un **rythme**, pas un cumul : le compteur repart a zero chaque
 *    jour, sans quoi une ruee d'un soir se paierait eternellement.
 */
class VeinPalenessServiceTest extends TestCase
{
    /**
     * @return array{rise_per_pressure: float, daily_recovery: float, max: float, visible_from: float, dulls_purity_from: float}
     */
    private function definition(): array
    {
        return [
            'rise_per_pressure' => 0.08,
            'daily_recovery' => 0.04,
            'max' => 0.60,
            'visible_from' => 0.10,
            'dulls_purity_from' => 0.30,
        ];
    }

    // =====================================================================
    // Le calcul d'un jour, verifiable sans base
    // =====================================================================

    public function testPressureAboveOneDullsTheVein(): void
    {
        // Pression 2 : on prend deux fois ce que le filon rend.
        self::assertEqualsWithDelta(0.08, VeinPalenessService::step(0.0, 2.0, $this->definition()), 0.0001);
    }

    public function testPressureAtOneChangesNothing(): void
    {
        // Exactement le debit soutenu : le filon tient, il ne s'abime pas et ne
        // se refait pas. C'est le regime d'equilibre que le calibrage vise.
        self::assertEqualsWithDelta(0.20, VeinPalenessService::step(0.20, 1.0, $this->definition()), 0.0001);
    }

    /**
     * L'interdit de la jachere : la recuperation court **pendant qu'on joue**.
     * Un filon frequente sous son debit se refait — il n'y a aucune phase a
     * proteger, et donc rien a gagner a demander a un serveur de s'abstenir.
     */
    public function testAVeinBelowItsSustainedRateMendsWhilePeopleStillHarvestIt(): void
    {
        self::assertEqualsWithDelta(0.16, VeinPalenessService::step(0.20, 0.9, $this->definition()), 0.0001);
    }

    public function testRecoveryNeverGoesBelowZero(): void
    {
        self::assertSame(0.0, VeinPalenessService::step(0.02, 0.0, $this->definition()));
    }

    /**
     * Le plancher dur : un filon pali ne devient **jamais** sterile. C'est ce
     * qui le distingue d'une Etale, lieu ancien et permanent.
     */
    public function testPalenessIsCappedNoMatterHowHardTheVeinIsPressed(): void
    {
        $paleness = 0.0;
        for ($day = 0; $day < 100; ++$day) {
            $paleness = VeinPalenessService::step($paleness, 50.0, $this->definition());
        }

        self::assertSame(0.60, $paleness);
        self::assertLessThan(1.0, $paleness, 'Un filon pali n\'est jamais sterile.');
    }

    /**
     * Abimer va plus vite que reparer — sinon la trace n'en serait pas une.
     */
    public function testDamageOutpacesRepair(): void
    {
        $definition = $this->definition();

        self::assertGreaterThan($definition['daily_recovery'], $definition['rise_per_pressure']);
    }

    // =====================================================================
    // Le tick
    // =====================================================================

    public function testTheTickDullsAnOverpressedVeinAndResetsItsCounter(): void
    {
        // Profil T2 : 32 unites de tampon, 28 800 s de repousse => 96 unites/jour.
        $vein = $this->vein('filon-de-cobalt', 32, 28800);
        $vein->setExtractedSinceTick(192); // pression 2

        $report = $this->service([$vein])->tick();

        self::assertEqualsWithDelta(0.08, $vein->getPaleness(), 0.0001);
        self::assertSame(0, $vein->getExtractedSinceTick(), 'Ce qui se mesure est un rythme, pas un cumul.');
        self::assertSame(['processed' => 1, 'dulled' => 1, 'recovered' => 0], $report);
    }

    public function testTheTickMendsAVeinLeftAlone(): void
    {
        $vein = $this->vein('filon-de-cobalt', 32, 28800);
        $vein->setPaleness(0.30);

        $report = $this->service([$vein])->tick();

        self::assertEqualsWithDelta(0.26, $vein->getPaleness(), 0.0001);
        self::assertSame(['processed' => 1, 'dulled' => 0, 'recovered' => 1], $report);
    }

    /**
     * Un filon dont la definition a disparu de la config de zone se refait tout
     * seul plutot que de rester fige : un residu doit finir par s'effacer.
     */
    public function testAVeinWithoutADeclaredDefinitionSimplyMends(): void
    {
        $vein = new ZoneVein($this->zone([]), 'filon-oublie', 0);
        $vein->setPaleness(0.20);
        $vein->setExtractedSinceTick(999);

        $this->service([$vein])->tick();

        self::assertEqualsWithDelta(0.16, $vein->getPaleness(), 0.0001);
    }

    // =====================================================================
    // Fixtures
    // =====================================================================

    private function zone(array $resources): Zone
    {
        return (new Zone())->setSlug('crete')->setName('Crête')->setGatherConfig(
            [] === $resources ? null : ['resources' => $resources],
        );
    }

    private function vein(string $slug, int $capacity, int $respawn): ZoneVein
    {
        $zone = $this->zone([[
            'slug' => $slug,
            'item' => 'ore-cobalt',
            'profession' => 'mining',
            'capacity' => $capacity,
            'respawn_seconds' => $respawn,
        ]]);

        return new ZoneVein($zone, $slug, $capacity);
    }

    /**
     * @param list<ZoneVein> $veins
     */
    private function service(array $veins): VeinPalenessService
    {
        $repository = $this->createMock(ZoneVeinRepository::class);
        $repository->method('findAll')->willReturn($veins);

        $loader = $this->createMock(SettlementDefinitionLoader::class);
        $loader->method('load')->willReturn(['paleness' => $this->definition()]);

        return new VeinPalenessService(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $loader,
        );
    }
}
