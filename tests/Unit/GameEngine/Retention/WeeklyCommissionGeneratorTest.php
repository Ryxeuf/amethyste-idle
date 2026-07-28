<?php

namespace App\Tests\Unit\GameEngine\Retention;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyCommission;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Entity\Game\Domain;
use App\Enum\InfluenceActivityType;
use App\Enum\WeeklyCommissionStatus;
use App\GameEngine\Retention\WeeklyCommissionGenerator;
use App\GameEngine\Retention\WeeklyCommissionTemplate;
use App\GameEngine\Retention\WeeklyCommissionTemplateLoader;
use App\Repository\PlayerWeeklyCommissionRepository;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Le tirage de la commission de la semaine (RET-02).
 *
 * Trois proprietes portent ce jalon, et chacune casserait sans bruit : le
 * tirage doit rester **dans ce que le joueur travaille**, il doit etre
 * **deterministe** — sinon rejouer la commande devient un reroll — et il ne doit
 * **jamais** produire deux commissions pour la meme semaine.
 */
class WeeklyCommissionGeneratorTest extends TestCase
{
    private const MONDAY = '2026-07-27 00:02:00';

    /** @var list<PlayerWeeklyCommission> */
    private array $persisted = [];

    /** @var array<int, PlayerWeeklyCommission> */
    private array $existing = [];

    /** @var list<PlayerWeeklyCommission> */
    private array $stale = [];

    /** @var list<Zone> */
    private array $settlementZones = [];

    protected function setUp(): void
    {
        $this->persisted = [];
        $this->existing = [];
        $this->stale = [];
        $this->settlementZones = [];
    }

    public function testTheWeekKeyMatchesTheGuildRotation(): void
    {
        // Meme forme que `WeeklyChallengeRotator` : les deux rendez-vous
        // tombent le meme lundi, sur la meme clef ISO.
        self::assertSame('2026-W31', WeeklyCommissionGenerator::weekKey(new \DateTimeImmutable(self::MONDAY)));
        self::assertSame(
            WeeklyCommissionGenerator::weekKey(new \DateTimeImmutable(self::MONDAY)),
            WeeklyCommissionGenerator::weekKey(new \DateTimeImmutable('2026-07-31 18:00:00')),
        );
    }

    public function testEachPlayerGetsOneCommission(): void
    {
        $players = [$this->player(1), $this->player(2)];

        $report = $this->generator()->generateFor($players, $this->now());

        self::assertSame(2, $report['created']);
        self::assertCount(2, $this->persisted);
        self::assertSame('2026-W31', $this->persisted[0]->getWeekKey());
        self::assertSame(WeeklyCommissionStatus::Open, $this->persisted[0]->getStatus());
    }

    /**
     * Une commission par semaine et par personnage. Rejouer la rotation — ou
     * la forcer — ne doit pas redistribuer : ce serait un reroll deguise.
     */
    public function testAPlayerWhoAlreadyHasOneIsSkipped(): void
    {
        $player = $this->player(1);
        $this->existing[1] = new PlayerWeeklyCommission($player, '2026-W31', 'deja-tire', InfluenceActivityType::Craft, 10);

        $report = $this->generator()->generateFor([$player], $this->now());

        self::assertSame(0, $report['created']);
        self::assertSame(1, $report['skipped']);
        self::assertSame([], $this->persisted);
    }

    /**
     * Le tirage est stable : meme joueur, meme semaine, meme commission. Sans
     * cela, deux executions de la rotation donneraient deux objectifs.
     */
    public function testTheDrawIsDeterministicForAGivenPlayerAndWeek(): void
    {
        $first = $this->generator();
        $first->generateFor([$this->player(7)], $this->now());
        $a = $this->persisted[0]->getTemplateSlug();

        $this->persisted = [];
        $second = $this->generator();
        $second->generateFor([$this->player(7)], $this->now());

        self::assertSame($a, $this->persisted[0]->getTemplateSlug());
    }

    /**
     * Proposer de la peche a qui n'a jamais peche transforme un rendez-vous en
     * corvee.
     */
    public function testTheObjectiveComesFromWhatThePlayerAlreadyWorks(): void
    {
        $this->generator()->generateFor([$this->player(3)], $this->now());

        // Le seul domaine travaille est `miner` : le tirage ne peut donner que
        // la commission qui s'y rattache, quelle que soit la taille du pool.
        self::assertSame('filon', $this->persisted[0]->getTemplateSlug());
        self::assertSame(InfluenceActivityType::Harvest, $this->persisted[0]->getActivity());
    }

    /**
     * Un personnage tout neuf n'a travaille aucun domaine. Lui refuser une
     * commission le priverait du rendez-vous precisement la ou il compte le
     * plus.
     */
    public function testABrandNewCharacterStillGetsACommission(): void
    {
        $report = $this->generator()->generateFor([$this->player(4, [])], $this->now());

        self::assertSame(1, $report['created']);
        self::assertNotSame('', $this->persisted[0]->getTemplateSlug());
    }

