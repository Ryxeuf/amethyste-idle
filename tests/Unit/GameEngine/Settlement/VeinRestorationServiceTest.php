<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use App\Entity\App\VeinRestoration;
use App\Entity\App\Zone;
use App\Entity\App\ZoneVein;
use App\Enum\GuildRank;
use App\GameEngine\Codex\WorldFactService;
use App\GameEngine\Guild\GuildSpendingAuthority;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\VeinPalenessService;
use App\GameEngine\Settlement\VeinRestorationException;
use App\GameEngine\Settlement\VeinRestorationService;
use App\Repository\VeinRestorationRepository;
use App\Repository\ZoneVeinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * La sanction devient une depense politique (FOY-12).
 *
 * Le jalon tient sur quatre proprietes, et trois d'entre elles sont des
 * **interdits** :
 *
 * 1. Le cout suit la Paleur accumulee — un filon deux fois plus abime coute
 *    deux fois plus cher, sans palier qui brouillerait la lecture.
 * 2. **On n'achete pas un monde propre** : un chantier complet ne ramene jamais
 *    un filon a l'intact, il accelere seulement sa guerison.
 * 3. **Payer n'autorise pas a continuer** : le bonus ne mord pas sur un filon
 *    qu'on presse encore.
 * 4. **Un filon ne porte jamais deux chantiers** : sans cette garde, le second
 *    paiement n'acheterait rien.
 */
class VeinRestorationServiceTest extends TestCase
{
    /**
     * @return array{cost_per_point: int, duration_days: int, daily_bonus: float, opens_from: float}
     */
    private function definition(): array
    {
        return [
            'cost_per_point' => 90,
            'duration_days' => 5,
            'daily_bonus' => 0.04,
            'opens_from' => 0.10,
        ];
    }

    /**
     * @return array{rise_per_pressure: float, daily_recovery: float, max: float, visible_from: float, dulls_purity_from: float}
     */
    private function paleness(): array
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
    // Le cout, verifiable sans base
    // =====================================================================

    public function testCostFollowsTheAccumulatedPaleness(): void
    {
        self::assertSame(900, VeinRestorationService::costFor(0.10, $this->definition()));
        self::assertSame(1800, VeinRestorationService::costFor(0.20, $this->definition()));
        self::assertSame(5400, VeinRestorationService::costFor(0.60, $this->definition()));
    }

    /**
     * Pas de palier : le prix est lineaire, donc lisible. Un joueur doit pouvoir
     * predire ce que lui coutera d'attendre un jour de plus.
     */
    public function testCostIsLinearInTheDamage(): void
    {
        $definition = $this->definition();
        $half = VeinRestorationService::costFor(0.30, $definition);
        $full = VeinRestorationService::costFor(0.60, $definition);

        self::assertSame($full, $half * 2);
    }

    // =====================================================================
    // L'effet : etale, jamais instantane
    // =====================================================================

    /**
     * **On n'achete pas un monde propre.** Cinq jours de chantier sur un filon
     * au plafond le laissent encore nettement pali : le chantier double la
     * guerison, il ne la remplace pas.
     */
    public function testAFullyPaidWorksNeverBuysAnIntactVein(): void
    {
        $paleness = 0.60;
        for ($day = 0; $day < $this->definition()['duration_days']; ++$day) {
            $paleness = VeinPalenessService::step($paleness, 0.5, $this->paleness(), $this->definition()['daily_bonus']);
        }

        // 5 jours x (0,04 naturel + 0,04 paye) = 0,40 retires sur 0,60.
        self::assertEqualsWithDelta(0.20, $paleness, 0.0001);
        self::assertGreaterThan(0.0, $paleness, 'Un chantier accompagne la guerison, il ne l\'achete pas.');
    }

    public function testTheWorksExactlyDoubleTheNaturalRecovery(): void
    {
        $without = VeinPalenessService::step(0.50, 0.5, $this->paleness());
        $with = VeinPalenessService::step(0.50, 0.5, $this->paleness(), $this->definition()['daily_bonus']);

        self::assertEqualsWithDelta(0.46, $without, 0.0001);
        self::assertEqualsWithDelta(0.42, $with, 0.0001);
    }

