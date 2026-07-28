<?php

namespace App\Tests\Unit\GameEngine\Retention;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyAttendance;
use App\GameEngine\Retention\WeeklyAttendanceDefinitionLoader;
use App\GameEngine\Retention\WeeklyAttendanceService;
use App\GameEngine\Retention\WeeklyAttendanceTier;
use App\Repository\PlayerWeeklyAttendanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * On recompense la presence, on ne sanctionne jamais l'absence (RET-04).
 *
 * L'invariant central de la brique n'est pas ce qu'elle fait, c'est ce qu'elle
 * **ne peut pas** faire : penaliser une semaine d'absence. Il est verrouille
 * par la forme meme du stockage — une ligne par semaine ISO, donc aucune
 * memoire des semaines ratees — et par
 * `testAMissedWeekCostsExactlyNothing`.
 */
class WeeklyAttendanceServiceTest extends TestCase
{
    /** @var array<string, PlayerWeeklyAttendance> */
    private array $rows = [];

    protected function setUp(): void
    {
        $this->rows = [];
    }

    // =====================================================================
    // Le comptage : des jours distincts, pas des actions
    // =====================================================================

    public function testTheFirstActionOfADayCountsOneDay(): void
    {
        $player = new Player();

        $this->service()->record($player, new \DateTimeImmutable('2026-07-28 09:00:00'));

        self::assertSame(1, $this->service()->currentDays($player, new \DateTimeImmutable('2026-07-28 23:00:00')));
    }

    public function testTwentyActionsOnTheSameDayStillCountOneDay(): void
    {
        $player = new Player();
        $service = $this->service();

        for ($i = 0; $i < 20; ++$i) {
            $service->record($player, new \DateTimeImmutable('2026-07-28 09:00:00'));
        }

        self::assertSame(1, $service->currentDays($player, new \DateTimeImmutable('2026-07-28 09:00:00')));
    }

    public function testDistinctDaysAccumulateWithinTheWeek(): void
    {
        $player = new Player();
        $service = $this->service();

        // Mardi, mercredi, jeudi de la meme semaine ISO.
        $service->record($player, new \DateTimeImmutable('2026-07-28 09:00:00'));
        $service->record($player, new \DateTimeImmutable('2026-07-29 09:00:00'));
        $service->record($player, new \DateTimeImmutable('2026-07-30 09:00:00'));

        self::assertSame(3, $service->currentDays($player, new \DateTimeImmutable('2026-07-30 09:00:00')));
    }

    // =====================================================================
    // Les paliers : une fois chacun, et rien de retroactif
    // =====================================================================

    public function testTheSecondDayPaysTheFirstTierOnce(): void
    {
        $player = new Player();
        $player->setGils(0);
        $service = $this->service();

        self::assertNull($service->record($player, new \DateTimeImmutable('2026-07-28 09:00:00')));

        $tier = $service->record($player, new \DateTimeImmutable('2026-07-29 09:00:00'));

        self::assertInstanceOf(WeeklyAttendanceTier::class, $tier);
        self::assertSame(2, $tier->days);
        self::assertSame(800, $player->getGils());

        // Un troisieme jour ne repaie pas le palier de deux.
        self::assertNull($service->record($player, new \DateTimeImmutable('2026-07-30 09:00:00')));
        self::assertSame(800, $player->getGils());
    }

    public function testTheLastTierAlsoGivesBackPlayingTime(): void
    {
        $player = new Player();
        $player->setGils(0);
        $player->setActionEnergy(10);
        $player->setMaxActionEnergy(240);
        $service = $this->service();

        foreach (['07-27', '07-28', '07-29', '07-30', '07-31', '08-01'] as $day) {
            $service->record($player, new \DateTimeImmutable('2026-' . $day . ' 09:00:00'));
        }

        self::assertSame(800 + 1800 + 3500, $player->getGils());
        self::assertSame(70, $player->getActionEnergy(), 'Le dernier palier rend 60 points d\'energie.');
    }

    /**
     * L'energie rendue ne franchit jamais le plafond : le budget quotidien
     * reste egalitaire (BALANCE § 8).
     */
    public function testTheEnergyRewardNeverExceedsTheCap(): void
    {
        $player = new Player();
        $player->setActionEnergy(230);
        $player->setMaxActionEnergy(240);
        $service = $this->service();

        foreach (['07-27', '07-28', '07-29', '07-30', '07-31', '08-01'] as $day) {
            $service->record($player, new \DateTimeImmutable('2026-' . $day . ' 09:00:00'));
        }

        self::assertSame(240, $player->getActionEnergy());
    }