    /**
     * Livrer la ou l'on est deja est le contraire d'une corvee de deplacement.
     */
    public function testDeliveryPrefersTheZoneThePlayerIsAlreadyIn(): void
    {
        $here = new Zone();
        $elsewhere = new Zone();
        $this->settlementZones = [$elsewhere, $here];

        $player = $this->player(5);
        $player->setCurrentZone($here);

        $this->generator()->generateFor([$player], $this->now());

        self::assertSame($here, $this->persisted[0]->getDeliveryZone());
    }

    public function testDeliveryFallsBackToAnyZoneWithASettlement(): void
    {
        $elsewhere = new Zone();
        $this->settlementZones = [$elsewhere];

        $player = $this->player(6);
        $player->setCurrentZone(new Zone()); // zone sans foyer

        $this->generator()->generateFor([$player], $this->now());

        self::assertSame($elsewhere, $this->persisted[0]->getDeliveryZone());
    }

    /**
     * Aucun foyer dans le monde : la commission existe quand meme. La refuser
     * priverait le joueur de son rendez-vous pour une raison qui ne le regarde
     * pas.
     */
    public function testACommissionSurvivesAWorldWithoutAnySettlement(): void
    {
        $this->settlementZones = [];

        $report = $this->generator()->generateFor([$this->player(8)], $this->now());

        self::assertSame(1, $report['created']);
        self::assertSame(1, $report['unassigned']);
        self::assertNull($this->persisted[0]->getDeliveryZone());
    }

    /**
     * Une commission de la semaine passee restee ouverte ferait deux
     * rendez-vous en cours, et le joueur ne saurait plus lequel court.
     */
    public function testLastWeekIsClosedBeforeTheNewOneOpens(): void
    {
        $old = new PlayerWeeklyCommission($this->player(9), '2026-W30', 'vieille', InfluenceActivityType::Quest, 5);
        $this->stale = [$old];

        $report = $this->generator()->generateFor([], $this->now());

        self::assertSame(1, $report['expired']);
        self::assertSame(WeeklyCommissionStatus::Expired, $old->getStatus());
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::MONDAY);
    }

    /**
     * @param ?list<string> $domains `null` = un mineur ; `[]` = un personnage neuf
     */
    private function player(int $id, ?array $domains = null): Player
    {
        $player = new class extends Player {
            public int $fakeId = 0;

            public function getId(): int
            {
                return $this->fakeId;
            }
        };
        $player->fakeId = $id;

        foreach ($domains ?? ['mineur'] as $slug) {
            $player->addDomainExperience($this->domainExperience($slug));
        }

        return $player;
    }

    /**
     * `Domain::getSlug()` est derive du **titre** : on pose donc le titre, pas
     * le slug. C'est le couplage que `WeeklyCommissionDomainTest` surveille.
     */
    private function domainExperience(string $slug): DomainExperience
    {
        $domain = new Domain();
        $domain->setTitle($slug);

        $experience = new DomainExperience();
        $experience->setDomain($domain);
        $experience->setTotalExperience(100);

        return $experience;
    }

    private function generator(): WeeklyCommissionGenerator
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            if ($entity instanceof PlayerWeeklyCommission) {
                $this->persisted[] = $entity;
            }
        });

        $commissionRepository = $this->createMock(PlayerWeeklyCommissionRepository::class);
        $commissionRepository->method('findOneForWeek')->willReturnCallback(
            fn (Player $player): ?PlayerWeeklyCommission => $this->existing[$player->getId()] ?? null,
        );
        $commissionRepository->method('findStaleOpen')->willReturnCallback(fn (): array => $this->stale);

        $settlementRepository = $this->createMock(SettlementRepository::class);
        $settlementRepository->method('findAllRanked')->willReturnCallback(
            fn (): array => array_map(static fn (Zone $zone): Settlement => new Settlement($zone), $this->settlementZones),
        );

        $loader = $this->createMock(WeeklyCommissionTemplateLoader::class);
        $loader->method('load')->willReturn([
            'per_week' => 1,
            'commissions' => [
                new WeeklyCommissionTemplate('filon', InfluenceActivityType::Harvest, 'miner', 'Filon', 'Vein', 'd', 'd', 60),
                new WeeklyCommissionTemplate('table', InfluenceActivityType::Fishing, 'fisherman', 'Table', 'Table', 'd', 'd', 20),
                new WeeklyCommissionTemplate('etabli', InfluenceActivityType::Craft, 'blacksmith', 'Etabli', 'Bench', 'd', 'd', 15),
            ],
        ]);

        return new WeeklyCommissionGenerator($entityManager, $commissionRepository, $settlementRepository, $loader);
    }
}
