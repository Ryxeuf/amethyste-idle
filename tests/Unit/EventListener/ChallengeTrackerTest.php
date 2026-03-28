<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\App\Fight;
use App\Entity\App\Guild;
use App\Entity\App\GuildChallengeProgress;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use App\Entity\App\WeeklyChallenge;
use App\Entity\Game\Item;
use App\Entity\Game\Quest;
use App\Entity\Game\Recipe;
use App\Enum\InfluenceActivityType;
use App\Event\CraftEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\Event\Map\ButcheringEvent;
use App\Event\Map\FishingEvent;
use App\Event\Map\SpotHarvestEvent;
use App\EventListener\ChallengeTracker;
use App\GameEngine\Guild\InfluenceManager;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChallengeTrackerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private InfluenceManager&MockObject $influenceManager;
    private PlayerHelper&MockObject $playerHelper;
    private ChallengeTracker $tracker;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->influenceManager = $this->createMock(InfluenceManager::class);
        $this->playerHelper = $this->createMock(PlayerHelper::class);

        $this->tracker = new ChallengeTracker(
            $this->em,
            $this->influenceManager,
            $this->playerHelper,
        );
    }

    public function testSubscribedEvents(): void
    {
        $events = ChallengeTracker::getSubscribedEvents();

        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertArrayHasKey(CraftEvent::NAME, $events);
        $this->assertArrayHasKey(SpotHarvestEvent::NAME, $events);
        $this->assertArrayHasKey(FishingEvent::NAME, $events);
        $this->assertArrayHasKey(ButcheringEvent::NAME, $events);
        $this->assertArrayHasKey(QuestCompletedEvent::NAME, $events);
    }

    public function testSubscribedEventsHaveLowPriority(): void
    {
        $events = ChallengeTracker::getSubscribedEvents();

        foreach ($events as $config) {
            $this->assertIsArray($config);
            $this->assertSame(-10, $config[1]);
        }
    }

    public function testOnMobDeadSkipsNoGuild(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);

        $mob = $this->createMobWithFight(5, [$player]);

        $this->influenceManager->method('getPlayerGuild')->willReturn(null);

        $this->em->expects($this->never())->method('persist');

        $this->tracker->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadSkipsSummonedMobs(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(true);

        $this->influenceManager->expects($this->never())->method('getPlayerGuild');

        $this->tracker->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadSkipsDeadPlayers(): void
    {
        $deadPlayer = $this->createMock(Player::class);
        $deadPlayer->method('isDead')->willReturn(true);

        $mob = $this->createMobWithFight(5, [$deadPlayer]);

        $this->influenceManager->expects($this->never())->method('getPlayerGuild');

        $this->tracker->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnMobDeadTracksProgress(): void
    {
        $guild = $this->createGuild();
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);

        $mob = $this->createMobWithFight(8, [$player]);

        $this->influenceManager->method('getPlayerGuild')->willReturn($guild);

        $challenge = $this->createActiveChallenge(InfluenceActivityType::MobKill, 50, 100);

        $this->mockRepositories([$challenge], null);

        $this->em->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(GuildChallengeProgress::class));
        $this->em->expects($this->once())->method('flush');

        $this->tracker->onMobDead(new MobDeadEvent($mob));
    }

    public function testOnCraftTracksProgressWithQuantity(): void
    {
        $guild = $this->createGuild();
        $player = $this->createMock(Player::class);

        $this->influenceManager->method('getPlayerGuild')->willReturn($guild);

        $challenge = $this->createActiveChallenge(InfluenceActivityType::Craft, 20, 80);

        $progress = new GuildChallengeProgress();
        $progress->setGuild($guild);
        $progress->setChallenge($challenge);
        $progress->setProgress(5);
        $progress->setCreatedAt(new \DateTime());
        $progress->setUpdatedAt(new \DateTime());

        $this->mockRepositories([$challenge], $progress);
        $this->em->expects($this->once())->method('flush');

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getRequiredLevel')->willReturn(1);
        $recipe->method('getName')->willReturn('Potion');
        $item = $this->createMock(Item::class);
        $item->method('getSlug')->willReturn('potion');

        $this->tracker->onCraft(new CraftEvent($player, $recipe, $item, 3));

        $this->assertSame(8, $progress->getProgress());
    }

    public function testCompletionAwardsBonusPoints(): void
    {
        $guild = $this->createGuild();
        $player = $this->createMock(Player::class);
        $region = new Region();
        $region->setName('Plaines');
        $region->setSlug('plaines');

        $this->influenceManager->method('getPlayerGuild')->willReturn($guild);
        $this->influenceManager->method('getPlayerRegion')->willReturn($region);

        $challenge = $this->createActiveChallenge(InfluenceActivityType::Quest, 10, 120);

        $progress = new GuildChallengeProgress();
        $progress->setGuild($guild);
        $progress->setChallenge($challenge);
        $progress->setProgress(9);
        $progress->setCreatedAt(new \DateTime());
        $progress->setUpdatedAt(new \DateTime());

        $this->mockRepositories([$challenge], $progress);

        $this->influenceManager->expects($this->once())
            ->method('addPoints')
            ->with(
                $guild,
                $region,
                $challenge->getSeason(),
                120,
                $player,
                InfluenceActivityType::Challenge,
                $this->isType('array'),
            );

        $this->em->expects($this->once())->method('flush');

        $quest = $this->createMock(Quest::class);
        $quest->method('getName')->willReturn('Test quest');

        $this->tracker->onQuestCompleted(new QuestCompletedEvent($player, $quest));

        $this->assertSame(10, $progress->getProgress());
        $this->assertNotNull($progress->getCompletedAt());
    }

    public function testAlreadyCompletedChallengeIsSkipped(): void
    {
        $guild = $this->createGuild();
        $player = $this->createMock(Player::class);

        $this->influenceManager->method('getPlayerGuild')->willReturn($guild);

        $challenge = $this->createActiveChallenge(InfluenceActivityType::Fishing, 5, 60);

        $progress = new GuildChallengeProgress();
        $progress->setGuild($guild);
        $progress->setChallenge($challenge);
        $progress->setProgress(5);
        $progress->setCompletedAt(new \DateTime('-1 hour'));
        $progress->setCreatedAt(new \DateTime());
        $progress->setUpdatedAt(new \DateTime());

        $this->mockRepositories([$challenge], $progress);

        $this->influenceManager->expects($this->never())->method('addPoints');
        $this->em->expects($this->never())->method('flush');

        $objectLayer = $this->createMock(ObjectLayer::class);
        $caughtItem = $this->createMock(PlayerItem::class);

        $this->tracker->onFishing(new FishingEvent($player, $objectLayer, $caughtItem));
    }

    public function testOnFishingSkipsFailure(): void
    {
        $player = $this->createMock(Player::class);
        $objectLayer = $this->createMock(ObjectLayer::class);

        $event = new FishingEvent($player, $objectLayer, null);

        $this->influenceManager->expects($this->never())->method('getPlayerGuild');

        $this->tracker->onFishing($event);
    }

    public function testOnSpotHarvestSkipsNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $event = new SpotHarvestEvent($objectLayer, [$this->createMock(PlayerItem::class)]);

        $this->influenceManager->expects($this->never())->method('getPlayerGuild');

        $this->tracker->onSpotHarvest($event);
    }

    public function testOnSpotHarvestSkipsEmptyItems(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $objectLayer = $this->createMock(ObjectLayer::class);
        $event = new SpotHarvestEvent($objectLayer, []);

        $this->influenceManager->expects($this->never())->method('getPlayerGuild');

        $this->tracker->onSpotHarvest($event);
    }

    public function testOnButcheringSkipsEmptyItems(): void
    {
        $player = $this->createMock(Player::class);
        $mob = $this->createMock(Mob::class);

        $event = new ButcheringEvent($player, $mob, []);

        $this->influenceManager->expects($this->never())->method('getPlayerGuild');

        $this->tracker->onButchering($event);
    }

    public function testNoChallengesActiveDoesNothing(): void
    {
        $guild = $this->createGuild();
        $player = $this->createMock(Player::class);

        $this->influenceManager->method('getPlayerGuild')->willReturn($guild);
        $this->mockRepositories([], null);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $quest = $this->createMock(Quest::class);
        $quest->method('getName')->willReturn('Test');

        $this->tracker->onQuestCompleted(new QuestCompletedEvent($player, $quest));
    }

    private function createGuild(): Guild
    {
        $guild = new Guild();
        $guild->setName('Test Guild');
        $guild->setTag('TEST');
        $guild->setCreatedAt(new \DateTime());
        $guild->setUpdatedAt(new \DateTime());

        return $guild;
    }

    private function createActiveChallenge(InfluenceActivityType $type, int $target, int $bonusPoints): WeeklyChallenge
    {
        $season = new InfluenceSeason();
        $season->setName('Test Season');
        $season->setSlug('test-season');
        $season->setSeasonNumber(1);
        $season->setStartsAt(new \DateTime('-7 days'));
        $season->setEndsAt(new \DateTime('+21 days'));
        $season->setCreatedAt(new \DateTime());
        $season->setUpdatedAt(new \DateTime());

        $challenge = new WeeklyChallenge();
        $challenge->setSeason($season);
        $challenge->setTitle('Test Challenge');
        $challenge->setDescription('Test description');
        $challenge->setActivityType($type);
        $challenge->setCriteria(['target' => $target]);
        $challenge->setBonusPoints($bonusPoints);
        $challenge->setWeekNumber(1);
        $challenge->setStartsAt(new \DateTime('-1 day'));
        $challenge->setEndsAt(new \DateTime('+6 days'));
        $challenge->setCreatedAt(new \DateTime());
        $challenge->setUpdatedAt(new \DateTime());

        return $challenge;
    }

    /**
     * @param Player[] $players
     */
    private function createMobWithFight(int $level, array $players): Mob&MockObject
    {
        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));

        $mob = $this->createMock(Mob::class);
        $mob->method('isSummoned')->willReturn(false);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getLevel')->willReturn($level);

        return $mob;
    }

    /**
     * @param list<WeeklyChallenge> $challenges
     */
    private function mockRepositories(array $challenges, ?GuildChallengeProgress $progress): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($challenges);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $challengeRepo = $this->createMock(EntityRepository::class);
        $challengeRepo->method('createQueryBuilder')->willReturn($qb);

        $progressRepo = $this->createMock(EntityRepository::class);
        $progressRepo->method('findOneBy')->willReturn($progress);

        $this->em->method('getRepository')
            ->willReturnCallback(function (string $class) use ($challengeRepo, $progressRepo) {
                if ($class === WeeklyChallenge::class) {
                    return $challengeRepo;
                }
                if ($class === GuildChallengeProgress::class) {
                    return $progressRepo;
                }

                return $this->createMock(EntityRepository::class);
            });
    }
}