    /**
     * **Payer n'autorise pas a continuer.** Un chantier ouvert sur un filon
     * qu'on presse encore ne repare rien : sinon la Paleur cesserait d'etre une
     * contrainte pour devenir une facture qu'une guilde riche acquitte en
     * surexploitant en continu.
     */
    public function testPayingDoesNotOffsetAnOngoingOverextraction(): void
    {
        $pressed = VeinPalenessService::step(0.20, 2.0, $this->paleness(), $this->definition()['daily_bonus']);

        self::assertEqualsWithDelta(0.28, $pressed, 0.0001);
    }

    /**
     * Au debit soutenu exact, la guerison naturelle est nulle — seul le chantier
     * paye fait reculer la trace, et par son seul debit.
     */
    public function testAtEquilibriumOnlyThePaidWorksMend(): void
    {
        self::assertEqualsWithDelta(0.20, VeinPalenessService::step(0.20, 1.0, $this->paleness()), 0.0001);
        self::assertEqualsWithDelta(0.16, VeinPalenessService::step(0.20, 1.0, $this->paleness(), 0.04), 0.0001);
    }

    // =====================================================================
    // L'ouverture d'un chantier
    // =====================================================================

    public function testOpeningDebitsTheTreasuryAndDatesTheWorks(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $vein = (new ZoneVein($zone, 'filon-de-cobalt', 32))->setPaleness(0.30);
        $now = new \DateTimeImmutable('2026-07-29 12:00:00');

        $restoration = $this->service($guild, $vein, null)->open($this->player(), $zone, 'filon-de-cobalt', $now);

        self::assertSame(2700, $restoration->getCostGils());
        self::assertSame(10_000 - 2700, $guild->getGilsTreasury());
        self::assertSame(0.30, $restoration->getPalenessAtStart());
        self::assertSame('2026-08-03 12:00:00', $restoration->getEndsAt()->format('Y-m-d H:i:s'));
    }

    /**
     * Le tresor insuffisant refuse **sans rien debiter** : un paiement partiel
     * laisserait une guilde plus pauvre et un filon intact.
     */
    public function testAThinTreasuryBuysNothingAndLosesNothing(): void
    {
        $guild = $this->guild(1_000);
        $zone = $this->zone();
        $vein = (new ZoneVein($zone, 'filon-de-cobalt', 32))->setPaleness(0.30);

        $this->expectException(VeinRestorationException::class);
        $this->expectExceptionMessage('game.zone.restoration.error.treasury_too_low');

        try {
            $this->service($guild, $vein, null)->open($this->player(), $zone, 'filon-de-cobalt');
        } finally {
            self::assertSame(1_000, $guild->getGilsTreasury());
        }
    }

    /**
     * Idempotence : un filon ne porte jamais deux chantiers. Deux clics — ou
     * deux guildes — paieraient sinon pour le meme effet.
     */
    public function testASecondWorksOnTheSameVeinIsRefused(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $vein = (new ZoneVein($zone, 'filon-de-cobalt', 32))->setPaleness(0.30);
        $underway = new VeinRestoration($zone, 'filon-de-cobalt', $guild, 2700, 0.30, new \DateTimeImmutable('+3 days'));

        $this->expectException(VeinRestorationException::class);
        $this->expectExceptionMessage('game.zone.restoration.error.already_underway');

        try {
            $this->service($guild, $vein, $underway)->open($this->player(), $zone, 'filon-de-cobalt');
        } finally {
            self::assertSame(10_000, $guild->getGilsTreasury());
        }
    }

    public function testAVeinBelowTheThresholdHasNothingToMend(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $vein = (new ZoneVein($zone, 'filon-de-cobalt', 32))->setPaleness(0.05);

        $this->expectException(VeinRestorationException::class);
        $this->expectExceptionMessage('game.zone.restoration.error.nothing_to_mend');

        $this->service($guild, $vein, null)->open($this->player(), $zone, 'filon-de-cobalt');
    }

    /**
     * L'autorite est celle qui gouverne deja la depense : un chantier est un
     * retrait du tresor, rien ne justifierait qu'il obeisse a une regle plus
     * permissive.
     */
    public function testARecruitCannotCommitTheTreasury(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $vein = (new ZoneVein($zone, 'filon-de-cobalt', 32))->setPaleness(0.30);

        $this->expectException(VeinRestorationException::class);
        $this->expectExceptionMessage('game.zone.restoration.error.rank_too_low');

        $this->service($guild, $vein, null, GuildRank::Recruit)->open($this->player(), $zone, 'filon-de-cobalt');
    }

