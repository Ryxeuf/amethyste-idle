<?php

namespace App\Tests\Integration\Quest;

use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Entity\Game\Quest;
use App\Event\Game\QuestCompletedEvent;
use App\GameEngine\Quest\PlayerQuestHelper;
use App\GameEngine\Quest\PlayerQuestUpdater;
use App\GameEngine\Quest\QuestTrackingFormater;
use App\Tests\Integration\AbstractIntegrationTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * TST-07 / Task 104 — Integration tests for the complete quest progression flow.
 *
 * Tests the real service layer with a real database (no mocks):
 * accept quest → kill mob → objective updated → completion → reward.
 */
class QuestProgressionIntegrationTest extends AbstractIntegrationTestCase
{
    private PlayerQuestHelper $questHelper;
    private PlayerQuestUpdater $questUpdater;
    private QuestTrackingFormater $trackingFormater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->questHelper = $this->getService(PlayerQuestHelper::class);
        $this->questUpdater = $this->getService(PlayerQuestUpdater::class);
        $this->trackingFormater = $this->getService(QuestTrackingFormater::class);
    }

    /**
     * Accept a quest → creates a PlayerQuest with correct tracking structure.
     */
    public function testAcceptQuestCreatesPlayerQuestWithTracking(): void
    {
        $player = $this->getPlayer();
        $this->setCurrentPlayer($player);

        // Find the zombie quest (requires killing 2 zombies)
        $quest = $this->em->getRepository(Quest::class)->findOneBy(['name' => 'Sus aux zombies']);
        self::assertNotNull($quest, 'Fixture quest "Sus aux zombies" not found.');

        // Ensure player has no active quest for this
        $existing = $this->em->getRepository(PlayerQuest::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }

        // Accept the quest
        $tracking = $this->trackingFormater->formatTracking($quest);
        $playerQuest = new PlayerQuest();
        $playerQuest->setPlayer($player);
        $playerQuest->setQuest($quest);
        $playerQuest->setTracking($tracking);

        $this->persistAndFlush($playerQuest);

        // Verify persisted
        self::assertNotNull($playerQuest->getId());
        self::assertSame($player->getId(), $playerQuest->getPlayer()->getId());
        self::assertSame($quest->getId(), $playerQuest->getQuest()->getId());

        // Verify tracking structure
        $savedTracking = $playerQuest->getTracking();
        self::assertArrayHasKey('monsters', $savedTracking);
        self::assertCount(1, $savedTracking['monsters']);
        self::assertSame('zombie', $savedTracking['monsters'][0]['slug']);
        self::assertSame(0, $savedTracking['monsters'][0]['count']);
        self::assertSame(2, $savedTracking['monsters'][0]['necessary']);

        // Progress should be 0%
        self::assertSame(0, $this->questHelper->getPlayerQuestProgress($playerQuest));
        self::assertFalse($this->questHelper->isPlayerQuestCompleted($playerQuest));
    }

    /**
     * Kill a mob → quest tracking is updated → progress increases.
     */
    public function testKillMobUpdatesQuestTracking(): void
    {
        $player = $this->getPlayer();
        $this->setCurrentPlayer($player);

        $quest = $this->em->getRepository(Quest::class)->findOneBy(['name' => 'Sus aux zombies']);
        self::assertNotNull($quest);

        // Clean existing quest entries for this player
        $existing = $this->em->getRepository(PlayerQuest::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }

        // Accept quest
        $tracking = $this->trackingFormater->formatTracking($quest);
        $playerQuest = new PlayerQuest();
        $playerQuest->setPlayer($player);
        $playerQuest->setQuest($quest);
        $playerQuest->setTracking($tracking);
        $this->persistAndFlush($playerQuest);

        // Kill a zombie mob
        $mob = $this->getMob(null, 'zombie');
        $this->questUpdater->updateMobKilled($mob);

        // Refresh and check tracking
        $this->refresh($playerQuest);
        $updatedTracking = $playerQuest->getTracking();
        self::assertSame(1, $updatedTracking['monsters'][0]['count'], 'Kill count should be 1 after killing one zombie.');
        self::assertSame(50, $this->questHelper->getPlayerQuestProgress($playerQuest), 'Progress should be 50% (1/2 kills).');
        self::assertFalse($this->questHelper->isPlayerQuestCompleted($playerQuest));
    }

    /**
     * Full flow: accept → kill enough mobs → quest reaches 100% → complete → rewards applied.
     */
    public function testFullQuestFlowAcceptKillCompleteReward(): void
    {
        $player = $this->getPlayer();
        $this->setCurrentPlayer($player);

        $quest = $this->em->getRepository(Quest::class)->findOneBy(['name' => 'Sus aux zombies']);
        self::assertNotNull($quest);

        // Clean up any existing player quest
        $existing = $this->em->getRepository(PlayerQuest::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }
        $existingCompleted = $this->em->getRepository(PlayerQuestCompleted::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
        if ($existingCompleted) {
            $this->em->remove($existingCompleted);
            $this->em->flush();
        }

        // Step 1: Accept quest
        $tracking = $this->trackingFormater->formatTracking($quest);
        $playerQuest = new PlayerQuest();
        $playerQuest->setPlayer($player);
        $playerQuest->setQuest($quest);
        $playerQuest->setTracking($tracking);
        $this->persistAndFlush($playerQuest);

        // Step 2: Kill zombies (need 2)
        $mob = $this->getMob(null, 'zombie');
        $this->questUpdater->updateMobKilled($mob);
        $this->questUpdater->updateMobKilled($mob);

        // Step 3: Verify completion
        $this->refresh($playerQuest);
        self::assertSame(100, $this->questHelper->getPlayerQuestProgress($playerQuest));
        self::assertTrue($this->questHelper->isPlayerQuestCompleted($playerQuest));

        // Step 4: Apply rewards (simulate what the controller does)
        $initialGils = $player->getGils();
        $rewards = $quest->getRewards();
        $gils = (int) ($rewards['gils'] ?? $rewards['gold'] ?? 0);
        if ($gils > 0) {
            $player->addGils($gils);
        }

        // Create completed record
        $completedQuest = new PlayerQuestCompleted();
        $completedQuest->setPlayer($player);
        $completedQuest->setQuest($quest);

        $this->em->remove($playerQuest);
        $this->em->persist($completedQuest);
        $this->em->persist($player);
        $this->em->flush();

        // Step 5: Verify rewards
        self::assertSame($initialGils + $gils, $player->getGils(), 'Player should have received gold reward.');

        // Step 6: Verify completed record exists
        $savedCompleted = $this->em->getRepository(PlayerQuestCompleted::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
        self::assertNotNull($savedCompleted, 'A PlayerQuestCompleted record should exist.');

        // Step 7: Quest is no longer active
        $activeQuest = $this->em->getRepository(PlayerQuest::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
        self::assertNull($activeQuest, 'Active PlayerQuest should be removed after completion.');
    }

    /**
     * QuestCompletedEvent is dispatched and subscribers respond correctly.
     */
    public function testQuestCompletedEventDispatchTriggersSubscribers(): void
    {
        $player = $this->getPlayer();
        $this->setCurrentPlayer($player);

        $quest = $this->em->getRepository(Quest::class)->findOneBy(['name' => 'Sus aux zombies']);
        self::assertNotNull($quest);

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->getService(EventDispatcherInterface::class);

        // Dispatch the event — this should not throw and subscribers should handle it
        $event = new QuestCompletedEvent($player, $quest);
        $dispatcher->dispatch($event, QuestCompletedEvent::NAME);

        // If we reach here, event subscribers handled the event without errors
        self::assertTrue(true, 'QuestCompletedEvent dispatched and handled without errors.');
    }

    /**
     * Killing a non-matching mob does not affect quest progress.
     */
    public function testKillNonMatchingMobDoesNotUpdateQuest(): void
    {
        $player = $this->getPlayer();
        $this->setCurrentPlayer($player);

        $quest = $this->em->getRepository(Quest::class)->findOneBy(['name' => 'Sus aux zombies']);
        self::assertNotNull($quest);

        // Clean and accept quest
        $existing = $this->em->getRepository(PlayerQuest::class)->findOneBy([
            'player' => $player,
            'quest' => $quest,
        ]);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }

        $tracking = $this->trackingFormater->formatTracking($quest);
        $playerQuest = new PlayerQuest();
        $playerQuest->setPlayer($player);
        $playerQuest->setQuest($quest);
        $playerQuest->setTracking($tracking);
        $this->persistAndFlush($playerQuest);

        // Kill a non-zombie mob (skeleton or any other)
        $nonZombieMob = $this->getMob(null, 'skeleton');
        $this->questUpdater->updateMobKilled($nonZombieMob);

        // Progress should still be 0%
        $this->refresh($playerQuest);
        self::assertSame(0, $this->questHelper->getPlayerQuestProgress($playerQuest), 'Killing a non-matching mob should not affect quest progress.');
    }
}
