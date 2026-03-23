<?php

namespace App\Tests\Integration\Event;

use App\Entity\App\Player;
use App\Entity\App\PlayerAchievement;
use App\Entity\Game\Achievement;
use App\Entity\Game\Faction;
use App\Entity\Game\Quest;
use App\Event\Game\QuestCompletedEvent;
use App\GameEngine\Achievement\AchievementTracker;
use App\GameEngine\Reputation\ReputationListener;
use App\GameEngine\Reputation\ReputationManager;
use App\Repository\PlayerAchievementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: QuestCompletedEvent triggers AchievementTracker
 * and ReputationListener simultaneously.
 */
class QuestCompletedEventIntegrationTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerAchievementRepository&MockObject $playerAchievementRepository;
    private ReputationManager&MockObject $reputationManager;

    private AchievementTracker $achievementTracker;
    private ReputationListener $reputationListener;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->playerAchievementRepository = $this->createMock(PlayerAchievementRepository::class);
        $this->reputationManager = $this->createMock(ReputationManager::class);

        $this->achievementTracker = new AchievementTracker(
            $this->entityManager,
            $this->playerAchievementRepository,
        );
        $this->reputationListener = new ReputationListener(
            $this->reputationManager,
            $this->entityManager,
        );
    }

    public function testBothListenersSubscribeToQuestCompletedEvent(): void
    {
        $this->assertArrayHasKey(QuestCompletedEvent::NAME, AchievementTracker::getSubscribedEvents());
        $this->assertArrayHasKey(QuestCompletedEvent::NAME, ReputationListener::getSubscribedEvents());
    }

    public function testQuestCompletedTriggersAchievementAndReputation(): void
    {
        $player = $this->createMock(Player::class);
        $faction = $this->createMock(Faction::class);
        $faction->method('getSlug')->willReturn('knights');

        $quest = $this->createMock(Quest::class);
        $quest->method('getRewards')->willReturn([
            'reputation' => [
                ['faction_slug' => 'knights', 'amount' => 50],
            ],
        ]);

        $event = new QuestCompletedEvent($player, $quest);

        // Achievement: quest_complete type exists
        $achievement = $this->createMock(Achievement::class);
        $achievement->method('getCriteriaType')->willReturn('quest_complete');
        $achievement->method('getCriteriaCount')->willReturn(5);
        $achievement->method('getReward')->willReturn(null);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findBy')->willReturn([$achievement]);

        $factionRepo = $this->createMock(EntityRepository::class);
        $factionRepo->method('findOneBy')
            ->with(['slug' => 'knights'])
            ->willReturn($faction);

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function (string $class) use ($achievementRepo, $factionRepo) {
                if ($class === Achievement::class) {
                    return $achievementRepo;
                }
                if ($class === Faction::class) {
                    return $factionRepo;
                }

                return $this->createMock(EntityRepository::class);
            });

        // Achievement: progress incremented
        $playerAchievement = $this->createMock(PlayerAchievement::class);
        $playerAchievement->method('isCompleted')->willReturn(false);
        $playerAchievement->method('getProgress')->willReturn(2);
        $playerAchievement->expects($this->once())
            ->method('setProgress')
            ->with(3);

        $this->playerAchievementRepository->method('findOneByPlayerAndAchievement')
            ->willReturn($playerAchievement);

        // Reputation: faction rep gained
        $this->reputationManager->expects($this->once())
            ->method('addReputation')
            ->with($player, $faction, 50);

        // Execute both listeners
        $this->achievementTracker->onQuestCompleted($event);
        $this->reputationListener->onQuestCompleted($event);
    }

    public function testQuestCompletedWithNoReputationRewardsSkipsReputation(): void
    {
        $player = $this->createMock(Player::class);
        $quest = $this->createMock(Quest::class);
        $quest->method('getRewards')->willReturn(['gils' => 100]);

        $event = new QuestCompletedEvent($player, $quest);

        $this->reputationManager->expects($this->never())
            ->method('addReputation');

        $this->reputationListener->onQuestCompleted($event);
    }

    public function testQuestCompletedWithUnknownFactionSlugSkipsReputation(): void
    {
        $player = $this->createMock(Player::class);
        $quest = $this->createMock(Quest::class);
        $quest->method('getRewards')->willReturn([
            'reputation' => [
                ['faction_slug' => 'nonexistent', 'amount' => 100],
            ],
        ]);

        $event = new QuestCompletedEvent($player, $quest);

        $factionRepo = $this->createMock(EntityRepository::class);
        $factionRepo->method('findOneBy')
            ->with(['slug' => 'nonexistent'])
            ->willReturn(null);

        $this->entityManager->method('getRepository')
            ->with(Faction::class)
            ->willReturn($factionRepo);

        $this->reputationManager->expects($this->never())
            ->method('addReputation');

        $this->reputationListener->onQuestCompleted($event);
    }

    public function testQuestCompletedCompletesAchievementAndGrantsGils(): void
    {
        $player = $this->createMock(Player::class);
        $quest = $this->createMock(Quest::class);
        $quest->method('getRewards')->willReturn([]);

        $event = new QuestCompletedEvent($player, $quest);

        $achievement = $this->createMock(Achievement::class);
        $achievement->method('getCriteriaType')->willReturn('quest_complete');
        $achievement->method('getCriteriaCount')->willReturn(3);
        $achievement->method('getReward')->willReturn(['gils' => 500]);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findBy')->willReturn([$achievement]);
        $this->entityManager->method('getRepository')
            ->with(Achievement::class)
            ->willReturn($achievementRepo);

        $playerAchievement = $this->createMock(PlayerAchievement::class);
        $playerAchievement->method('isCompleted')->willReturn(false);
        $playerAchievement->method('getProgress')->willReturn(2);

        $playerAchievement->expects($this->once())
            ->method('setProgress')
            ->with(3);
        $playerAchievement->expects($this->once())
            ->method('setCompletedAt');

        $player->expects($this->once())
            ->method('addGils')
            ->with(500);

        $this->playerAchievementRepository->method('findOneByPlayerAndAchievement')
            ->willReturn($playerAchievement);

        $this->achievementTracker->onQuestCompleted($event);
    }

    public function testQuestCompletedWithAlreadyCompletedAchievementDoesNothing(): void
    {
        $player = $this->createMock(Player::class);
        $quest = $this->createMock(Quest::class);
        $quest->method('getRewards')->willReturn([]);

        $event = new QuestCompletedEvent($player, $quest);

        $achievement = $this->createMock(Achievement::class);
        $achievement->method('getCriteriaType')->willReturn('quest_complete');
        $achievement->method('getCriteriaCount')->willReturn(3);
        $achievement->method('getReward')->willReturn(['gils' => 500]);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findBy')->willReturn([$achievement]);
        $this->entityManager->method('getRepository')
            ->with(Achievement::class)
            ->willReturn($achievementRepo);

        $playerAchievement = $this->createMock(PlayerAchievement::class);
        $playerAchievement->method('isCompleted')->willReturn(true);
        $playerAchievement->expects($this->never())->method('setProgress');

        $player->expects($this->never())->method('addGils');

        $this->playerAchievementRepository->method('findOneByPlayerAndAchievement')
            ->willReturn($playerAchievement);

        $this->achievementTracker->onQuestCompleted($event);
    }

    public function testQuestCompletedWithMultipleReputationRewards(): void
    {
        $player = $this->createMock(Player::class);
        $quest = $this->createMock(Quest::class);
        $quest->method('getRewards')->willReturn([
            'reputation' => [
                ['faction_slug' => 'knights', 'amount' => 50],
                ['faction_slug' => 'merchants', 'amount' => 25],
            ],
        ]);

        $event = new QuestCompletedEvent($player, $quest);

        $knightsFaction = $this->createMock(Faction::class);
        $merchantsFaction = $this->createMock(Faction::class);

        $factionRepo = $this->createMock(EntityRepository::class);
        $factionRepo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($knightsFaction, $merchantsFaction) {
                return match ($criteria['slug']) {
                    'knights' => $knightsFaction,
                    'merchants' => $merchantsFaction,
                    default => null,
                };
            });

        $this->entityManager->method('getRepository')
            ->with(Faction::class)
            ->willReturn($factionRepo);

        $this->reputationManager->expects($this->exactly(2))
            ->method('addReputation');

        $this->reputationListener->onQuestCompleted($event);
    }
}