    public function testAPlayerWithoutAGuildHasNoTreasuryToSpend(): void
    {
        $zone = $this->zone();
        $vein = (new ZoneVein($zone, 'filon-de-cobalt', 32))->setPaleness(0.30);

        $this->expectException(VeinRestorationException::class);
        $this->expectExceptionMessage('game.zone.restoration.error.no_guild');

        $this->service(null, $vein, null)->open($this->player(), $zone, 'filon-de-cobalt');
    }

    // =====================================================================
    // Ce que l'ecran annonce
    // =====================================================================

    /**
     * Le prix se lit meme sans guilde : restaurer est un acte de gouvernement,
     * et le prix d'un acte de gouvernement se connait avant qu'on ait le pouvoir
     * de le poser.
     */
    public function testTheQuoteIsPublicEvenToAPlayerWhoCannotPayIt(): void
    {
        $zone = $this->zone();
        $vein = (new ZoneVein($zone, 'filon-de-cobalt', 32))->setPaleness(0.30);

        $offers = $this->service(null, $vein, null)->offersFor($this->player(), $zone);

        self::assertArrayHasKey('filon-de-cobalt', $offers);
        self::assertSame(2700, $offers['filon-de-cobalt']->cost);
        self::assertSame(30, $offers['filon-de-cobalt']->palenessPercent);
        self::assertFalse($offers['filon-de-cobalt']->canOpen());
        self::assertSame('game.zone.restoration.error.no_guild', $offers['filon-de-cobalt']->blockedReason);
    }

    public function testAVeinWithNothingToMendIsNotOffered(): void
    {
        $zone = $this->zone();
        $vein = (new ZoneVein($zone, 'filon-de-cobalt', 32))->setPaleness(0.05);

        self::assertSame([], $this->service($this->guild(10_000), $vein, null)->offersFor($this->player(), $zone));
    }

    public function testAnUnderwayWorksNamesTheGuildThatPaidForIt(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $vein = (new ZoneVein($zone, 'filon-de-cobalt', 32))->setPaleness(0.30);
        $underway = new VeinRestoration($zone, 'filon-de-cobalt', $guild, 2700, 0.30, new \DateTimeImmutable('+3 days'));

        $offers = $this->service($guild, $vein, $underway)->offersFor($this->player(), $zone);

        self::assertTrue($offers['filon-de-cobalt']->isUnderway());
        self::assertSame('Les Veilleurs', $offers['filon-de-cobalt']->activeGuildName);
        self::assertFalse($offers['filon-de-cobalt']->canOpen());
    }

    // =====================================================================
    // Fixtures
    // =====================================================================

    private function zone(): Zone
    {
        return (new Zone())->setSlug('crete')->setName('Crête');
    }

    private function player(): Player
    {
        return (new Player())->setName('Sylve');
    }

    private function guild(int $treasury): Guild
    {
        return (new Guild())->setName('Les Veilleurs')->setGilsTreasury($treasury);
    }

    private function service(?Guild $guild, ZoneVein $vein, ?VeinRestoration $underway, GuildRank $rank = GuildRank::Officer): VeinRestorationService
    {
        $memberRepository = $this->createMock(EntityRepository::class);
        $memberRepository->method('findOneBy')->willReturn(
            null === $guild
                ? null
                : (new GuildMember())->setGuild($guild)->setPlayer($this->player())->setRank($rank),
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($memberRepository);

        $restorations = $this->createMock(VeinRestorationRepository::class);
        $restorations->method('findActive')->willReturn($underway);
        $restorations->method('activeInZone')->willReturn(
            null === $underway ? [] : [$underway->getVeinSlug() => $underway],
        );

        $veins = $this->createMock(ZoneVeinRepository::class);
        $veins->method('findOneByZoneAndSlug')->willReturn($vein);
        $veins->method('findBy')->willReturn([$vein]);

        $loader = $this->createMock(SettlementDefinitionLoader::class);
        $loader->method('load')->willReturn([
            'paleness' => $this->paleness(),
            'restoration' => $this->definition(),
        ]);

        return new VeinRestorationService(
            $entityManager,
            $restorations,
            $veins,
            $loader,
            $this->createMock(WorldFactService::class),
            new GuildSpendingAuthority($entityManager),
        );
    }
}
