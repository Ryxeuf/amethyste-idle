<?php

namespace App\Tests\Integration\Event;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerAchievement;
use App\Entity\Game\Achievement;
use App\Entity\Game\Faction;
use App\Entity\Game\Monster;
use App\Event\Fight\MobDeadEvent;
use App\EventListener\BestiaryListener;
use App\GameEngine\Achievement\AchievementTracker;
use App\GameEngine\Quest\PlayerQuestUpdater;
use App\GameEngine\Quest\QuestMonsterTrackingListener;
use App\GameEngine\Reputation\ReputationListener;
use App\GameEngine\Reputation\ReputationManager;
use App\Repository\PlayerAchievementRepository;
use App\Repository\PlayerBestiaryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Integration tests: MobDeadEvent triggers BestiaryListener, AchievementTracker,
 * QuestMonsterTrackingListener and ReputationListener simultaneously.
 */
class MobDeadEventIntegrationTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerBestiaryRepository&MockObject $bestiaryRepository;
    private PlayerAchievementRepository&MockObject $playerAchievementRepository;
    private PlayerQuestUpdater&MockObject $playerQuestUpdater;
    private ReputationManager&MockObject $reputationManager;

    private BestiaryListener $bestiaryListener;
    private AchievementTracker $achievementTracker;
    private QuestMonsterTrackingListener $questMonsterTracker;
    private ReputationListener $reputationListener;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bestiaryRepository = $this->createMock(PlayerBestiaryRepository::class);
        $this->playerAchievementRepository = $this->createMock(PlayerAchievementRepository::class);
        $this->playerQuestUpdater = $this->createMock(PlayerQuestUpdater::class);
        $this->reputationManager = $this->createMock(ReputationManager::class);

        $this->bestiaryListener = new BestiaryListener(
            $this->bestiaryRepository,
            $this->entityManager,
        );
        $this->achievementTracker = new AchievementTracker(
            $this->entityManager,
            $this->playerAchievementRepository,
            $this->createMock(EventDispatcherInterface::class),
        );
        $this->questMonsterTracker = new QuestMonsterTrackingListener(
            $this->playerQuestUpdater,
        );
        $this->reputationListener = new ReputationListener(
            $this->reputationManager,
            $this->entityManager,
        );
    }

    public function testAllListenersSubscribeToMobDeadEvent(): void
    {
        $this->assertArrayHasKey(MobDeadEvent::NAME, BestiaryListener::getSubscribedEvents());
        $this->assertArrayHasKey(MobDeadEvent::NAME, AchievementTracker::getSubscribedEvents());
        $this->assertArrayHasKey(MobDeadEvent::NAME, QuestMonsterTrackingListener::getSubscribedEvents());
        $this->assertArrayHasKey(MobDeadEvent::NAME, ReputationListener::getSubscribedEvents());
    }

    public function testMobDeadTriggersAllListeners(): void
    {
        $player = $this->createPlayer();
        $faction = $this->createMock(Faction::class);
        $mob = $this->createMobWithFight([$player], 'slime', 3, $faction);
        $event = new MobDeadEvent($mob);

        // Bestiary: new entry created
        $this->bestiaryRepository->method('findOneByPlayerAndMonster')->willReturn(null);

        // Achievement: no matching achievements
        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findBy')->willReturn([]);
        $this->entityManager->method('getRepository')
            ->willReturnCallback(function (string $class) use ($achievementRepo) {
                if ($class === Achievement::class) {
                    return $achievementRepo;
                }

                return $this->createMock(EntityRepository::class);
            });

        // Reputation: faction associated → rep granted
        $this->reputationManager->method('getReputationAmount')->with(3)->willReturn(5);
        $this->reputationManager->expects($this->once())
            ->method('addReputation')
            ->with($player, $faction, 5);

        // Quest tracker called
        $this->playerQuestUpdater->expects($this->once())
            ->method('updateMobKilled')
            ->with($mob);

        // Execute all listeners (simulating event dispatch)
        $this->bestiaryListener->onMobDead($event);
        $this->achievementTracker->onMobDead($event);
        $this->questMonsterTracker->updateMonsterPlayerQuest($event);
        $this->reputationListener->onMobDead($event);
    }

    public function testMobDeadWithDeadPlayerSkipsBestiaryAndReputation(): void
    {
        $deadPlayer = $this->createPlayer(isDead: true);
        $mob = $this->createMobWithFight([$deadPlayer], 'goblin', 2, $this->createMock(Faction::class));
        $event = new MobDeadEvent($mob);

        // Bestiary: dead player should NOT create entry
        $this->bestiaryRepository->expects($this->never())
            ->method('findOneByPlayerAndMonster');

        // Reputation: dead player should NOT get rep
        $this->reputationManager->expects($this->never())
            ->method('addReputation');

        // But quest tracking still runs (it uses PlayerQuestHelper internally, not fight players)
        $this->playerQuestUpdater->expects($this->once())
            ->method('updateMobKilled')
            ->with($mob);

        $this->bestiaryListener->onMobDead($event);
        $this->reputationListener->onMobDead($event);
        $this->questMonsterTracker->updateMonsterPlayerQuest($event);
    }

    public function testMobDeadWithNoFightReturnsEarlyForAllListeners(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('getFight')->willReturn(null);
        $mob->method('getMonster')->willReturn($this->createMock(Monster::class));

        $event = new MobDeadEvent($mob);

        // None should flush
        $this->entityManager->expects($this->never())->method('flush');

        $this->bestiaryListener->onMobDead($event);
        $this->reputationListener->onMobDead($event);
    }

    public function testMobDeadWithNoFactionSkipsReputation(): void
    {
        $player = $this->createPlayer();
        $mob = $this->createMobWithFight([$player], 'wolf', 1, null);
        $event = new MobDeadEvent($mob);

        $this->reputationManager->expects($this->never())
            ->method('addReputation');

        $this->reputationListener->onMobDead($event);
    }

    public function testMobDeadProgressesMobKillAchievement(): void
    {
        $player = $this->createPlayer();
        $mob = $this->createMobWithFight([$player], 'slime', 1, null);
        $event = new MobDeadEvent($mob);

        $achievement = $this->createMock(Achievement::class);
        $achievement->method('getCriteriaType')->willReturn('mob_kill');
        $achievement->method('getCriteriaMonsterSlug')->willReturn('slime');
        $achievement->method('getCriteriaCount')->willReturn(10);
        $achievement->method('getReward')->willReturn(null);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findBy')->willReturn([$achievement]);
        $this->entityManager->method('getRepository')
            ->with(Achievement::class)
            ->willReturn($achievementRepo);

        $playerAchievement = $this->createMock(PlayerAchievement::class);
        $playerAchievement->method('isCompleted')->willReturn(false);
        $playerAchievement->method('getProgress')->willReturn(4);
        $playerAchievement->expects($this->once())
            ->method('setProgress')
            ->with(5);

        $this->playerAchievementRepository->method('findOneByPlayerAndAchievement')
            ->willReturn($playerAchievement);

        $this->achievementTracker->onMobDead($event);
    }

    public function testMobDeadCompletesAchievementAndGrantsReward(): void
    {
        $player = $this->createPlayer();
        $mob = $this->createMobWithFight([$player], 'slime', 1, null);
        $event = new MobDeadEvent($mob);

        $achievement = $this->createMock(Achievement::class);
        $achievement->method('getCriteriaType')->willReturn('mob_kill');
        $achievement->method('getCriteriaMonsterSlug')->willReturn('slime');
        $achievement->method('getCriteriaCount')->willReturn(10);
        $achievement->method('getReward')->willReturn(['gils' => 100]);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findBy')->willReturn([$achievement]);
        $this->entityManager->method('getRepository')
            ->with(Achievement::class)
            ->willReturn($achievementRepo);

        $playerAchievement = $this->createMock(PlayerAchievement::class);
        $playerAchievement->method('isCompleted')->willReturn(false);
        $playerAchievement->method('getProgress')->willReturn(9);

        $playerAchievement->expects($this->once())
            ->method('setProgress')
            ->with(10);
        $playerAchievement->expects($this->once())
            ->method('setCompletedAt');

        $player->expects($this->once())
            ->method('addGils')
            ->with(100);

        $this->playerAchievementRepository->method('findOneByPlayerAndAchievement')
            ->willReturn($playerAchievement);

        $this->achievementTracker->onMobDead($event);
    }

    private function createPlayer(bool $isDead = false): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn($isDead);

        return $player;
    }

    private function createMobWithFight(array $players, string $monsterSlug, int $level, ?Faction $faction): Mob&MockObject
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn($monsterSlug);
        $monster->method('getName')->willReturn(ucfirst($monsterSlug));
        $monster->method('getLevel')->willReturn($level);
        $monster->method('getFaction')->willReturn($faction);

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));

        $mob = $this->createMock(Mob::class);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('getLevel')->willReturn($level);
        $mob->method('isSummoned')->willReturn(false);

        return $mob;
    }
}
