<?php

namespace App\Tests\Unit\GameEngine\Retention;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyAttendance;
use App\Entity\App\PlayerWeeklyCommission;
use App\Entity\App\Zone;
use App\Entity\Game\CodexEntry;
use App\Enum\InfluenceActivityType;
use App\Enum\WeeklyCommissionStatus;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Guild\WeeklyChallengeReader;
use App\GameEngine\Player\HubWeekRecapLine;
use App\GameEngine\Retention\WeeklyAttendanceService;
use App\GameEngine\Retention\WeeklyAttendanceTier;
use App\GameEngine\Retention\WeeklyRecapService;
use App\GameEngine\Settlement\SettlementChronicleService;
use App\Repository\PlayerWeeklyAttendanceRepository;
use App\Repository\PlayerWeeklyCommissionRepository;
use App\Repository\SettlementWeeklyWorkContributionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Le lundi se constate, il ne se planifie pas (RET-09).
 *
 * Ce que ces tests protegent tient en trois phrases. La rotation se lit en
 * comparant deux clefs de semaine, sans cron ni table neuve. Le recap se rend
 * **une fois** : le lire, c'est le consommer, faute de quoi il redevient une
 * modale a congedier ou un encart qui revient a chaque rafraichissement. Et une
 * semaine dont on ne sait rien ne se raconte pas — un personnage neuf est
 * inscrit sur la semaine courante, en silence.
 */
class WeeklyRecapServiceTest extends TestCase
{
    /** Un mardi : la semaine courante est `2026-W31`, la close `2026-W30`. */
    private const NOW = '2026-07-28 09:00:00';

    private const CURRENT_WEEK = '2026-W31';

    private const CLOSED_WEEK = '2026-W30';

    // =====================================================================
    // La rotation
    // =====================================================================

    /**
     * Une semaine deja vue ne se raconte pas deux fois.
     *
     * C'est le cas de loin le plus frequent — toutes les visites d'une semaine
     * sauf la premiere — et il ne doit rien couter : ni lecture, ni ecriture.
     */
    public function testAWeekAlreadySeenOpensNothing(): void
    {
        $player = $this->player(self::CURRENT_WEEK);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $recap = $this->service(['entityManager' => $entityManager])->consume($player, $this->now());

        self::assertNull($recap);
        self::assertSame(self::CURRENT_WEEK, $player->getHubWeekKey());
    }

    /**
     * Un personnage qui decouvre le hub est inscrit, pas raconte.
     *
     * La marque nulle couvre deux populations : les personnages neufs et ceux
     * qui existaient avant la colonne. Ni les uns ni les autres n'ont de
     * semaine close connue, et en inventer une serait un mensonge du premier
     * jour.
     */
    public function testAFirstVisitEnrollsWithoutTellingAStory(): void
    {
        $player = $this->player(null);

        $recap = $this->service()->consume($player, $this->now());

        self::assertNull($recap);
        self::assertSame(self::CURRENT_WEEK, $player->getHubWeekKey());
    }

    /**
     * Lire, c'est consommer : la marque avance dans la meme requete.
     *
     * C'est le point delicat du jalon. Marquer sur un geste du joueur ferait du
     * recap une modale a congedier ; ne pas marquer du tout le ferait revenir a
     * chaque rafraichissement. La seule reponse compatible avec « un etat du
     * meme bloc » est celle-ci.
     */
    public function testReadingTheRecapIsWhatConsumesIt(): void
    {
        $player = $this->player(self::CLOSED_WEEK);
        $service = $this->service(['contributedUnits' => 12]);

        $first = $service->consume($player, $this->now());
        $second = $service->consume($player, $this->now());

        self::assertNotNull($first);
        self::assertNull($second);
        self::assertSame(self::CURRENT_WEEK, $player->getHubWeekKey());
    }

