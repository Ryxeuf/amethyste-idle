<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildChallengeProgress;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Entity\App\WeeklyChallenge;
use App\Enum\InfluenceActivityType;
use App\GameEngine\Guild\InfluenceManager;
use App\GameEngine\Guild\SeasonManager;
use App\GameEngine\Guild\WeeklyChallengeRotator;
use App\GameEngine\Guild\WeeklyChallengeTemplate;
use App\GameEngine\Guild\WeeklyChallengeTemplateLoader;
use App\GameEngine\Realtime\Guild\InfluenceMercurePublisher;
use App\Repository\GuildChallengeProgressRepository;
use App\Repository\WeeklyChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * RET-01 — la rotation hebdomadaire des defis de guilde.
 *
 * Le systeme etait complet mais ne tournait pas : les fixtures posaient deux
 * semaines, apres quoi l'ecran restait vide. Ces tests verrouillent les trois
 * gestes de la rotation (cloturer, activer, creer) et surtout son idempotence —
 * un cron qui rejoue ne doit ni doubler les versements ni doubler les defis.
 */
class WeeklyChallengeRotatorTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $parameterRepo;
    private WeeklyChallengeRepository&MockObject $challengeRepo;
    private GuildChallengeProgressRepository&MockObject $progressRepo;
    private SeasonManager&MockObject $seasonManager;
    private InfluenceManager&MockObject $influenceManager;
    private WeeklyChallengeTemplateLoader&MockObject $templateLoader;
    private InfluenceMercurePublisher&MockObject $publisher;
    private WeeklyChallengeRotator $rotator;

    /** @var list<object> */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepo = $this->createMock(EntityRepository::class);
        $this->challengeRepo = $this->createMock(WeeklyChallengeRepository::class);
        $this->progressRepo = $this->createMock(GuildChallengeProgressRepository::class);
        $this->seasonManager = $this->createMock(SeasonManager::class);
        $this->influenceManager = $this->createMock(InfluenceManager::class);
        $this->templateLoader = $this->createMock(WeeklyChallengeTemplateLoader::class);
        $this->publisher = $this->createMock(InfluenceMercurePublisher::class);

        $this->em->method('getRepository')->willReturn($this->parameterRepo);
        $this->em->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });

        $this->templateLoader->method('load')->willReturn($this->pool());

        $this->rotator = new WeeklyChallengeRotator(
            $this->em,
            $this->challengeRepo,
            $this->progressRepo,
            $this->seasonManager,
            $this->influenceManager,
            $this->templateLoader,
            $this->publisher,
            new NullLogger(),
        );
    }

    // -----------------------------------------------------------------
    // Creation de la semaine
    // -----------------------------------------------------------------

    public function testCreatesChallengesWhenTheWeekHasNone(): void
    {
        $season = $this->season();
        $this->seasonManager->method('getCurrentSeason')->willReturn($season);
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->challengeRepo->method('findEndingBetween')->willReturn([]);
        $this->challengeRepo->method('findOverlapping')->willReturn([]);
        $this->challengeRepo->method('maxWeekNumber')->willReturn(4);

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertTrue($result->rotated);
        self::assertSame(3, $result->createdChallenges);
        self::assertCount(3, $result->activeChallenges);

        foreach ($result->activeChallenges as $challenge) {
            self::assertSame(5, $challenge->getWeekNumber(), 'La semaine creee suit la derniere posee.');
            self::assertSame($season, $challenge->getSeason());
            // Lundi 00h00 → dimanche 23h59m59s de la semaine de reference.
            self::assertSame('2026-08-03 00:00:00', $challenge->getStartsAt()->format('Y-m-d H:i:s'));
            self::assertSame('2026-08-09 23:59:59', $challenge->getEndsAt()->format('Y-m-d H:i:s'));
        }
    }

    /**
     * Trois lignes se lisent d'un coup d'oeil — a condition qu'elles ne
     * demandent pas trois fois la meme chose.
     */
    public function testCreatedChallengesCoverDistinctActivities(): void
    {
        $this->seasonManager->method('getCurrentSeason')->willReturn($this->season());
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->challengeRepo->method('findEndingBetween')->willReturn([]);
        $this->challengeRepo->method('findOverlapping')->willReturn([]);
        $this->challengeRepo->method('maxWeekNumber')->willReturn(0);

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        $activities = array_map(
            static fn (WeeklyChallenge $c): string => $c->getActivityType()->value,
            $result->activeChallenges,
        );

        self::assertSame($activities, array_unique($activities));
    }

    /**
     * La selection ne tire pas au hasard : deux serveurs a la meme semaine
     * doivent tomber sur les memes defis, et deux semaines consecutives sur des
     * defis differents.
     */
    public function testSelectionIsDeterministicAndVariesWeekToWeek(): void
    {
        $weekFive = $this->slugsForWeekAfter(4);
        $weekFiveAgain = $this->slugsForWeekAfter(4);
        $weekSix = $this->slugsForWeekAfter(5);

        self::assertSame($weekFive, $weekFiveAgain, 'Meme semaine, meme selection.');
        self::assertNotSame($weekFive, $weekSix, 'Deux semaines de suite ne servent pas la meme chose.');
    }

    public function testExistingChallengesForTheWeekAreNotDuplicated(): void
    {
        $season = $this->season();
        $existing = $this->challenge($season, InfluenceActivityType::Craft, 20, 80, 3);

        $this->seasonManager->method('getCurrentSeason')->willReturn($season);
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->challengeRepo->method('findEndingBetween')->willReturn([]);
        $this->challengeRepo->method('findOverlapping')->willReturn([$existing]);
        $this->challengeRepo->expects(self::never())->method('maxWeekNumber');

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertSame(0, $result->createdChallenges);
        self::assertSame([$existing], $result->activeChallenges);
    }

    // -----------------------------------------------------------------
    // Idempotence
    // -----------------------------------------------------------------

    public function testRotationIsANoOpWhenTheWeekWasAlreadyProcessed(): void
    {
        $parameter = new Parameter();
        $parameter->setName(WeeklyChallengeRotator::PARAMETER_NAME);
        // Semaine ISO du 5 aout 2026.
        $parameter->setValue('2026-W32');

        $this->parameterRepo->method('findOneBy')->willReturn($parameter);
        $this->seasonManager->expects(self::never())->method('getCurrentSeason');
        $this->publisher->expects(self::never())->method('publishChallengeRotation');

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertFalse($result->rotated);
        self::assertSame('2026-W32', $result->weekKey);
        self::assertSame([], $this->persisted);
    }

    public function testForceReplaysAnAlreadyProcessedWeek(): void
    {
        $parameter = new Parameter();
        $parameter->setName(WeeklyChallengeRotator::PARAMETER_NAME);
        $parameter->setValue('2026-W32');

        $this->parameterRepo->method('findOneBy')->willReturn($parameter);
        $this->seasonManager->method('getCurrentSeason')->willReturn($this->season());
        $this->challengeRepo->method('findEndingBetween')->willReturn([]);
        $this->challengeRepo->method('findOverlapping')->willReturn([]);
        $this->challengeRepo->method('maxWeekNumber')->willReturn(1);

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'), true);

        self::assertTrue($result->rotated);
    }

    /**
     * Sans saison active, la semaine n'est **pas** memorisee : la rotation doit
     * repasser des qu'une saison demarre, pas attendre lundi prochain.
     */
    public function testWithoutActiveSeasonNothingIsRecorded(): void
    {
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->seasonManager->method('getCurrentSeason')->willReturn(null);

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertFalse($result->rotated);
        self::assertSame([], $this->persisted);
    }

    // -----------------------------------------------------------------
    // Cloture de la semaine ecoulee
    // -----------------------------------------------------------------

    public function testClosureSettlesProgressThatReachedItsTarget(): void
    {
        $season = $this->season();
        $ended = $this->challenge($season, InfluenceActivityType::MobKill, 50, 100, 4);
        $progress = $this->progress($ended, 50);

        $this->seasonManager->method('getCurrentSeason')->willReturn($season);
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->challengeRepo->method('findEndingBetween')->willReturn([$ended]);
        $this->challengeRepo->method('findOverlapping')->willReturn([]);
        $this->challengeRepo->method('maxWeekNumber')->willReturn(4);
        $this->progressRepo->method('findForChallenges')->willReturn([$progress]);
        $this->influenceManager->method('getPlayerRegion')->willReturn(new Region());

        $this->influenceManager->expects(self::once())
            ->method('addPoints')
            ->with(
                self::identicalTo($progress->getGuild()),
                self::isInstanceOf(Region::class),
                self::identicalTo($season),
                self::identicalTo(100),
                self::identicalTo($progress->getGuild()->getLeader()),
                self::identicalTo(InfluenceActivityType::Challenge),
            );

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertSame(1, $result->closedChallenges);
        self::assertSame(1, $result->settledProgress);
        self::assertSame(100, $result->awardedBonusPoints);
        self::assertTrue($progress->isCompleted());
    }

    public function testClosureLeavesProgressBelowTargetAlone(): void
    {
        $season = $this->season();
        $ended = $this->challenge($season, InfluenceActivityType::MobKill, 50, 100, 4);
        $progress = $this->progress($ended, 49);

        $this->seasonManager->method('getCurrentSeason')->willReturn($season);
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->challengeRepo->method('findEndingBetween')->willReturn([$ended]);
        $this->challengeRepo->method('findOverlapping')->willReturn([]);
        $this->challengeRepo->method('maxWeekNumber')->willReturn(4);
        $this->progressRepo->method('findForChallenges')->willReturn([$progress]);

        $this->influenceManager->expects(self::never())->method('addPoints');

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertSame(0, $result->settledProgress);
        self::assertSame(0, $result->awardedBonusPoints);
        self::assertFalse($progress->isCompleted());
    }

    /**
     * Le versement en temps reel de `ChallengeTracker` reste la voie normale :
     * la cloture ne doit jamais payer une seconde fois.
     */
    public function testClosureDoesNotReAwardAnAlreadyCompletedProgress(): void
    {
        $season = $this->season();
        $ended = $this->challenge($season, InfluenceActivityType::MobKill, 50, 100, 4);
        $progress = $this->progress($ended, 60);
        $progress->setCompletedAt(new \DateTime('2026-08-01 12:00:00'));

        $this->seasonManager->method('getCurrentSeason')->willReturn($season);
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->challengeRepo->method('findEndingBetween')->willReturn([$ended]);
        $this->challengeRepo->method('findOverlapping')->willReturn([]);
        $this->challengeRepo->method('maxWeekNumber')->willReturn(4);
        $this->progressRepo->method('findForChallenges')->willReturn([$progress]);

        $this->influenceManager->expects(self::never())->method('addPoints');

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertSame(0, $result->settledProgress);
    }

    /**
     * Une progression sans region resolvable est tout de meme close — laissee
     * ouverte pour toujours, elle serait pire — mais rien n'est verse.
     */
    public function testClosureWithoutResolvableRegionStillClosesWithoutAwarding(): void
    {
        $season = $this->season();
        $ended = $this->challenge($season, InfluenceActivityType::MobKill, 50, 100, 4);
        $progress = $this->progress($ended, 50);

        $this->seasonManager->method('getCurrentSeason')->willReturn($season);
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->challengeRepo->method('findEndingBetween')->willReturn([$ended]);
        $this->challengeRepo->method('findOverlapping')->willReturn([]);
        $this->challengeRepo->method('maxWeekNumber')->willReturn(4);
        $this->progressRepo->method('findForChallenges')->willReturn([$progress]);
        $this->influenceManager->method('getPlayerRegion')->willReturn(null);

        $this->influenceManager->expects(self::never())->method('addPoints');

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertSame(1, $result->settledProgress);
        self::assertSame(0, $result->awardedBonusPoints);
        self::assertTrue($progress->isCompleted());
    }

    // -----------------------------------------------------------------
    // Annonce
    // -----------------------------------------------------------------

    public function testRotationAnnouncesTheNewWeek(): void
    {
        $this->seasonManager->method('getCurrentSeason')->willReturn($this->season());
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->challengeRepo->method('findEndingBetween')->willReturn([]);
        $this->challengeRepo->method('findOverlapping')->willReturn([]);
        $this->challengeRepo->method('maxWeekNumber')->willReturn(0);

        $this->publisher->expects(self::once())->method('publishChallengeRotation');

        $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));
    }

    public function testRotationRecordsTheIsoWeek(): void
    {
        $this->seasonManager->method('getCurrentSeason')->willReturn($this->season());
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $this->challengeRepo->method('findEndingBetween')->willReturn([]);
        $this->challengeRepo->method('findOverlapping')->willReturn([]);
        $this->challengeRepo->method('maxWeekNumber')->willReturn(0);

        $result = $this->rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        self::assertSame('2026-W32', $result->weekKey);

        $parameters = array_values(array_filter(
            $this->persisted,
            static fn (object $entity): bool => $entity instanceof Parameter,
        ));

        self::assertCount(1, $parameters);
        self::assertSame('2026-W32', $parameters[0]->getValue());
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @return list<string>
     */
    private function slugsForWeekAfter(int $lastWeekNumber): array
    {
        $rotator = new WeeklyChallengeRotator(
            $this->em,
            $challengeRepo = $this->createMock(WeeklyChallengeRepository::class),
            $this->progressRepo,
            $this->seasonManager,
            $this->influenceManager,
            $this->templateLoader,
            $this->publisher,
            new NullLogger(),
        );

        $this->seasonManager->method('getCurrentSeason')->willReturn($this->season());
        $this->parameterRepo->method('findOneBy')->willReturn(null);
        $challengeRepo->method('findEndingBetween')->willReturn([]);
        $challengeRepo->method('findOverlapping')->willReturn([]);
        $challengeRepo->method('maxWeekNumber')->willReturn($lastWeekNumber);

        $result = $rotator->rotate(new \DateTimeImmutable('2026-08-05 09:00:00'));

        return array_map(
            static fn (WeeklyChallenge $c): string => (string) ($c->getCriteria()['template'] ?? ''),
            $result->activeChallenges,
        );
    }

    /**
     * @return array{per_week: int, challenges: list<WeeklyChallengeTemplate>}
     */
    private function pool(): array
    {
        $templates = [];
        $activities = [
            InfluenceActivityType::MobKill,
            InfluenceActivityType::Craft,
            InfluenceActivityType::Harvest,
            InfluenceActivityType::Quest,
        ];

        foreach ($activities as $activity) {
            foreach ([1, 2] as $variant) {
                $templates[] = new WeeklyChallengeTemplate(
                    slug: sprintf('%s-%d', $activity->value, $variant),
                    activity: $activity,
                    title: sprintf('Defi %s %d', $activity->value, $variant),
                    titleEn: sprintf('Challenge %s %d', $activity->value, $variant),
                    description: 'Description.',
                    descriptionEn: 'Description.',
                    target: 10 * $variant,
                    bonusPoints: 50 * $variant,
                );
            }
        }

        return ['per_week' => 3, 'challenges' => $templates];
    }

    private function season(): InfluenceSeason
    {
        $season = new InfluenceSeason();
        $season->setName('Saison 1');
        $season->setSlug('saison-1');
        $season->setSeasonNumber(1);

        return $season;
    }

    private function challenge(InfluenceSeason $season, InfluenceActivityType $activity, int $target, int $bonus, int $week): WeeklyChallenge
    {
        $challenge = new WeeklyChallenge();
        $challenge->setSeason($season);
        $challenge->setTitle('Defi ' . $week);
        $challenge->setDescription('Description.');
        $challenge->setActivityType($activity);
        $challenge->setCriteria(['target' => $target]);
        $challenge->setBonusPoints($bonus);
        $challenge->setWeekNumber($week);
        $challenge->setStartsAt(new \DateTime('2026-07-27 00:00:00'));
        $challenge->setEndsAt(new \DateTime('2026-08-02 23:59:59'));

        return $challenge;
    }

    private function progress(WeeklyChallenge $challenge, int $current): GuildChallengeProgress
    {
        $leader = new Player();
        $leader->setName('Chef');

        $guild = new Guild();
        $guild->setName('Les Veilleurs')->setTag('VEIL');
        $guild->setLeader($leader);

        $progress = new GuildChallengeProgress();
        $progress->setGuild($guild);
        $progress->setChallenge($challenge);
        $progress->setProgress($current);

        return $progress;
    }
}
