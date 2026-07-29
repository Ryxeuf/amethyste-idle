<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\GuildRank;
use App\Enum\SettlementDoctrine;
use App\Enum\SettlementRank;
use App\GameEngine\Codex\WorldFactService;
use App\GameEngine\Guild\GuildSpendingAuthority;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementDoctrineBonus;
use App\GameEngine\Settlement\SettlementDoctrineException;
use App\GameEngine\Settlement\SettlementDoctrineService;
use App\GameEngine\Settlement\VeinPalenessService;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * L'axe Extraire / Preserver devient un batiment (FOY-13).
 *
 * Quatre proprietes portent le jalon :
 *
 * 1. Les deux ateliers ont des effets **opposes** — c'est ce qui en fait un
 *    choix plutot qu'un achat.
 * 2. Ils sont **exclusifs** : adopter l'un remplace l'autre, jamais ne s'y
 *    ajoute.
 * 3. Ils se **verrouillent** une maree : une doctrine qui se retourne a la
 *    semaine ne divise plus personne.
 * 4. Ils **survivent a la regression** : un foyer qui retombe garde l'atelier
 *    qu'on lui a paye (regle du patrimoine, FOY-05 / FOY-10).
 */
class SettlementDoctrineServiceTest extends TestCase
{
    /**
     * @return array{minimum_rank: SettlementRank, cost: int, lock_days: int, foundry: array{gather_bonus: float, paleness_multiplier: float}, readers: array{lore_multiplier: float, paleness_multiplier: float}}
     */
    private function definition(): array
    {
        return [
            'minimum_rank' => SettlementRank::Hamlet,
            'cost' => 6000,
            'lock_days' => 28,
            'foundry' => ['gather_bonus' => 0.15, 'paleness_multiplier' => 1.5],
            'readers' => ['lore_multiplier' => 1.5, 'paleness_multiplier' => 0.5],
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
    // Les effets, opposes par construction
    // =====================================================================

    /**
     * Le cœur du jalon : a pression identique, la Fonderie abime davantage et
     * les Lecteurs moins. Si les deux tombaient du meme cote, l'axe serait une
     * ligne de documentation.
     */
    public function testTheTwoWorkshopsPullTheVeinInOppositeDirections(): void
    {
        $neutral = VeinPalenessService::step(0.20, 2.0, $this->paleness());
        $foundry = VeinPalenessService::step(0.20, 2.0, $this->paleness(), 0.0, 1.5);
        $readers = VeinPalenessService::step(0.20, 2.0, $this->paleness(), 0.0, 0.5);

        self::assertEqualsWithDelta(0.28, $neutral, 0.0001);
        self::assertEqualsWithDelta(0.32, $foundry, 0.0001);
        self::assertEqualsWithDelta(0.24, $readers, 0.0001);

        self::assertGreaterThan($neutral, $foundry);
        self::assertLessThan($neutral, $readers);
    }

    /**
     * La doctrine ne touche que la **montee**. Un atelier oriente ce qu'on fait
     * au filon ; il ne decide pas de la vitesse a laquelle le monde se repare
     * tout seul, sinon la Fonderie punirait aussi ceux qui s'abstiennent.
     */
    public function testNoWorkshopChangesHowFastTheWorldMendsItself(): void
    {
        $recovering = VeinPalenessService::step(0.30, 0.5, $this->paleness());

        self::assertSame($recovering, VeinPalenessService::step(0.30, 0.5, $this->paleness(), 0.0, 1.5));
        self::assertSame($recovering, VeinPalenessService::step(0.30, 0.5, $this->paleness(), 0.0, 0.5));
    }

    public function testTheFoundryRaisesGatheringAndTheReadersDoNot(): void
    {
        $zone = $this->zone();

        self::assertEqualsWithDelta(1.15, $this->bonus($this->settlement($zone, SettlementDoctrine::Foundry))->gatherMultiplier($zone), 0.0001);
        self::assertSame(1.0, $this->bonus($this->settlement($zone, SettlementDoctrine::Readers))->gatherMultiplier($zone));
        self::assertSame(1.0, $this->bonus($this->settlement($zone, null))->gatherMultiplier($zone));
    }

    public function testTheReadersRaiseLoreAndTheFoundryDoesNot(): void
    {
        $zone = $this->zone();

        self::assertEqualsWithDelta(1.5, $this->bonus($this->settlement($zone, SettlementDoctrine::Readers))->loreMultiplier($zone), 0.0001);
        self::assertSame(1.0, $this->bonus($this->settlement($zone, SettlementDoctrine::Foundry))->loreMultiplier($zone));
    }

    /**
     * Une zone sans foyer ne rend jamais d'erreur : elle rend 1. Lumiere et les
     * Jardins sont batis sur la Voute — y jouer est normal.
     */
    public function testAZoneWithoutASettlementSimplyHasNoDoctrine(): void
    {
        $zone = $this->zone();
        $bonus = $this->bonus(null);

        self::assertNull($bonus->doctrineOf($zone));
        self::assertSame(1.0, $bonus->gatherMultiplier($zone));
        self::assertSame(1.0, $bonus->palenessMultiplier($zone));
        self::assertSame(1.0, $bonus->loreMultiplier($zone));
    }

    // =====================================================================
    // L'adoption
    // =====================================================================

    public function testAdoptingDebitsTheTreasuryAndDatesTheChoice(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $settlement = $this->settlement($zone, null);
        $now = new \DateTimeImmutable('2026-07-29 12:00:00');

        $this->service($settlement, $guild)->adopt($this->player(), $zone, SettlementDoctrine::Foundry, $now);

        self::assertSame(SettlementDoctrine::Foundry, $settlement->getDoctrine());
        self::assertSame(10_000 - 6000, $guild->getGilsTreasury());
        self::assertSame('2026-07-29 12:00:00', $settlement->getDoctrineSince()?->format('Y-m-d H:i:s'));
    }

    /**
     * Exclusivite : adopter l'autre atelier **remplace**, il ne s'ajoute pas.
     * Une seule colonne, donc aucun chemin de code ne peut les cumuler.
     */
    public function testTheSecondWorkshopReplacesTheFirst(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $settlement = $this->settlement($zone, SettlementDoctrine::Foundry);
        $settlement->adoptDoctrine(SettlementDoctrine::Foundry, new \DateTimeImmutable('2026-01-01'));

        $this->service($settlement, $guild)->adopt($this->player(), $zone, SettlementDoctrine::Readers, new \DateTimeImmutable('2026-07-29'));

        self::assertSame(SettlementDoctrine::Readers, $settlement->getDoctrine());
    }

    /**
     * Le verrou d'une maree : on ne bascule pas d'un atelier a l'autre a la
     * semaine, sinon l'axe n'est qu'un interrupteur.
     */
    public function testTheDoctrineIsLockedForATideAfterBeingAdopted(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $settlement = $this->settlement($zone, SettlementDoctrine::Foundry);
        $settlement->adoptDoctrine(SettlementDoctrine::Foundry, new \DateTimeImmutable('2026-07-20'));

        $this->expectException(SettlementDoctrineException::class);
        $this->expectExceptionMessage('game.zone.doctrine.error.locked');

        try {
            $this->service($settlement, $guild)->adopt($this->player(), $zone, SettlementDoctrine::Readers, new \DateTimeImmutable('2026-07-29'));
        } finally {
            self::assertSame(10_000, $guild->getGilsTreasury());
            self::assertSame(SettlementDoctrine::Foundry, $settlement->getDoctrine());
        }
    }

    public function testAdoptingTheWorkshopAlreadyInPlaceBuysNothing(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $settlement = $this->settlement($zone, SettlementDoctrine::Foundry);

        $this->expectException(SettlementDoctrineException::class);
        $this->expectExceptionMessage('game.zone.doctrine.error.already_adopted');

        try {
            $this->service($settlement, $guild)->adopt($this->player(), $zone, SettlementDoctrine::Foundry);
        } finally {
            self::assertSame(10_000, $guild->getGilsTreasury());
        }
    }

    /**
     * Un Campement n'a pas encore d'atelier a offrir — le meme seuil que celui
     * a partir duquel un foyer a une identite et un bonus d'etabli.
     */
    public function testASettlementBelowTheThresholdHasNoWorkshopToOffer(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $settlement = $this->settlement($zone, null, SettlementRank::Camp);

        $this->expectException(SettlementDoctrineException::class);
        $this->expectExceptionMessage('game.zone.doctrine.error.settlement_too_small');

        $this->service($settlement, $guild)->adopt($this->player(), $zone, SettlementDoctrine::Foundry);
    }

    public function testAThinTreasuryBuysNothingAndLosesNothing(): void
    {
        $guild = $this->guild(1_000);
        $zone = $this->zone();
        $settlement = $this->settlement($zone, null);

        $this->expectException(SettlementDoctrineException::class);
        $this->expectExceptionMessage('game.zone.doctrine.error.treasury_too_low');

        try {
            $this->service($settlement, $guild)->adopt($this->player(), $zone, SettlementDoctrine::Foundry);
        } finally {
            self::assertSame(1_000, $guild->getGilsTreasury());
            self::assertNull($settlement->getDoctrine());
        }
    }

    public function testARecruitCannotCommitTheTreasury(): void
    {
        $guild = $this->guild(10_000);
        $zone = $this->zone();
        $settlement = $this->settlement($zone, null);

        $this->expectException(SettlementDoctrineException::class);
        $this->expectExceptionMessage('game.zone.doctrine.error.rank_too_low');

        $this->service($settlement, $guild, GuildRank::Recruit)->adopt($this->player(), $zone, SettlementDoctrine::Foundry);
    }

    public function testAZoneWithoutASettlementHasNothingToOrient(): void
    {
        $this->expectException(SettlementDoctrineException::class);
        $this->expectExceptionMessage('game.zone.doctrine.error.no_settlement');

        $this->service(null, $this->guild(10_000))->adopt($this->player(), $this->zone(), SettlementDoctrine::Foundry);
    }

    // =====================================================================
    // Le patrimoine
    // =====================================================================

    /**
     * Un foyer qui retombe **garde** son atelier. C'est la regle du patrimoine
     * (FOY-05 / FOY-10) : on borne ce qui reste a acquerir, on ne reprend pas
     * ce qui est acquis — et un atelier paye ne s'efface pas parce que la
     * frequentation a baisse. Le rang minimum ne se lit donc qu'a l'adoption.
     */
    public function testARegressingSettlementKeepsTheWorkshopItPaidFor(): void
    {
        $zone = $this->zone();
        $settlement = $this->settlement($zone, null);
        $settlement->adoptDoctrine(SettlementDoctrine::Readers, new \DateTimeImmutable('2026-07-01'));

        $settlement->setRank(SettlementRank::Ruin);

        self::assertSame(SettlementDoctrine::Readers, $settlement->getDoctrine());
        self::assertEqualsWithDelta(0.5, $this->bonus($settlement)->palenessMultiplier($zone), 0.0001);
    }

    // =====================================================================
    // Ce que l'ecran annonce
    // =====================================================================

    /**
     * Les deux ateliers s'affichent toujours : l'axe ne se comprend qu'en
     * voyant ce qu'on n'a pas choisi.
     */
    public function testBothWorkshopsAreAlwaysShown(): void
    {
        $zone = $this->zone();
        $offers = $this->service($this->settlement($zone, SettlementDoctrine::Foundry), $this->guild(10_000))
            ->offersFor($this->player(), $zone);

        self::assertCount(2, $offers);
        self::assertTrue($offers[0]->adopted);
        self::assertFalse($offers[1]->adopted);
    }

    public function testAZoneWithoutASettlementOffersNothing(): void
    {
        self::assertSame([], $this->service(null, $this->guild(10_000))->offersFor($this->player(), $this->zone()));
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

    private function settlement(Zone $zone, ?SettlementDoctrine $doctrine, SettlementRank $rank = SettlementRank::Town): Settlement
    {
        $settlement = (new Settlement($zone))->setRank($rank);
        if (null !== $doctrine) {
            $settlement->adoptDoctrine($doctrine, new \DateTimeImmutable('2026-01-01'));
        }

        return $settlement;
    }

    private function loader(): SettlementDefinitionLoader
    {
        $loader = $this->createMock(SettlementDefinitionLoader::class);
        $loader->method('load')->willReturn([
            'paleness' => $this->paleness(),
            'doctrine' => $this->definition(),
        ]);

        return $loader;
    }

    private function bonus(?Settlement $settlement): SettlementDoctrineBonus
    {
        $repository = $this->createMock(SettlementRepository::class);
        $repository->method('findOneByZone')->willReturn($settlement);

        return new SettlementDoctrineBonus($repository, $this->loader());
    }

    private function service(?Settlement $settlement, Guild $guild, GuildRank $rank = GuildRank::Officer): SettlementDoctrineService
    {
        $memberRepository = $this->createMock(EntityRepository::class);
        $memberRepository->method('findOneBy')->willReturn(
            (new GuildMember())->setGuild($guild)->setPlayer($this->player())->setRank($rank),
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($memberRepository);

        $repository = $this->createMock(SettlementRepository::class);
        $repository->method('findOneByZone')->willReturn($settlement);

        $loader = $this->loader();

        return new SettlementDoctrineService(
            $entityManager,
            $repository,
            $loader,
            $this->createMock(WorldFactService::class),
            new GuildSpendingAuthority($entityManager),
            new SettlementDoctrineBonus($repository, $loader),
        );
    }
}