    // =====================================================================
    // L'interdit : aucune memoire des semaines ratees
    // =====================================================================

    /**
     * L'invariant du plan. Une semaine sautee ne retire rien, ne reduit rien,
     * et ne rend pas la suivante plus difficile : le compteur repart de zero
     * comme apres n'importe quelle semaine.
     */
    public function testAMissedWeekCostsExactlyNothing(): void
    {
        $player = new Player();
        $player->setGils(0);
        $service = $this->service();

        // Semaine 1 : deux jours, premier palier paye.
        $service->record($player, new \DateTimeImmutable('2026-07-27 09:00:00'));
        $service->record($player, new \DateTimeImmutable('2026-07-28 09:00:00'));
        self::assertSame(800, $player->getGils());

        // Semaine 2 : absence totale. Aucune ecriture, donc rien a perdre.

        // Semaine 3 : le compteur repart a zero, et le premier palier se
        // repaie exactement comme la premiere fois.
        $service->record($player, new \DateTimeImmutable('2026-08-10 09:00:00'));
        self::assertSame(1, $service->currentDays($player, new \DateTimeImmutable('2026-08-10 09:00:00')));

        $tier = $service->record($player, new \DateTimeImmutable('2026-08-11 09:00:00'));
        self::assertNotNull($tier);
        self::assertSame(2, $tier->days);
        self::assertSame(1600, $player->getGils());
    }

    public function testANewWeekResetsTheCounterWithoutAnyCron(): void
    {
        $player = new Player();
        $service = $this->service();

        // Dimanche 2026-08-02, puis lundi 2026-08-03 : deux semaines ISO.
        $service->record($player, new \DateTimeImmutable('2026-08-02 23:00:00'));
        self::assertSame(1, $service->currentDays($player, new \DateTimeImmutable('2026-08-02 23:00:00')));

        $service->record($player, new \DateTimeImmutable('2026-08-03 00:30:00'));
        self::assertSame(1, $service->currentDays($player, new \DateTimeImmutable('2026-08-03 00:30:00')));
    }

    // =====================================================================
    // La lecture n'inscrit rien
    // =====================================================================

    /**
     * Regarder son tableau de bord n'a jamais compte comme une journee active.
     * C'est le seul endroit ou la distinction entre « s'etre connecte » et
     * « avoir joue » pourrait se perdre.
     */
    public function testReadingTheCounterNeverRecordsAPresence(): void
    {
        $player = new Player();
        $service = $this->service();

        self::assertSame(0, $service->currentDays($player, new \DateTimeImmutable('2026-07-28 09:00:00')));
        self::assertSame([], $this->rows);
    }

    // =====================================================================
    // Le prochain palier
    // =====================================================================

    public function testNextTierWalksUpTheLadderThenStops(): void
    {
        $service = $this->service();

        self::assertSame(2, $service->nextTier(0)?->days);
        self::assertSame(4, $service->nextTier(2)?->days);
        self::assertSame(6, $service->nextTier(5)?->days);
        self::assertNull($service->nextTier(6));
        self::assertNull($service->nextTier(7));
    }

    // =====================================================================
    // Fixtures
    // =====================================================================

    private function service(): WeeklyAttendanceService
    {
        $repository = $this->createMock(PlayerWeeklyAttendanceRepository::class);
        $repository->method('findOneForWeek')->willReturnCallback(
            fn (Player $player, string $weekKey): ?PlayerWeeklyAttendance => $this->rows[$weekKey] ?? null,
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            if ($entity instanceof PlayerWeeklyAttendance) {
                $this->rows[$entity->getWeekKey()] = $entity;
            }
        });

        $loader = $this->createMock(WeeklyAttendanceDefinitionLoader::class);
        $loader->method('load')->willReturn([
            new WeeklyAttendanceTier(2, 800, 0),
            new WeeklyAttendanceTier(4, 1800, 0),
            new WeeklyAttendanceTier(6, 3500, 60),
        ]);

        return new WeeklyAttendanceService($repository, $loader, $entityManager);
    }
}