    /**
     * La semaine racontee est celle qui etait marquee, pas « celle d'avant ».
     *
     * Un joueur absent trois semaines retrouve la derniere semaine qu'il a
     * jouee, et non une semaine vide calculee a rebours depuis aujourd'hui.
     */
    public function testTheRecapTellsTheWeekThePlayerLastSaw(): void
    {
        $player = $this->player('2026-W28');
        $service = $this->service(['contributedUnits' => 3, 'expectedWeek' => '2026-W28']);

        $recap = $service->consume($player, $this->now());

        self::assertNotNull($recap);
        self::assertSame('2026-W28', $recap->weekKey);
    }

    // =====================================================================
    // Ce qui s'est depose
    // =====================================================================

    /**
     * Le palier d'assiduite dit **ce qu'il a paye**.
     *
     * L'energie du palier est calculee depuis RET-04 et n'etait affichee nulle
     * part : une recompense qu'on ne voit pas passer n'a pas ete recue.
     */
    public function testTheAttendanceTierSaysWhatItPaid(): void
    {
        $player = $this->player(self::CLOSED_WEEK);
        $attendance = new PlayerWeeklyAttendance($player, self::CLOSED_WEEK);
        $attendance->setActiveDays(5);
        $attendance->setGrantedTierDays(4);

        $recap = $this->service([
            'attendance' => $attendance,
            'tiers' => [new WeeklyAttendanceTier(2, 60, 10), new WeeklyAttendanceTier(4, 120, 20)],
        ])->consume($player, $this->now());

        self::assertNotNull($recap);
        self::assertSame('attendance', $recap->lines[0]->key);
        self::assertSame(120, $recap->lines[0]->params['%gils%']);
        self::assertSame(20, $recap->lines[0]->params['%energy%']);
        self::assertSame(HubWeekRecapLine::TONE_GAIN, $recap->lines[0]->tone);
    }

    /**
     * Une semaine sans palier franchi n'en parle pas.
     *
     * Dire « aucun palier » serait un reproche, et le recap n'en fait pas.
     */
    public function testAWeekWithoutATierSaysNothingAboutIt(): void
    {
        $player = $this->player(self::CLOSED_WEEK);
        $attendance = new PlayerWeeklyAttendance($player, self::CLOSED_WEEK);
        $attendance->setActiveDays(1);

        $recap = $this->service(['attendance' => $attendance])->consume($player, $this->now());

        self::assertNull($recap);
    }

    /**
     * La commission expiree est un constat, jamais un reproche.
     *
     * « Repartie sans vous, une autre s'ouvre » : le ton est neutre, et le type
     * n'a de toute facon pas de mot pour le manque.
     */
    public function testAnExpiredCommissionIsStatedNeverBlamed(): void
    {
        $player = $this->player(self::CLOSED_WEEK);
        $commission = $this->commission($player, WeeklyCommissionStatus::Expired);

        $recap = $this->service(['commission' => $commission])->consume($player, $this->now());

        self::assertNotNull($recap);
        self::assertSame('commission_gone', $recap->lines[0]->key);
        self::assertSame(HubWeekRecapLine::TONE_NEUTRAL, $recap->lines[0]->tone);
    }

    /**
     * La commission livree se dit sur le ton du gain.
     */
    public function testADeliveredCommissionIsAGain(): void
    {
        $player = $this->player(self::CLOSED_WEEK);
        $commission = $this->commission($player, WeeklyCommissionStatus::Delivered);

        $recap = $this->service(['commission' => $commission])->consume($player, $this->now());

        self::assertNotNull($recap);
        self::assertSame('commission_delivered', $recap->lines[0]->key);
        self::assertSame(HubWeekRecapLine::TONE_GAIN, $recap->lines[0]->tone);
    }

    /**
     * Le chantier compte les unites portees, tous foyers confondus.
     */
    public function testTheWorksLineCountsWhatWasCarried(): void
    {
        $player = $this->player(self::CLOSED_WEEK);

        $recap = $this->service(['contributedUnits' => 40])->consume($player, $this->now());

        self::assertNotNull($recap);
        self::assertSame('settlement_work', $recap->lines[0]->key);
        self::assertSame(40, $recap->lines[0]->params['%count%']);
    }

    // =====================================================================
    // La chronique
    // =====================================================================

    /**
     * La chronique du foyer d'attache trouve sa premiere surface joueur.
     *
     * Elle n'etait lisible que par le Codex — autant dire par personne le lundi
     * matin.
     */
    public function testTheHomeChronicleReachesTheHub(): void
    {
        $player = $this->player(self::CLOSED_WEEK);
        $player->claimHomeZone($this->zone());

        $fact = new CodexEntry();
        $recap = $this->service(['chronicle' => $fact])->consume($player, $this->now());

        self::assertNotNull($recap);
        self::assertSame($fact, $recap->chronicle);
        self::assertSame([], $recap->lines);
    }

    /**
     * Sans foyer d'attache, aucune chronique n'est cherchee.
     *
     * Le foyer se gagne a la cloture de l'acte I : avant, il n'y a pas de ville
     * dont raconter les nouvelles.
     */
    public function testWithoutAHomeThereIsNoChronicleToTell(): void
    {
        $player = $this->player(self::CLOSED_WEEK);

        $chronicle = $this->createMock(SettlementChronicleService::class);
        $chronicle->expects(self::never())->method('latestFor');

        $recap = $this->service(['chronicleService' => $chronicle])->consume($player, $this->now());

        self::assertNull($recap);
    }

    // =====================================================================
    // Fabriques
    // =====================================================================

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW);
    }

    private function player(?string $hubWeekKey): Player
    {
        $player = new Player();
        $player->setName('Alba');
        $player->setHubWeekKey($hubWeekKey);

        return $player;
    }

    private function zone(): Zone
    {
        $zone = new Zone();
        $zone->setSlug('village-de-lumiere');
        $zone->setName('Le Fanal');

        return $zone;
    }

    private function commission(Player $player, WeeklyCommissionStatus $status): PlayerWeeklyCommission
    {
        $commission = new PlayerWeeklyCommission(
            $player,
            self::CLOSED_WEEK,
            'truites',
            InfluenceActivityType::Harvest,
            10,
        );
        $commission->setStatus($status);

        if (WeeklyCommissionStatus::Delivered === $status) {
            $commission->setDeliveredAt($this->now());
        }

        return $commission;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function service(array $overrides = []): WeeklyRecapService
    {
        $expectedWeek = $overrides['expectedWeek'] ?? self::CLOSED_WEEK;

        $attendanceRepository = $this->createMock(PlayerWeeklyAttendanceRepository::class);
        $attendanceRepository->method('findOneForWeek')->willReturnCallback(
            static fn (Player $p, string $week): ?PlayerWeeklyAttendance => $week === $expectedWeek
                ? ($overrides['attendance'] ?? null)
                : null,
        );

        $attendanceService = $this->createMock(WeeklyAttendanceService::class);
        $attendanceService->method('tiers')->willReturn($overrides['tiers'] ?? []);

        $commissionRepository = $this->createMock(PlayerWeeklyCommissionRepository::class);
        $commissionRepository->method('findOneForWeek')->willReturnCallback(
            static fn (Player $p, string $week): ?PlayerWeeklyCommission => $week === $expectedWeek
                ? ($overrides['commission'] ?? null)
                : null,
        );

        $workContributions = $this->createMock(SettlementWeeklyWorkContributionRepository::class);
        $workContributions->method('sumUnitsForWeek')->willReturnCallback(
            static fn (Player $p, string $week): int => $week === $expectedWeek
                ? (int) ($overrides['contributedUnits'] ?? 0)
                : 0,
        );

        $chronicleService = $overrides['chronicleService'] ?? $this->createMock(SettlementChronicleService::class);
        if (!isset($overrides['chronicleService'])) {
            $chronicleService->method('latestFor')->willReturn($overrides['chronicle'] ?? null);
        }

        return new WeeklyRecapService(
            $overrides['entityManager'] ?? $this->createMock(EntityManagerInterface::class),
            $attendanceRepository,
            $attendanceService,
            $commissionRepository,
            $workContributions,
            $this->createMock(GuildManager::class),
            $this->createMock(SeasonManager::class),
            $this->createMock(WeeklyChallengeReader::class),
            $chronicleService,
        );
    }
}
